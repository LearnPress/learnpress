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
	 * Shared inline SVG icons used by the widget controls.
	 *
	 * @param string $icon Icon name.
	 *
	 * @return string
	 */
	private function icon_markup( string $icon ): string {
		$icons = array(
			'trash'         => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_6750_3601)"><path d="M14.28 2C14.6998 2.00011 15.1088 2.13229 15.4493 2.37781C15.7898 2.62333 16.0444 2.96975 16.177 3.368L16.72 5H20C20.2652 5 20.5196 5.10536 20.7071 5.29289C20.8946 5.48043 21 5.73478 21 6C21 6.26522 20.8946 6.51957 20.7071 6.70711C20.5196 6.89464 20.2652 7 20 7L19.997 7.071L19.13 19.214C19.0759 19.9706 18.7372 20.6786 18.182 21.1956C17.6269 21.7125 16.8965 21.9999 16.138 22H7.862C7.10346 21.9999 6.37311 21.7125 5.81797 21.1956C5.26283 20.6786 4.92411 19.9706 4.87 19.214L4.003 7.07C4.00119 7.04671 4.00019 7.02336 4 7C3.73478 7 3.48043 6.89464 3.29289 6.70711C3.10536 6.51957 3 6.26522 3 6C3 5.73478 3.10536 5.48043 3.29289 5.29289C3.48043 5.10536 3.73478 5 4 5H7.28L7.823 3.368C7.9557 2.96959 8.21043 2.62305 8.5511 2.37752C8.89176 2.13198 9.30107 1.9999 9.721 2H14.28ZM17.997 7H6.003L6.865 19.071C6.88295 19.3232 6.99577 19.5592 7.18076 19.7316C7.36574 19.904 7.60916 19.9999 7.862 20H16.138C16.3908 19.9999 16.6343 19.904 16.8192 19.7316C17.0042 19.5592 17.117 19.3232 17.135 19.071L17.997 7ZM10 10C10.2449 10 10.4813 10.09 10.6644 10.2527C10.8474 10.4155 10.9643 10.6397 10.993 10.883L11 11V16C10.9997 16.2549 10.9021 16.5 10.7272 16.6854C10.5522 16.8707 10.313 16.9822 10.0586 16.9972C9.80416 17.0121 9.55362 16.9293 9.35817 16.7657C9.16271 16.6021 9.0371 16.3701 9.007 16.117L9 16V11C9 10.7348 9.10536 10.4804 9.29289 10.2929C9.48043 10.1054 9.73478 10 10 10ZM14 10C14.2652 10 14.5196 10.1054 14.7071 10.2929C14.8946 10.4804 15 10.7348 15 11V16C15 16.2652 14.8946 16.5196 14.7071 16.7071C14.5196 16.8946 14.2652 17 14 17C13.7348 17 13.4804 16.8946 13.2929 16.7071C13.1054 16.5196 13 16.2652 13 16V11C13 10.7348 13.1054 10.4804 13.2929 10.2929C13.4804 10.1054 13.7348 10 14 10ZM14.28 4H9.72L9.387 5H14.613L14.28 4Z" fill="white"/></g><defs><clipPath id="clip0_6750_3601"><rect width="24" height="24" fill="white"/></clipPath></defs></svg>',
			'chevron-right' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.75 6.75L15 12L9.75 17.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			'send'          => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 2L11 13" stroke="#333333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="#333333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		);

		if ( empty( $icons[ $icon ] ) ) {
			return '';
		}

		return sprintf(
			'<span class="lp-ai-assistant__icon lp-ai-assistant__icon--%1$s" aria-hidden="true">%2$s</span>',
			esc_attr( $icon ),
			$icons[ $icon ]
		);
	}

	/**
	 * Toggle button that opens/closes the chat panel.
	 *
	 * @return string
	 */
	public function html_toggle(): string {
		$icon  = '<span class="lp-ai-assistant__toggle-icon" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_6927_497)"><path fill-rule="evenodd" clip-rule="evenodd" d="M2 6C2 5.20435 2.31607 4.44129 2.87868 3.87868C3.44129 3.31607 4.20435 3 5 3H16.8931C16.7692 3.10939 16.6222 3.19366 16.4658 3.24707L16.1826 3.34473L16.1562 3.35352L16.1309 3.36426L16.043 3.40039L16.0098 3.41309L15.9785 3.42871C15.7067 3.56224 15.4746 3.76379 15.3047 4.0127L15.2354 4.12207C15.0805 4.38933 15.0001 4.6924 15.0003 5H5C4.73478 5 4.48043 5.10536 4.29289 5.29289C4.10536 5.48043 4 5.73478 4 6V16C4 16.2652 4.10536 16.5196 4.29289 16.7071C4.48043 16.8946 4.73478 17 5 17H6.5C6.89782 17 7.27936 17.158 7.56066 17.4393C7.84196 17.7206 8 18.1022 8 18.5V19L10.133 17.4C10.4792 17.1404 10.9003 17 11.333 17H19C19.2652 17 19.5196 16.8946 19.7071 16.7071C19.8946 16.5196 20 16.2652 20 16V8.6853C20.0211 8.67057 20.042 8.65535 20.0625 8.63965C20.3374 8.42941 20.5445 8.14291 20.6562 7.81543L20.7529 7.53125L20.7842 7.44629C20.8523 7.28623 20.954 7.14234 21.0811 7.02344C21.2103 6.90252 21.3667 6.81009 21.5342 6.75293L21.8174 6.65527L21.8438 6.64648L21.8691 6.63574L21.957 6.59961L21.9902 6.58691L22 6.58203V16C22 16.7957 21.6839 17.5587 21.1213 18.1213C20.5587 18.6839 19.7956 19 19 19H11.333L8 21.5C7.176 22.118 6 21.53 6 20.5V19H5C4.20435 19 3.44129 18.6839 2.87868 18.1213C2.31607 17.5587 2 16.7957 2 16V6ZM16.0004 5H18.3975C18.6206 4.82259 18.8226 4.61964 19 4.39648C19.1774 4.61964 19.3794 4.82259 19.6025 5C19.5558 5.03719 19.5099 5.07545 19.4651 5.11472C19.5535 5.16116 19.6352 5.22099 19.7071 5.29289C19.8946 5.48043 20 5.73478 20 6V6.78853C20.1085 6.60578 20.2427 6.43868 20.3984 6.29297C20.511 6.18771 20.6338 6.09452 20.7648 6.01472C20.8705 5.95029 20.9816 5.89475 21.0968 5.84846C21.1338 5.83359 21.1712 5.81954 21.209 5.80664L21.4922 5.70898L21.5811 5.67383C21.7141 5.60843 21.8251 5.50427 21.8994 5.37598C21.9082 5.36075 21.9165 5.34526 21.9241 5.32952C21.8377 4.95257 21.679 4.59527 21.4565 4.27785L21.209 4.19336L21.0703 4.1416C20.7762 4.01887 20.5107 3.83429 20.293 3.60156C20.1683 3.46819 20.0605 3.32041 19.9719 3.16178C19.6619 3.05564 19.3338 3 19 3H18.1108C17.9889 3.26601 17.8156 3.50582 17.6016 3.70605C17.3688 3.92379 17.0926 4.09046 16.791 4.19336L16.5078 4.29004L16.4189 4.32617C16.2859 4.39158 16.1749 4.49574 16.1006 4.62402C16.0344 4.73841 16.0003 4.86834 16.0004 5Z" fill="white"/<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 7C12.1872 7 12.3705 7.05248 12.5293 7.15147C12.688 7.25046 12.8158 7.392 12.8981 7.56L12.9461 7.677L13.0761 8.055C13.2133 8.45718 13.4344 8.82563 13.7247 9.13594C14.015 9.44625 14.3679 9.69135 14.7601 9.855L14.9451 9.925L15.3231 10.054C15.5103 10.1179 15.6744 10.2358 15.7945 10.3929C15.9147 10.55 15.9856 10.7392 15.9982 10.9366C16.0108 11.134 15.9646 11.3307 15.8655 11.5018C15.7663 11.6729 15.6186 11.8108 15.4411 11.898L15.3231 11.946L14.9451 12.076C14.5429 12.2132 14.1745 12.4343 13.8642 12.7246C13.5539 13.0149 13.3088 13.3678 13.1451 13.76L13.0751 13.945L12.9461 14.323C12.8823 14.5102 12.7643 14.6742 12.6072 14.7944C12.4501 14.9146 12.2609 14.9854 12.0635 14.9981C11.8662 15.0107 11.6695 14.9645 11.4983 14.8654C11.3272 14.7662 11.1893 14.6185 11.1021 14.441L11.0541 14.323L10.9241 13.945C10.7869 13.5428 10.5658 13.1744 10.2755 12.8641C9.98521 12.5537 9.63228 12.3087 9.24011 12.145L9.05511 12.075L8.67711 11.946C8.48992 11.8821 8.32587 11.7642 8.20571 11.6071C8.08555 11.45 8.01467 11.2608 8.00204 11.0634C7.98941 10.866 8.03559 10.6693 8.13475 10.4982C8.23391 10.3271 8.38159 10.1892 8.55911 10.102L8.67711 10.054L9.05511 9.924C9.4573 9.7868 9.82574 9.56572 10.1361 9.27541C10.4464 8.98509 10.6915 8.63217 10.8551 8.24L10.9251 8.055L11.0541 7.677C11.1215 7.47959 11.2489 7.30818 11.4185 7.18679C11.5882 7.06539 11.7915 7.00008 12.0001 7ZM12.0001 10.196C11.7635 10.4937 11.4938 10.7634 11.1961 11C11.4941 11.236 11.7631 11.506 12.0001 11.804C12.2361 11.506 12.5061 11.237 12.8041 11C12.5065 10.7634 12.2367 10.4937 12.0001 10.196Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M19 2C19.1403 2 19.2778 2.03936 19.3969 2.1136C19.5159 2.18785 19.6118 2.294 19.6735 2.41999L19.7095 2.50774L19.807 2.79124C19.8509 2.9198 19.906 3.04383 19.9719 3.16178C20.0606 3.32057 20.1685 3.4684 20.2934 3.60193C20.5112 3.83466 20.7758 4.01848 21.07 4.14122L21.2087 4.19372L21.4565 4.27785L21.4922 4.29047C21.6326 4.33836 21.7556 4.42682 21.8457 4.54464C21.9359 4.66246 21.989 4.80436 21.9985 4.95239C22.0068 5.08271 21.9812 5.21272 21.9242 5.32952C21.9165 5.34524 21.9078 5.36107 21.899 5.37629C21.8246 5.50464 21.7138 5.60805 21.5807 5.67345L21.4922 5.70945L21.2087 5.80695C21.171 5.81981 21.1337 5.83365 21.0968 5.84846C20.9816 5.89473 20.8706 5.95033 20.7648 6.01472C20.6339 6.09447 20.5106 6.18808 20.398 6.29339C20.2422 6.43915 20.1083 6.60594 20 6.78853C19.9465 6.87869 19.8993 6.97271 19.8588 7.06993L19.8063 7.20868L19.7095 7.49217C19.6616 7.63256 19.5732 7.7556 19.4553 7.84572C19.3375 7.93584 19.1956 7.989 19.0476 7.99847C18.8996 8.00794 18.752 7.9733 18.6237 7.89894C18.4954 7.82457 18.3919 7.71381 18.3265 7.58067L18.2905 7.49217L18.193 7.20868C18.0901 6.90704 17.9243 6.63071 17.7066 6.39798C17.4889 6.16525 17.2242 5.98143 16.9301 5.8587L16.7913 5.8062L16.5078 5.70945C16.3675 5.66155 16.2444 5.5731 16.1543 5.45528C16.0642 5.33746 16.011 5.19556 16.0016 5.04752C16.0005 5.03167 16.0004 5.01582 16.0004 5C16.0004 4.86811 16.0347 4.73823 16.1011 4.62363C16.1755 4.49528 16.2862 4.39187 16.4193 4.32647L16.5078 4.29047L16.7913 4.19297C17.093 4.09007 17.3693 3.92426 17.602 3.70653C17.8161 3.5062 17.989 3.26618 18.1109 3C18.1214 2.97688 18.1315 2.95349 18.1413 2.92999L18.1938 2.79124L18.2905 2.50774C18.3411 2.35969 18.4366 2.23114 18.5639 2.14009C18.6911 2.04904 18.8436 2.00006 19 2ZM19 4.39697C18.8226 4.6202 18.6203 4.8225 18.397 4.99996C18.6205 5.17695 18.8223 5.37945 19 5.60295C19.1399 5.42641 19.2956 5.26301 19.4651 5.11472C19.5101 5.07537 19.5562 5.03722 19.603 4.99996C19.3798 4.8225 19.1775 4.6202 19 4.39697Z" fill="white"/></g><defs><clipPath id="clip0_6927_497"><rect width="24" height="24" fill="white"/></clipPath></defs></svg></span>';
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
		$title     = Template::instance()->nest_elements(
			array( '<h2 id="lp-ai-assistant-title" class="lp-ai-assistant__title">' => '</h2>' ),
			esc_html__( 'AI Learning Assistant', 'learnpress' )
		);
		$clear_btn = sprintf(
			'<button type="button" class="lp-ai-assistant__clear-btn" title="%1$s" aria-label="%1$s">%2$s<span class="screen-reader-text">%3$s</span></button>',
			esc_attr__( 'Clear chat history', 'learnpress' ),
			$this->icon_markup( 'trash' ),
			esc_html__( 'Clear', 'learnpress' )
		);

		$close_btn = sprintf(
			'<button type="button" class="lp-ai-assistant__close-btn" aria-label="%1$s">%2$s<span class="screen-reader-text">%3$s</span></button>',
			esc_attr__( 'Close AI Assistant', 'learnpress' ),
			$this->icon_markup( 'chevron-right' ),
			esc_html__( 'Close', 'learnpress' )
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
				esc_html__( 'Mini Quiz', 'learnpress' )
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
			'<button type="button" class="lp-ai-assistant__send-btn" aria-label="%1$s">%2$s<span class="screen-reader-text">%3$s</span></button>',
			esc_attr__( 'Send message', 'learnpress' ),
			$this->icon_markup( 'send' ),
			esc_html__( 'Send', 'learnpress' )
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
