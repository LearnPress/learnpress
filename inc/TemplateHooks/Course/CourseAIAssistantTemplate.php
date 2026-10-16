<?php
/**
 * Template hook: AI Assistant floating chat panel on curriculum pages.
 *
 * Two item types:
 * - Lesson pages: Show quick actions (Summarize, Explain, Mini Quiz) + optional free chat.
 * - Quiz pages:  Show ONLY after user completed the quiz → Smart Review button only.
 *
 * @since   4.3.5
 * @version 1.1.0
 * @package LearnPress\TemplateHooks\Course
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Template;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_Debug;
use LP_Global;
use LP_Page_Controller;
use LP_Settings;
use LearnPress\AI\Assistant\AIAssistantController;
use Throwable;

defined( 'ABSPATH' ) || exit;

class CourseAIAssistantTemplate {

	/**
	 * Cached render state for the current request.
	 *
	 * @var array|false
	 */
	protected $render_state = false;

	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {}

	/**
	 * Gate checks — all must pass before rendering.
	 *
	 * Allows both lesson pages AND quiz item pages (quiz pages only when
	 * the user has completed the quiz — checked later in render_widget).
	 *
	 * @return bool
	 */
	protected function should_render(): bool {
		$current_page = LP_Page_Controller::page_current();
		if ( ! in_array(
			$current_page,
			array( LP_PAGE_SINGLE_COURSE_CURRICULUM, LP_PAGE_QUIZ )
		) ) {
			return false;
		}

		if ( ! AIAssistantController::is_enabled() ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		return true;
	}

	/**
	 * Resolve and cache the render state for the current request.
	 *
	 * @return array|false
	 */
	protected function get_render_state() {
		$item_type = ( $item = LP_Global::course_item() ) ? (string) $item->get_item_type() : '';
		$course_id = $item ? absint( $item->get_course_id() ) : 0;
		$item_id   = $item ? absint( $item->get_id() ) : 0;
		$user_id   = get_current_user_id();

		/**
		 * Defense in depth: run the same resolver the AJAX controller uses, so the widget
		 * is never offered for an item the user cannot view. This is not the security
		 * boundary — AIAssistantController::handle_chat() is, because the AJAX action is
		 * reachable without this markup ever rendering.
		 *
		 * Catches Throwable because this runs while resolving frontend asset state.
		 * Any failure denies rather than fatals the page.
		 */
		try {
			AIAssistantController::resolve_item_access( $user_id, $course_id, $item_type, $item_id );
		} catch ( Throwable $e ) {
			$this->render_state = false;
			return $this->render_state;
		}

		$enabled_actions   = AIAssistantController::get_enabled_actions();
		$free_chat_enabled = LP_Settings::get_option( 'ai_assistant_free_chat', 'no' ) === 'yes';

		if ( $item_type === 'lp_quiz' ) {
			if ( ! ( $enabled_actions['smart_review'] ?? true ) ) {
				$this->render_state = false;
				return $this->render_state;
			}

			$quiz_result = $this->get_completed_quiz_result( $user_id, $item_id, $course_id );
			if ( $quiz_result === false ) {
				$this->render_state = false;
				return $this->render_state;
			}

			$enabled_actions   = array(
				'summarize'    => false,
				'explain'      => false,
				'quick_quiz'   => false,
				'smart_review' => true,
			);
			$free_chat_enabled = false;
		} else {
			$enabled_actions['smart_review'] = false;

			if ( ! $free_chat_enabled && ! in_array( true, $enabled_actions, true ) ) {
				$this->render_state = false;
				return $this->render_state;
			}

			$quiz_result = null;
		}

		$this->render_state = array(
			'item_id'           => $item_id,
			'item_type'         => $item_type,
			'course_id'         => $course_id,
			'enabled_actions'   => $enabled_actions,
			'free_chat_enabled' => $free_chat_enabled,
			'quiz_result'       => $quiz_result,
		);

		return $this->render_state;
	}

	/**
	 * Build the frontend runtime config for the widget.
	 *
	 * @param array $render_state Computed render state.
	 * @return array
	 */
	protected function get_widget_config( array $render_state ): array {
		return array(
			'ajaxUrl'         => LP_Settings::url_handle_lp_ajax(),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'lessonId'        => $render_state['item_id'],
			'itemId'          => $render_state['item_id'],
			// Server-resolved curriculum type. The client echoes it back as item_type
			// and the server re-validates it; it is transport, not proof.
			'itemType'        => $render_state['item_type'],
			'courseId'        => $render_state['course_id'],
			'quizCompleted'   => $render_state['item_type'] === 'lp_quiz',
			'quizResult'      => $render_state['quiz_result'],
			'enabled'         => true,
			'freeChatEnabled' => $render_state['free_chat_enabled'],
			'enabledActions'  => $render_state['enabled_actions'],
			'i18n'            => array(
				'you'               => __( 'You', 'learnpress' ),
				'assistant'         => __( 'AI Assistant', 'learnpress' ),
				'thinking'          => __( 'Thinking...', 'learnpress' ),
				'sendError'         => __( 'An error occurred. Please try again.', 'learnpress' ),
				'clearConfirm'      => __( 'Clear chat history?', 'learnpress' ),
				'quizPrompt'        => __( 'Create a quick quiz from this lesson.', 'learnpress' ),
				'explainPrompt'     => __( 'Explain a concept from this lesson.', 'learnpress' ),
				'summarizePrompt'   => __( 'Summarize this lesson with key points.', 'learnpress' ),
				'smartReviewPrompt' => __( 'Give me a smart review of my quiz results.', 'learnpress' ),
				'quizCorrectTitle'  => __( 'Correct', 'learnpress' ),
				'quizWrongTitle'    => __( 'Incorrect', 'learnpress' ),
			),
		);
	}

	/**
	 * Get quiz result if user has completed the specific quiz.
	 *
	 * Returns the result array from UserQuizModel::get_result() when the quiz
	 * status is LP_ITEM_COMPLETED, or false if not completed yet.
	 *
	 * @param int $user_id
	 * @param int $quiz_id
	 * @param int $course_id
	 *
	 * @return array|false Result array on completion, false otherwise.
	 */
	private function get_completed_quiz_result( int $user_id, int $quiz_id, int $course_id ) {
		if ( $user_id <= 0 || $quiz_id <= 0 || $course_id <= 0 ) {
			return false;
		}

		$user_quiz = UserQuizModel::find_user_item(
			$user_id,
			$quiz_id,
			LP_QUIZ_CPT,
			$course_id,
			LP_COURSE_CPT,
			true
		);

		if ( ! $user_quiz instanceof UserQuizModel ) {
			return false;
		}

		if ( ! method_exists( $user_quiz, 'get_status' ) || $user_quiz->get_status() !== LP_ITEM_COMPLETED ) {
			return false;
		}

		if ( ! method_exists( $user_quiz, 'get_result' ) ) {
			return false;
		}

		$result = $user_quiz->get_result();

		return is_array( $result ) ? $result : false;
	}

	/**
	 * Scrollable message log container (populated by JS).
	 *
	 * @return string
	 */
	public function html_messages(): string {
		$section = apply_filters(
			'learn-press/ai-assistant/html-messages',
			array(
				'wrapper'     => '<div class="lp-ai-assistant__messages-wrap">',
				'messages'    => '<div class="lp-ai-assistant__messages" role="log" aria-live="polite" aria-relevant="additions"></div>',
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Quick-action buttons row (Summarize, Smart Review).
	 *
	 * @return string
	 */
	public function html_quick_actions( array $enabled_actions = array() ): string {
		$buttons = array();

		if ( $enabled_actions['explain'] ?? true ) {
			$buttons[] = sprintf(
				'<button type="button" class="lp-ai-assistant__quick-btn" data-lp-ai-action="explain">%s</button>',
				esc_html__( 'Explain Concept', 'learnpress' )
			);
		}

		if ( $enabled_actions['quick_quiz'] ?? true ) {
			$buttons[] = sprintf(
				'<button type="button" class="lp-ai-assistant__quick-btn" data-lp-ai-action="quick-quiz">%s</button>',
				esc_html__( 'Quick Quiz', 'learnpress' )
			);
		}

		if ( $enabled_actions['summarize'] ?? true ) {
			$buttons[] = sprintf(
				'<button type="button" class="lp-ai-assistant__quick-btn" data-lp-ai-action="summarize">%s</button>',
				esc_html__( 'Summarize Lesson', 'learnpress' )
			);
		}

		if ( $enabled_actions['smart_review'] ?? true ) {
			$buttons[] = sprintf(
				'<button type="button" class="lp-ai-assistant__quick-btn lp-ai-assistant__smart-review-btn" data-lp-ai-action="smart-review">%s</button>',
				esc_html__( 'Smart Review', 'learnpress' )
			);
		}

		if ( empty( $buttons ) ) {
			return '';
		}

		$section = apply_filters(
			'learn-press/ai-assistant/html-quick-actions',
			array(
				'wrapper'     => '<div class="lp-ai-assistant__quick-actions" role="group" aria-label="' . esc_attr__( 'AI assistant quick actions', 'learnpress' ) . '">',
				'buttons'     => implode( '', $buttons ),
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Textarea + Send button input row.
	 *
	 * @return string
	 */
	public function html_input_area(): string {
		$textarea = sprintf(
			'<textarea class="lp-ai-assistant__input" rows="1" aria-label="%s" placeholder="%s"></textarea>',
			esc_attr__( 'Your message to the AI assistant', 'learnpress' ),
			esc_attr__( 'Type your message', 'learnpress' )
		);

		$send_btn = sprintf(
			'<span class="lp-ai-assistant__send-btn lp-icon-comment-o" aria-label="%1$s" title="%1$s" aria-hidden="true"></span>',
			esc_attr__( 'Send message', 'learnpress' )
		);

		$section = apply_filters(
			'learn-press/ai-assistant/html-input-area',
			array(
				'wrapper'      => '<div class="lp-ai-assistant__input-area">',
				'composer'     => '<div class="lp-ai-assistant__composer">',
				'textarea'     => $textarea,
				'send_btn'     => $send_btn,
				'composer_end' => '</div>',
				'wrapper_end'  => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Footer controls pinned to the bottom of the panel.
	 *
	 * @param bool  $free_chat_enabled Whether to render the textarea/send-button input area.
	 * @param array $enabled_actions Enabled quick actions.
	 *
	 * @return string
	 */
	public function html_panel_footer( bool $free_chat_enabled = true, array $enabled_actions = array() ): string {
		$content = sprintf(
			'%s%s',
			$this->html_quick_actions( $enabled_actions ),
			$free_chat_enabled ? $this->html_input_area() : ''
		);

		if ( '' === $content ) {
			return '';
		}

		$section = apply_filters(
			'learn-press/ai-assistant/html-panel-footer',
			array(
				'wrapper'     => '<div class="lp-ai-assistant__panel-footer">',
				'content'     => $content,
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * @return string
	 */
	public function layout_ai_assistant_on_learning_content_bar(): string {
		$render_state = $this->get_render_state();
		if ( ! $render_state ) {
			return '';
		}

		if ( ! $this->should_render() ) {
			return '';
		}

		wp_enqueue_script( 'lp-ai-assistant' );
		wp_enqueue_style( 'lp-ai-assistant' );

		$section_head = [
			'wrap'    => '<div class="lp-learning-bar-item-head">',
			'icon' => '<span class="lp-icon lp-icon-ai-assistant"></span>',
			'title'   => sprintf(
				'<span class="lp-ai-assistant__title lp-learning-bar-item-title">%s</span>',
				esc_html__( 'AI Learning Assistant', 'learnpress' )
			),
			'actions' => sprintf(
				'<span class="lp-ai-assistant__clear-btn lp-icon-trash-o"
					aria-label="%1$s" title="%1$s" aria-hidden="true"></span>',
				esc_attr__( 'Clear chat history', 'learnpress' ),
			),
			'wrap-end' => '</div>',
		];

		$section_content = [
			'wrapper'     => sprintf(
				'<div id="lp-ai-assistant"
					class="lp-learning-bar-item-content lp-ai-assistant"
					data-lp-ai-config="%s">',
				esc_attr( wp_json_encode( $this->get_widget_config( $render_state ) ) )
			),
			'message'     => sprintf(
				'%s',
				$this->html_messages()
			),
			'footer'      => $this->html_panel_footer(
				$render_state['free_chat_enabled'],
				$render_state['enabled_actions']
			),
			'wrapper_end' => '</div>',
		];

		$section = apply_filters(
			'learn-press/learning-bar/ai-assistant',
			[
				'wrapper'     => '<template id="lp-ai-assistant-template">',
				'head'        => Template::combine_components( $section_head ),
				'content'     => Template::combine_components( $section_content ),
				'wrapper_end' => '</template>',
			]
		);

		return Template::combine_components( $section );
	}
}
