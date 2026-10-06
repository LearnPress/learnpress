<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Services\NoteService;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * AJAX handlers for student notes on the learning page.
 *
 * Request: `lp-load-ajax` = action, `nonce` = wp_rest nonce (checked by AbstractAjax),
 * `data` = JSON object. Authorization is checked by NoteService.
 *
 * Response: { status: success|error, message: string, data: {} }
 *
 * @since 4.4.9.2
 */
class NoteAjax extends AbstractAjax {

	/**
	 * List notes of a student on a lesson.
	 *
	 * data: { course_id, item_id, user_id? } — user_id defaults to the current user;
	 * another user's notes are returned only to admins / the course instructors.
	 */
	public function lp_note_list() {
		$response = new LP_REST_Response();

		try {
			$params    = $this->get_params();
			$viewer_id = get_current_user_id();
			$owner_id  = absint( $params['user_id'] ?? 0 );
			$owner_id  = $owner_id > 0 ? $owner_id : $viewer_id;
			$course_id = absint( $params['course_id'] ?? 0 );
			$item_id   = absint( $params['item_id'] ?? 0 );

			$notes = NoteService::instance()->get_item_notes( $viewer_id, $owner_id, $course_id, $item_id );

			$response->status = 'success';
			$response->data   = array(
				'notes'    => array_map( array( NoteService::instance(), 'to_response' ), $notes ),
				'can_edit' => $viewer_id === $owner_id
					&& NoteService::instance()->can_create( $viewer_id, $course_id, $item_id ),
			);
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Create or update a note of the current user.
	 *
	 * data: { note_id?, course_id, item_id, note_type, content, highlight_text?, anchor? }
	 */
	public function lp_note_save() {
		$response = new LP_REST_Response();

		try {
			$params = $this->get_params();
			$is_new = empty( $params['note_id'] );
			$note   = NoteService::instance()->save_note( get_current_user_id(), $params );

			$response->status  = 'success';
			$response->message = $is_new ? __( 'Note added.', 'learnpress' ) : __( 'Note updated.', 'learnpress' );
			$response->data    = array( 'note' => NoteService::instance()->to_response( $note ) );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Delete a note of the current user.
	 *
	 * data: { note_id }
	 */
	public function lp_note_delete() {
		$response = new LP_REST_Response();

		try {
			$params  = $this->get_params();
			$note_id = absint( $params['note_id'] ?? 0 );

			if ( ! NoteService::instance()->delete_note( get_current_user_id(), $note_id ) ) {
				throw new Exception( __( 'Could not delete the note.', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = __( 'Note deleted.', 'learnpress' );
			$response->data    = array( 'note_id' => $note_id );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Decode the JSON `data` param.
	 * Kept raw (only invalid UTF-8 removed) because the highlight quote must match the
	 * lesson text exactly; every field is sanitized by NoteModel::validate().
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function get_params(): array {
		if ( ! is_user_logged_in() ) {
			throw new Exception( __( 'Please log in to use notes.', 'learnpress' ) );
		}

		if ( ! NoteService::is_enabled() ) {
			throw new Exception( __( 'Notes are disabled.', 'learnpress' ) );
		}

		$params = LP_Helper::json_decode( LP_Request::get_param( 'data', '', 'wp_check_invalid_utf8', 'post' ), true );
		if ( ! is_array( $params ) ) {
			throw new Exception( __( 'Invalid request data.', 'learnpress' ) );
		}

		return $params;
	}
}
