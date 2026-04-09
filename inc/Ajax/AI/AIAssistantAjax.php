<?php

namespace LearnPress\Ajax\AI;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\AI\Assistant\AIAssistantController;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Throwable;

/**
 * Class AIAssistantAjax
 *
 * Handles student-facing AJAX requests for the LP AI Assistant.
 *
 * Intentionally separate from OpenAiAjax — every method in OpenAiAjax
 * enforces ROLE_ADMINISTRATOR || ROLE_INSTRUCTOR. Mixing student-facing
 * methods into the same class creates conflicting permission models.
 *
 * Transport: lp-load-ajax (same AbstractAjax mechanic as all other LP AJAX classes).
 *
 * @package LearnPress\Ajax\AI
 * @since 4.3.5
 */
class AIAssistantAjax extends AbstractAjax {

	/**
	 * Handle assistant chat request from a logged-in learner.
	 *
	 * Request data (JSON-encoded in 'data' param):
	 * {
	 *   "message": string,
	 *   "lesson_id": int,
	 *   "course_id": int,
	 *   "history": [{role, content}, ...],
	 *   "active_quiz_questions": []
	 * }
	 *
	 * Response shape:
	 * { "status": "success", "message": "", "data": { "type": "text|quiz", "message": "...", "quiz": {} } }
	 */
	public function openai_assistant_chat() {
		$response = new LP_REST_Response();

		try {
			if ( ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be logged in to use the AI Assistant.', 'learnpress' ) );
			}

			if ( ! AIAssistantController::is_enabled() ) {
				throw new Exception( __( 'AI Assistant is not available.', 'learnpress' ) );
			}

			$data_str = LP_Request::get_param( 'data' );
			$data     = LP_Helper::json_decode( $data_str, true );

			if ( ! is_array( $data ) ) {
				throw new Exception( __( 'Invalid request data.', 'learnpress' ) );
			}

			$data = $this->normalize_request_data( $data );

			$controller = new AIAssistantController();
			$result     = $controller->handle_chat( $data );
			$result     = $this->normalize_response_data( $result );

			$response->status  = 'success';
			$response->message = '';
			$response->data    = $result;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
			$response->data    = $this->normalize_response_data( array() );
		}

		wp_send_json( $response );
	}

	/**
	 * Normalize request payload to a stable shape before controller validation.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function normalize_request_data( array $data ): array {
		return array(
			'message'               => isset( $data['message'] ) ? (string) $data['message'] : '',
			'lesson_id'             => absint( $data['lesson_id'] ?? 0 ),
			'course_id'             => absint( $data['course_id'] ?? 0 ),
			'history'               => is_array( $data['history'] ?? null ) ? $data['history'] : array(),
			'active_quiz_questions' => is_array( $data['active_quiz_questions'] ?? null ) ? $data['active_quiz_questions'] : array(),
		);
	}

	/**
	 * Normalize controller result to required frontend contract.
	 *
	 * @param array $result
	 *
	 * @return array{type:string,message:string,quiz:array|null}
	 */
	private function normalize_response_data( array $result ): array {
		$type = $result['type'] ?? 'text';

		if ( ! in_array( $type, array( 'text', 'quiz' ), true ) ) {
			$type = 'text';
		}

		return array(
			'type'    => $type,
			'message' => isset( $result['message'] ) ? (string) $result['message'] : '',
			'quiz'    => is_array( $result['quiz'] ?? null ) ? $result['quiz'] : null,
		);
	}
}
