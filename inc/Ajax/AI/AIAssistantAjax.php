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
 * @since 4.3.0
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

			$controller = new AIAssistantController();
			$result     = $controller->handle_chat( $data );

			$response->status  = 'success';
			$response->message = '';
			$response->data    = $result;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
