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
 * @since 4.3.0
 */
class AIAssistantController {

	/**
	 * Check if the AI Assistant feature is fully enabled.
	 *
	 * All three gates must pass:
	 * - enable_open_ai = yes
	 * - secret key exists
	 * - lp_ai_assistant_enabled = yes
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

		return LP_Settings::get_option( 'lp_ai_assistant_enabled', 'no' ) === 'yes';
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
		$message   = trim( $data['message'] ?? '' );
		$lesson_id = absint( $data['lesson_id'] ?? 0 );
		$course_id = absint( $data['course_id'] ?? 0 );
		$history   = $data['history'] ?? array();
		$quiz_data = $data['active_quiz_questions'] ?? array();

		if ( $message === '' ) {
			throw new Exception( __( 'Message is required.', 'learnpress' ) );
		}

		if ( $lesson_id === 0 ) {
			throw new Exception( __( 'Lesson ID is required.', 'learnpress' ) );
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

		$agent = new Agent();

		return $agent->run(
			sanitize_textarea_field( $message ),
			$lesson_id,
			$course_id,
			$user_id,
			$sanitized_history,
			is_array( $quiz_data ) ? $quiz_data : array()
		);
	}
}
