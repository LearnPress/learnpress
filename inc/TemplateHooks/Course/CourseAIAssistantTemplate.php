<?php
/**
 * Template hook: AI Assistant floating chat panel on lesson curriculum pages.
 *
 * Renders on wp_footer when the current page is a curriculum lesson (not a quiz,
 * not a disabled feature, logged-in users only).
 *
 * @since   4.3.5
 * @version 1.0.0
 * @package LearnPress\TemplateHooks\Course
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LP_Global;
use LP_Page_Controller;
use LP_Settings;
use LearnPress\AI\Assistant\AIAssistantController;
use Throwable;

defined( 'ABSPATH' ) || exit;

class CourseAIAssistantTemplate {

	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'wp_footer', array( $this, 'render_widget' ) );
	}

	/**
	 * Gate checks — all must pass before rendering.
	 *
	 * @return bool
	 */
	protected function should_render(): bool {
		if ( LP_Page_Controller::page_current() !== LP_PAGE_SINGLE_COURSE_CURRICULUM ) {
			return false;
		}

		if ( LP_Global::course_item_quiz() ) {
			return false; // Exclude quiz items.
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
	 * Render the widget on wp_footer.
	 *
	 * Enqueues assets, injects localized data, then prints HTML.
	 */
	public function render_widget() {
		try {
			if ( ! $this->should_render() ) {
				return;
			}

			$item      = LP_Global::course_item();
			$lesson_id = $item ? absint( $item->get_id() ) : 0;
			$course_id = $item ? absint( $item->get_course_id() ) : 0;

			$has_quiz_attempt = $this->has_quiz_attempt( get_current_user_id(), $course_id );

			// Enqueue assets (registered in LP_Assets::_get_scripts / _get_styles).
			wp_enqueue_script( 'lp-ai-assistant' );
			wp_enqueue_style( 'lp-ai-assistant' );

			// Inject dynamic data before the script body runs.
			$js_data = wp_json_encode(
				array(
					'ajaxUrl'        => LP_Settings::url_handle_lp_ajax(),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'lessonId'       => $lesson_id,
					'courseId'       => $course_id,
					'hasQuizAttempt' => $has_quiz_attempt,
					'enabled'        => true,
					'i18n'           => array(
						'you'               => __( 'You', 'learnpress' ),
						'assistant'         => __( 'AI Assistant', 'learnpress' ),
						'thinking'          => __( 'Thinking…', 'learnpress' ),
						'sendError'         => __( 'An error occurred. Please try again.', 'learnpress' ),
						'clearConfirm'      => __( 'Clear the chat history for this lesson?', 'learnpress' ),
						'quizPrompt'        => __( '/mini-quiz', 'learnpress' ),
						'explainPrompt'     => __( '/explain', 'learnpress' ),
						'summarizePrompt'   => __( 'Please summarize the key points of this lesson.', 'learnpress' ),
						'smartReviewPrompt' => __( 'Based on my quiz attempts, which topics should I review?', 'learnpress' ),
					),
				)
			);

			wp_add_inline_script( 'lp-ai-assistant', 'window.lpAIAssistant = ' . $js_data . ';', 'before' );

			$this->html_widget();

		} catch ( Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<!-- LP AI Assistant render error: ' . esc_html( $e->getMessage() ) . ' -->';
			}
		}
	}

	/**
	 * Resolve whether current user has any quiz attempts in the current course.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return bool
	 */
	private function has_quiz_attempt( int $user_id, int $course_id ): bool {
		if ( $user_id <= 0 || $course_id <= 0 ) {
			return false;
		}

		$course = CourseModel::find( $course_id, true );
		if ( ! $course ) {
			return false;
		}

		$sections_items = $course->get_section_items();
		if ( empty( $sections_items ) ) {
			return false;
		}

		foreach ( $sections_items as $section ) {
			if ( empty( $section->items ) || ! is_array( $section->items ) ) {
				continue;
			}

			foreach ( $section->items as $item ) {
				$item_id = absint( $item->id ?? $item->item_id ?? 0 );
				if ( ! $item_id || get_post_type( $item_id ) !== LP_QUIZ_CPT ) {
					continue;
				}

				$user_quiz = UserQuizModel::find_user_item(
					$user_id,
					$item_id,
					LP_QUIZ_CPT,
					$course_id,
					LP_COURSE_CPT,
					true
				);

				if ( ! $user_quiz || ! method_exists( $user_quiz, 'get_attempts' ) ) {
					continue;
				}

				$attempts = $user_quiz->get_attempts( 1 );
				if ( ! empty( $attempts ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Toggle button that opens/closes the chat panel.
	 *
	 * @return string
	 */
	public function html_toggle(): string {
		$icon  = '<span class="lp-ai-assistant__toggle-icon" aria-hidden="true">&#129302;</span>';
		$label = sprintf(
			'<span class="lp-ai-assistant__toggle-label">%s</span>',
			esc_html__( 'AI Assistant', 'learnpress' )
		);

		$section = apply_filters(
			'learn-press/ai-assistant/html-toggle',
			array(
				'wrapper'     => sprintf(
					'<button class="lp-ai-assistant__toggle" aria-label="%s" aria-expanded="false" aria-controls="lp-ai-assistant-panel">',
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
		$title = Template::instance()->nest_elements(
			array( '<h2 id="lp-ai-assistant-title" class="lp-ai-assistant__title">' => '</h2>' ),
			esc_html__( 'AI Learning Assistant', 'learnpress' )
		);

		$clear_btn = sprintf(
			'<button class="lp-ai-assistant__clear-btn" title="%s">%s</button>',
			esc_attr__( 'Clear chat history', 'learnpress' ),
			esc_html__( 'Clear', 'learnpress' )
		);

		$close_btn = sprintf(
			'<button class="lp-ai-assistant__close-btn" aria-label="%s">%s</button>',
			esc_attr__( 'Close AI Assistant', 'learnpress' ),
			'&times;'
		);

		$actions = Template::instance()->nest_elements(
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
				'wrapper'     => '<div class="lp-ai-assistant__messages" role="log" aria-live="polite" aria-relevant="additions">',
				'content'     => '',
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
	public function html_quick_actions(): string {
		$btn_explain = sprintf(
			'<button class="lp-ai-assistant__quick-btn" data-lp-ai-action="explain">%s</button>',
			esc_html__( 'Explain Concept', 'learnpress' )
		);

		$btn_mini_quiz = sprintf(
			'<button class="lp-ai-assistant__quick-btn" data-lp-ai-action="mini-quiz">%s</button>',
			esc_html__( 'Mini Quiz', 'learnpress' )
		);

		$btn_summarize = sprintf(
			'<button class="lp-ai-assistant__quick-btn" data-lp-ai-action="summarize">%s</button>',
			esc_html__( 'Summarize Lesson', 'learnpress' )
		);

		$btn_smart_review = sprintf(
			'<button class="lp-ai-assistant__quick-btn lp-ai-assistant__smart-review-btn" data-lp-ai-action="smart-review">%s</button>',
			esc_html__( 'Smart Review', 'learnpress' )
		);

		$section = apply_filters(
			'learn-press/ai-assistant/html-quick-actions',
			array(
				'wrapper'      => '<div class="lp-ai-assistant__quick-actions">',
				'explain'      => $btn_explain,
				'mini_quiz'    => $btn_mini_quiz,
				'summarize'    => $btn_summarize,
				'smart_review' => $btn_smart_review,
				'wrapper_end'  => '</div>',
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
			'<textarea class="lp-ai-assistant__input" rows="3" aria-label="%s" placeholder="%s"></textarea>',
			esc_attr__( 'Your message to the AI assistant', 'learnpress' ),
			esc_attr__( 'Ask a question about this lesson…', 'learnpress' )
		);

		$send_btn = sprintf(
			'<button class="lp-ai-assistant__send-btn lp-button">%s</button>',
			esc_html__( 'Send', 'learnpress' )
		);

		$section = apply_filters(
			'learn-press/ai-assistant/html-input-area',
			array(
				'wrapper'     => '<div class="lp-ai-assistant__input-area">',
				'textarea'    => $textarea,
				'send_btn'    => $send_btn,
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Full chat panel (header + messages + quick actions + input area).
	 *
	 * @return string
	 */
	public function html_panel(): string {
		$content = sprintf(
			'%s%s%s%s',
			$this->html_header(),
			$this->html_messages(),
			$this->html_quick_actions(),
			$this->html_input_area()
		);

		$section = apply_filters(
			'learn-press/ai-assistant/html-panel',
			array(
				'wrapper'     => '<div id="lp-ai-assistant-panel" class="lp-ai-assistant__panel" role="dialog" aria-labelledby="lp-ai-assistant-title" aria-modal="true" hidden>',
				'content'     => $content,
				'wrapper_end' => '</div>',
			)
		);

		return Template::combine_components( $section );
	}

	/**
	 * Outer floating widget: toggle button + panel, assembled from sub-components.
	 *
	 * Follows LP TemplateHook standard:
	 * - Each visual block is a dedicated `html_*()` method returning string.
	 * - Sections assembled via `Template::combine_components()`.
	 * - Each section wrapped in `apply_filters()` for extensibility.
	 */
	public function html_widget() {
		$section = apply_filters(
			'learn-press/ai-assistant/html-widget',
			array(
				'wrapper'     => '<div id="lp-ai-assistant" class="lp-ai-assistant" aria-hidden="true">',
				'toggle'      => $this->html_toggle(),
				'panel'       => $this->html_panel(),
				'wrapper_end' => '</div>',
			)
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Template::combine_components( $section );
	}
}
