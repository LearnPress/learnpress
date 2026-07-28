<?php

namespace LearnPress\Ajax\AI;

use LearnPress\Ajax\AbstractAjax;
use LearnPress\AI\Assistant\AIAssistantController;
use LP_Debug;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Exception;
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
	 * Nonce is verified by AbstractAjax::catch_lp_ajax() and is CSRF protection only —
	 * never authorization. Login is checked here; per-item authorization is the
	 * controller's job, via AIAssistantController::resolve_item_access().
	 *
	 * Request data (JSON-encoded in 'data' param). A course item is addressed by the
	 * full tuple (course_id, item_type, item_id); all three are required.
	 * {
	 *   "message": string,
	 *   "course_id": int,
	 *   "item_type": string,   // lp_lesson | lp_quiz — validated against the curriculum
	 *   "item_id": int,
	 *   "history": [{role, content}, ...],
	 *   "active_quiz_questions": {},
	 *   "action_hint": string
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
			$result     = $this->normalize_response_data( $result );

			$response->status = 'success';
			$response->data   = $result;
		} catch ( Throwable $e ) {
			$response->message = __( 'The AI Assistant is unavailable right now. Please try again later.', 'learnpress' );
			$response->data    = $this->normalize_response_data( array() );
		}

		wp_send_json( $response );
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
