<?php

namespace LearnPress\AI\Assistant;

use LearnPress\Models\CourseModel;
use LearnPress\Models\PostModel;
use LP_Settings;
use LP_User;
use LearnPress\Services\OpenAiService;
use Exception;

/**
 * AI Assistant Controller — validates requests, sanitizes input, calls Agent.
 *
 * Entry point for the AJAX layer. Guarantees the response structure
 * required by the frontend: { type, message, quiz }.
 *
 * @package LearnPress\AI\Assistant
 * @since 4.3.5
 */
class AIAssistantController {
	private const ACTION_SETTINGS = array(
		'summarize'    => 'ai_assistant_summarize_enabled',
		'explain'      => 'ai_assistant_explain_enabled',
		'quick_quiz'   => 'ai_assistant_quick_quiz_enabled',
		'smart_review' => 'ai_assistant_smart_review_enabled',
	);

	/**
	 * Check if the AI Assistant feature is fully enabled.
	 *
	 * All three gates must pass:
	 * - enable_open_ai = yes
	 * - secret key exists
	 * - ai_assistant_enabled = yes
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$service = OpenAiService::instance();

		if ( ! $service->is_enable() ) {
			return false;
		}

		if ( empty( LP_Settings::get_option( 'open_ai_secret_key', '' ) ) ) {
			return false;
		}

		return LP_Settings::get_option( 'ai_assistant_enabled', 'no' ) === 'yes';
	}

	/**
	 * Resolve per-action enabled flags from admin settings.
	 *
	 * @return array<string, bool>
	 */
	public static function get_enabled_actions(): array {
		$enabled_actions = array();

		foreach ( self::ACTION_SETTINGS as $action => $setting_key ) {
			$enabled_actions[ $action ] = LP_Settings::get_option( $setting_key, 'yes' ) === 'yes';
		}

		return $enabled_actions;
	}

	/**
	 * Check whether a specific assistant action is enabled.
	 *
	 * @param string $action Action slug.
	 *
	 * @return bool
	 */
	public static function is_action_enabled( string $action ): bool {
		$enabled_actions = self::get_enabled_actions();

		return $enabled_actions[ $action ] ?? true;
	}

	/**
	 * Course item types the assistant can be grounded on.
	 *
	 * Deliberately excludes LP_QUESTION_CPT and every third-party item type: an item
	 * type is only listed here once the assistant has a data loader and an access rule
	 * for it. Not a filter — widening this is a code change, reviewed as such.
	 *
	 * Naming contract for the `LearnPress\AI\Assistant` namespace:
	 * - `$item_id`   — a course item ID whose type is not yet proven.
	 * - `$item_type` — the resolved curriculum type: LP_LESSON_CPT or LP_QUIZ_CPT.
	 * - `$lesson_id` — an `$item_id` already proven to be LP_LESSON_CPT.
	 * - `$quiz_id`   — an `$item_id` already proven to be LP_QUIZ_CPT.
	 *
	 * Only proven IDs may be passed to the DataLoaders layer.
	 *
	 * @return string[]
	 */
	public static function get_supported_item_types(): array {
		return array( LP_LESSON_CPT, LP_QUIZ_CPT );
	}

	/**
	 * Resolve and authorize the composite course-item identity for a request.
	 *
	 * A curriculum item is identified by the tuple (course_id, item_type, item_id).
	 * `item_type` arrives from the client and is therefore untrusted: it selects which
	 * typed lookup runs, and the lookup itself is what proves the tuple. It never grants
	 * access on its own. Nothing is ever resolved from `item_id` alone.
	 *
	 * Used by both the AJAX controller and the template renderer so the two cannot drift.
	 *
	 * @param int    $user_id   Current user ID.
	 * @param int    $course_id Course the item is claimed to belong to.
	 * @param string $item_type Raw item type from the request.
	 * @param int    $item_id   Course item ID.
	 *
	 * @return array{course:CourseModel,item:PostModel,item_id:int,item_type:string} Trusted context.
	 * @throws Exception When the tuple is invalid or the user may not view the item.
	 */
	public static function resolve_item_access( int $user_id, int $course_id, string $item_type, int $item_id ): array {
		$denied = __( 'You do not have permission to use the AI Assistant for this course item.', 'learnpress' );

		// item_type is mandatory: without it there is no identity to verify.
		$item_type = sanitize_key( $item_type );
		if ( empty( $item_type ) || ! in_array( $item_type, self::get_supported_item_types(), true ) ) {
			throw new Exception(
				__( 'The AI Assistant is not available for this type of course item.', 'learnpress' )
			);
		}

		if ( $user_id <= 0 || $course_id <= 0 || $item_id <= 0 ) {
			throw new Exception( $denied );
		}

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel instanceof CourseModel ) {
			throw new Exception( $denied );
		}

		/**
		 * Resolves through the supplied type AND asserts curriculum membership, so a
		 * course_id/item_id pair from different courses cannot be combined, and a quiz
		 * ID cannot be resolved as a lesson.
		 */
		$itemModel = $courseModel->get_item_model( $item_id, $item_type, true );
		if ( ! $itemModel instanceof PostModel ) {
			throw new Exception( $denied );
		}

		// Reject drafts, pending, private and trashed items — get_item_model() does not filter status.
		if ( PostModel::STATUS_PUBLISH !== $itemModel->post_status ) {
			throw new Exception( $denied );
		}

