<?php
/**
 * Template hook: AI Assistant floating chat panel on curriculum pages.
 *
 * Two rendering contexts:
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
use LP_Global;
use LP_Page_Controller;
use LP_Settings;
use LearnPress\AI\Assistant\AIAssistantController;
use Throwable;

defined( 'ABSPATH' ) || exit;

class CourseAIAssistantTemplate {

	/**
	 * Shared footer action used to collect launcher buttons inside one wrapper.
	 */
	const FOOTER_LAUNCHERS_HOOK = 'learn-press/course-item-footer-launchers';

	/**
	 * Cached render state for the current request.
	 *
	 * @var array|false
	 */
	protected $render_state = false;

	/**
	 * Whether the render state has already been resolved.
	 *
	 * @var bool
	 */
	protected $render_state_resolved = false;

	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_launcher_wrapper' ), 5 );
		add_action( self::FOOTER_LAUNCHERS_HOOK, array( $this, 'render_launcher' ), 20 );
		add_action( 'wp_footer', array( $this, 'render_panel' ), 10 );
	}

	/**
	 * Enqueue frontend assets early so launcher markup does not rely on inline styles.
	 */
	public function enqueue_assets() {
		if ( ! $this->get_render_state() ) {
			return;
		}

		wp_enqueue_script( 'lp-ai-assistant' );
		wp_enqueue_style( 'lp-ai-assistant' );
	}

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
		if ( ! in_array( $current_page, array( LP_PAGE_SINGLE_COURSE_CURRICULUM, LP_PAGE_QUIZ ), true ) ) {
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
	 * Detect the rendering context.
	 *
	 * @return string 'quiz' | 'lesson'
	 */
	protected function detect_context(): string {
		return LP_Global::course_item_quiz() ? 'quiz' : 'lesson';
	}

	/**
	 * Resolve and cache the render state for the current request.
	 *
	 * @return array|false
	 */
	protected function get_render_state() {
		if ( $this->render_state_resolved ) {
			return $this->render_state;
		}

		$this->render_state_resolved = true;

		if ( ! $this->should_render() ) {
			return $this->render_state = false;
		}

		$context   = $this->detect_context();
		$item      = LP_Global::course_item();
		$item_id   = $item ? absint( $item->get_id() ) : 0;
		$course_id = $item ? absint( $item->get_course_id() ) : 0;
		$user_id   = get_current_user_id();

		$enabled_actions   = AIAssistantController::get_enabled_actions();
		$free_chat_enabled = LP_Settings::get_option( 'ai_assistant_free_chat', 'no' ) === 'yes';

		if ( $context === 'quiz' ) {
			if ( ! ( $enabled_actions['smart_review'] ?? true ) ) {
				return $this->render_state = false;
			}

			$quiz_result = $this->get_completed_quiz_result( $user_id, $item_id, $course_id );
			if ( $quiz_result === false ) {
				return $this->render_state = false;
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
				return $this->render_state = false;
			}

			$quiz_result = null;
		}

		return $this->render_state = array(
			'context'           => $context,
			'item_id'           => $item_id,
			'course_id'         => $course_id,
			'enabled_actions'   => $enabled_actions,
			'free_chat_enabled' => $free_chat_enabled,
			'quiz_result'       => $quiz_result,
		);
	}

	/**
	 * Enqueue assets and localize runtime data for the frontend widget.
	 *
	 * @param array $render_state Computed render state.
	 */
	protected function localize_script_data( array $render_state ) {
		$js_data = wp_json_encode(
			array(
				'ajaxUrl'         => LP_Settings::url_handle_lp_ajax(),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'lessonId'        => $render_state['item_id'],
				'itemId'          => $render_state['item_id'],
				'courseId'        => $render_state['course_id'],
				'context'         => $render_state['context'],
				'quizCompleted'   => $render_state['context'] === 'quiz',
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
			)
		);

		wp_add_inline_script( 'lp-ai-assistant', 'window.lpAIAssistant = ' . $js_data . ';', 'before' );
	}

	/**
	 * Backward-compatible entrypoint kept for external callers.
	 */
	public function render_widget() {
		$this->render_panel();
	}

	/**
	 * Render the shared footer wrapper for launcher buttons.
	 */
	public function render_launcher_wrapper() {
		ob_start();
		do_action( self::FOOTER_LAUNCHERS_HOOK );
		$launchers_html = trim( ob_get_clean() );

		if ( '' === $launchers_html ) {
			return;
		}

		printf(
			'<div class="lp-footer-launchers" aria-label="%1$s">%2$s</div>',
			esc_attr__( 'Learning tools', 'learnpress' ),
			$launchers_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Render the AI Assistant launcher into the shared wrapper.
	 */
	public function render_launcher() {
		if ( ! $this->get_render_state() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->html_toggle();
	}

	/**
	 * Render the AI Assistant panel on wp_footer.
	 */
	public function render_panel() {
		try {
			$render_state = $this->get_render_state();
			if ( ! $render_state ) {
				return;
			}

			$this->localize_script_data( $render_state );
			$this->html_panel_widget(
				$render_state['free_chat_enabled'],
				$render_state['enabled_actions']
			);
		} catch ( Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<!-- LP AI Assistant render error: ' . esc_html( $e->getMessage() ) . ' -->';
			}
		}
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
	 * Toggle button that opens/closes the chat panel.
	 *
	 * @return string
	 */
	public function html_toggle(): string {
		$icon  = '<span class="lp-icon lp-icon-graduation-cap"></span>';
		$label = sprintf(
			'<span class="lp-ai-assistant__toggle-label">%s</span>',
			esc_html__( 'AI Assistant', 'learnpress' )
		);

		$section = apply_filters(
			'learn-press/ai-assistant/html-toggle',
			array(
				'wrapper'     => sprintf(
					'<button type="button" class="lp-ai-assistant__toggle" aria-label="%s" aria-expanded="false" aria-controls="lp-ai-assistant-panel">',
					esc_attr__( 'Open AI Learning Assistant', 'learnpress' )
				),
				'icon'        => $icon,
				'label'       => $label,
				'wrapper_end' => '</button>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Panel header: title + clear and close action buttons.
	 *
	 * @return string
	 */
	public function html_header(): string {
		$title     = sprintf(
			'<h2 id="lp-ai-assistant-title" class="lp-ai-assistant__title">%s</h2>',
			esc_html__( 'AI Learning Assistant', 'learnpress' )
		);
		$clear_btn = sprintf(
			'<span class="lp-ai-assistant__clear-btn lp-icon-trash-o" aria-label="%1$s" title="%1$s" aria-hidden="true"></span>',
			esc_attr__( 'Clear chat history', 'learnpress' ),
		);
		$close_btn = sprintf(
			'<span class="lp-ai-assistant__close-btn lp-icon-angle-right" aria-label="%1$s" title="%1$s" aria-hidden="true"></span>',
			esc_attr__( 'Close AI Assistant', 'learnpress' ),
		);
		$actions   = Template::instance()->nest_elements(
			array( '<div class="lp-ai-assistant__header-actions">' => '</div>' ),
			sprintf( '%s%s', $clear_btn, $close_btn )
		);

		$section = apply_filters(
			'learn-press/ai-assistant/html-header',
			array(
				'wrapper'     => '<div class="lp-ai-assistant__header">',
				'title'       => $title,
				'actions'     => $actions,
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
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
	 * Full chat panel (header + messages + quick actions + optional input area).
	 *
	 * @param bool $free_chat_enabled Whether to render the textarea/send-button input area.
	 *
	 * @return string
	 */
	public function html_panel( bool $free_chat_enabled = true, array $enabled_actions = array() ): string {
		$content = sprintf(
			'%s<div class="lp-ai-assistant__panel-body">%s%s</div>',
			$this->html_header(),
			$this->html_messages(),
			$this->html_panel_footer( $free_chat_enabled, $enabled_actions )
		);

		$panel_class = 'lp-ai-assistant__panel' . ( $free_chat_enabled ? '' : ' lp-ai-assistant-panel--quick-only' );

		$section = apply_filters(
			'learn-press/ai-assistant/html-panel',
			array(
				'wrapper'     => sprintf(
					'<div id="lp-ai-assistant-panel" class="%s" role="dialog" aria-labelledby="lp-ai-assistant-title" aria-modal="true" hidden>',
					esc_attr( $panel_class )
				),
				'content'     => $content,
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Root widget that contains only the floating panel.
	 *
	 * @param bool  $free_chat_enabled Whether to render the full chat input area.
	 * @param array $enabled_actions Enabled quick actions.
	 */
	public function html_panel_widget( bool $free_chat_enabled = true, array $enabled_actions = array() ) {
		$section = apply_filters(
			'learn-press/ai-assistant/html-panel-widget',
			array(
				'wrapper'     => '<div id="lp-ai-assistant" class="lp-ai-assistant" aria-hidden="true">',
				'panel'       => $this->html_panel( $free_chat_enabled, $enabled_actions ),
				'wrapper_end' => '</div>',
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Template::combine_components( $section );
	}

	/**
	 * Outer floating widget: toggle button + panel, assembled from sub-components.
	 *
	 * Follows LP TemplateHook standard:
	 * - Each visual block is a dedicated `html_*()` method returning string.
	 * - Sections assembled via `Template::combine_components()`.
	 * - Each section wrapped in `apply_filters()` for extensibility.
	 *
	 * @param bool $free_chat_enabled Whether to render the full chat input area.
	 */
	public function html_widget( bool $free_chat_enabled = true, array $enabled_actions = array() ) {
		$section = apply_filters(
			'learn-press/ai-assistant/html-widget',
			array(
				'wrapper'     => '<div id="lp-ai-assistant" class="lp-ai-assistant" aria-hidden="true">',
				'toggle'      => $this->html_toggle(),
				'panel'       => $this->html_panel( $free_chat_enabled, $enabled_actions ),
				'wrapper_end' => '</div>',
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Template::combine_components( $section );
	}
}
