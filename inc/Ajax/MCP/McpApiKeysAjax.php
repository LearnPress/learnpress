<?php
namespace LearnPress\Ajax\MCP;

use LearnPress\Ajax\AbstractAjax;
use LearnPress\MCP\Auth\ApiKeysRepository;
use LP_Helper;
use LP_REST_Response;
use Throwable;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Handle MCP API key CRUD requests through lp-load-ajax transport.
 */
class McpApiKeysAjax extends AbstractAjax {
	/**
	 * @var string
	 */
	protected static $required_capability = 'manage_options';

	/**
	 * Validate current AJAX request for MCP API key actions.
	 *
	 * This helper enforces the required capability and decodes the JSON payload
	 * sent through LearnPress `lp-load-ajax` transport (`$_REQUEST['data']`).
	 * It throws an exception for all invalid states so action handlers can return
	 * a normalized error response.
	 *
	 * @return array<string, mixed>
	 * @throws Exception
	 */
	public static function check_valid(): array {
		if ( ! current_user_can( self::$required_capability ) ) {
			throw new Exception( __( 'You are not allowed to manage MCP API keys.', 'learnpress' ) );
		}

		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( __( 'Error: params invalid!', 'learnpress' ) );
		}

		$params = LP_Helper::json_decode( $params, true );
		if ( ! is_array( $params ) ) {
			throw new Exception( __( 'Error: params invalid!', 'learnpress' ) );
		}

		return $params;
	}

	/**
	 * Create a new LearnPress MCP API key for a selected user.
	 *
	 * Expected payload fields:
	 * - `user_id` (int): key owner user ID.
	 * - `description` (string): optional key description.
	 * - `permissions` (string): one of read/write/read_write.
	 *
	 * Success response includes plaintext credentials once in `data.key`.
	 *
	 * @return void
	 */
	public static function mcp_create_api_key() {
		$response = new LP_REST_Response();

		try {
			$payload = self::check_valid();

			$user_id     = absint( $payload['user_id'] ?? 0 );
			$description = LP_Helper::sanitize_params_submitted( $payload['description'] ?? '' );
			$permissions = LP_Helper::sanitize_params_submitted( $payload['permissions'] ?? 'read', 'key' );

			$created = ( new ApiKeysRepository() )->create_key( $user_id, $description, $permissions );
			if ( ! $created ) {
				throw new Exception( __( 'Could not create API key.', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = __( 'API key created.', 'learnpress' );
			$response->data    = array(
				'key' => $created,
			);
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update mutable metadata for an existing MCP API key.
	 *
	 * Expected payload fields:
	 * - `key_id` (int): target key ID.
	 * - `user_id` (int): updated owner user ID.
	 * - `description` (string): updated description.
	 * - `permissions` (string): updated scope value.
	 *
	 * This action does not return secret material.
	 *
	 * @return void
	 */
	public static function mcp_update_api_key() {
		$response = new LP_REST_Response();

		try {
			$payload = self::check_valid();

			$key_id      = absint( $payload['key_id'] ?? 0 );
			$user_id     = absint( $payload['user_id'] ?? 0 );
			$description = LP_Helper::sanitize_params_submitted( $payload['description'] ?? '' );
			$permissions = LP_Helper::sanitize_params_submitted( $payload['permissions'] ?? 'read', 'key' );

			$updated = ( new ApiKeysRepository() )->update_key_meta( $key_id, $user_id, $description, $permissions );
			if ( ! $updated ) {
				throw new Exception( __( 'Could not update API key.', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = __( 'API key updated.', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Regenerate consumer key and secret for an existing MCP API key.
	 *
	 * Expected payload fields:
	 * - `key_id` (int): target key ID.
	 *
	 * Success response includes newly generated plaintext credentials once in
	 * `data.key`. Existing credentials become invalid after regeneration.
	 *
	 * @return void
	 */
	public static function mcp_regenerate_api_key() {
		$response = new LP_REST_Response();

		try {
			$payload = self::check_valid();
			$key_id  = absint( $payload['key_id'] ?? 0 );
			if ( $key_id <= 0 ) {
				throw new Exception( __( 'Invalid key ID.', 'learnpress' ) );
			}

			$regenerated = ( new ApiKeysRepository() )->regenerate_key( $key_id );
			if ( ! $regenerated ) {
				throw new Exception( __( 'Could not regenerate API key.', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = __( 'API key regenerated.', 'learnpress' );
			$response->data    = array(
				'key' => $regenerated,
			);
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
