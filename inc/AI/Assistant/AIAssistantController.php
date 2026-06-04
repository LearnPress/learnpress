<?php

namespace LearnPress\AI\Assistant;

use Exception;
use LP_Settings;
use LearnPress\Services\OpenAiService;

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
	 * Handle an assistant chat request.
	 *
	 * @param array $data Raw decoded data from the AJAX request.
	 *
	 * @return array{type: string, message: string, quiz: array|null}
	 * @throws Exception On validation failure or API error.
	 */
	public function handle_chat( array $data ): array {
		$message     = trim( $data['message'] ?? '' );
		$item_id     = absint( $data['item_id'] ?? 0 );
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