		// Canonical LearnPress access policy: course-level gate, then the item-level rule
		// that lets preview items through. Both must pass.
		$user = learn_press_get_user( $user_id );
		if ( ! $user instanceof LP_User ) {
			throw new Exception( $denied );
		}

		$can_view_course = $user->can_view_content_course( $course_id );
		$can_view_item   = $user->can_view_item( $item_id, $can_view_course );
		if ( empty( $can_view_item->flag ) ) {
			$message = (string) ( $can_view_item->message ?? '' );

			throw new Exception( '' !== $message ? $message : $denied );
		}

		return array(
			'course'    => $courseModel,
			'item'      => $itemModel,
			'item_id'   => $item_id,
			'item_type' => $item_type,
		);
	}

	/**
	 * Handle an assistant chat request.
	 *
	 * @param array $data Raw decoded data from the AJAX request.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 * @throws Exception On validation failure or denied access.
	 * @throws Throwable On provider/transport failure — logged and masked by the AJAX layer.
	 */
	public function handle_chat( array $data ): array {
		$message     = trim( $data['message'] ?? '' );
		$item_id     = absint( $data['item_id'] ?? 0 );
		$item_type   = is_scalar( $data['item_type'] ?? null ) ? (string) $data['item_type'] : '';
		$course_id   = absint( $data['course_id'] ?? 0 );
		$history     = $data['history'] ?? array();
		$quiz_data   = $data['active_quiz_questions'] ?? array();
		$action_hint = $this->sanitize_action_hint( $data['action_hint'] ?? '' );

		if ( $message === '' ) {
			throw new Exception( __( 'Message is required.', 'learnpress' ) );
		}

		if ( empty( $item_id ) ) {
			throw new Exception( __( 'Item ID is required.', 'learnpress' ) );
		}

		if ( $course_id === 0 ) {
			throw new Exception( __( 'Course ID is required.', 'learnpress' ) );
		}

		$user_id = get_current_user_id();

		if ( $user_id === 0 ) {
			throw new Exception( __( 'User must be logged in.', 'learnpress' ) );
		}

		/**
		 * Authoritative gate. Runs before the Agent is constructed, so a denied request
		 * costs no prompt construction, no quota accounting and no OpenAI call. The
		 * widget markup check is advisory only — this endpoint is reachable directly.
		 */
		$access    = self::resolve_item_access( $user_id, $course_id, $item_type, $item_id );
		$item_type = $access['item_type'];

		// Sanitize history — only allow safe role/content pairs.
		$sanitized_history = array();
		if ( is_array( $history ) ) {
			foreach ( $history as $msg ) {
				$role    = $msg['role'] ?? '';
				$content = $msg['content'] ?? '';

				if ( in_array( $role, array( 'user', 'assistant' ), true ) && is_string( $content ) ) {
					$sanitized_history[] = array(
						'role'    => $role,
						'content' => sanitize_textarea_field( $content ),
					);
				}
			}
		}

		$agent                = new Agent();
		$sanitized_quiz_state = $this->sanitize_active_quiz_state( $quiz_data );

		return $agent->run(
			sanitize_textarea_field( $message ),
			$item_id,
			$item_type,
			$course_id,
			$user_id,
			$sanitized_history,
			$sanitized_quiz_state,
			$action_hint
		);
	}

	/**
	 * Sanitize optional quick-action hint from frontend.
	 *
	 * @param mixed $action_hint Raw action hint.
	 *
	 * @return string|null
	 */
	private function sanitize_action_hint( $action_hint ): ?string {
		if ( ! is_scalar( $action_hint ) ) {
			return null;
		}

		$normalized = strtolower( trim( (string) $action_hint ) );
		if ( '' === $normalized ) {
			return null;
		}

		$normalized = str_replace( '-', '_', $normalized );

		$aliases = array(
			'quick_quiz'   => 'quick_quiz',
			'summarize'    => 'summarize',
			'explain'      => 'explain',
			'smart_review' => 'smart_review',
		);

		return $aliases[ $normalized ] ?? null;
	}

	/**
	 * Sanitize active quick-quiz state from frontend.
	 *
	 * @param mixed $quiz_data
	 *
	 * @return array
	 */
	private function sanitize_active_quiz_state( $quiz_data ): array {
		if ( ! is_array( $quiz_data ) ) {
			return array();
		}

		$questions = array();
		if ( ! empty( $quiz_data['questions'] ) && is_array( $quiz_data['questions'] ) ) {
			foreach ( $quiz_data['questions'] as $question ) {
				if ( ! is_array( $question ) ) {
					continue;
				}

				$options = array();
				if ( ! empty( $question['options'] ) && is_array( $question['options'] ) ) {
					foreach ( $question['options'] as $option ) {
						$options[] = sanitize_text_field( (string) $option );
					}
				}

				$questions[] = array(
					'question'      => sanitize_text_field( (string) ( $question['question'] ?? '' ) ),
					'options'       => $options,
					'correct_index' => absint( $question['correct_index'] ?? 0 ),
					'explanation'   => sanitize_textarea_field( (string) ( $question['explanation'] ?? '' ) ),
				);
			}
		}

		return array(
			'is_active'     => ! empty( $quiz_data['is_active'] ),
			'completed'     => ! empty( $quiz_data['completed'] ),
			'current_index' => absint( $quiz_data['current_index'] ?? 0 ),
			'score'         => absint( $quiz_data['score'] ?? 0 ),
			'total'         => absint( $quiz_data['total'] ?? count( $questions ) ),
			'questions'     => $questions,
		);
	}
}
