<?php

namespace LearnPress\Ajax\Webhook;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Models\Webhook\WebhookModel;
use LearnPress\Webhook\WebhookEvents;
use LP_Helper;
use LP_Settings;
use LP_REST_Response;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Handle webhook CRUD requests through lp-load-ajax transport.
 */
class WebhooksAjax extends AbstractAjax {
	/**
	 * @var string
	 */
	protected static $required_capability = 'manage_options';

	/**
	 * Validate current request and decode JSON payload.
	 *
	 * @return array<string, mixed>
	 * @throws Exception
	 */
	public static function check_valid(): array {
		if ( ! current_user_can( self::$required_capability ) ) {
			throw new Exception( __( 'You are not allowed to manage webhooks.', 'learnpress' ) );
		}

		if ( 'yes' !== LP_Settings::get_option( 'enable_webhook_integration', 'no' ) ) {
			throw new Exception( __( 'Webhook integration is disabled.', 'learnpress' ) );
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
	 * Create a webhook.
	 *
	 * @return void
	 */
	public static function create_webhook() {
		$response = new LP_REST_Response();

		try {
			$data                  = self::normalize_webhook_payload( self::check_valid() );
			$webhook               = new WebhookModel();
			$webhook->user_id      = get_current_user_id();
			$webhook->name         = $data['name'];
			$webhook->delivery_url = $data['delivery_url'];
			$webhook->status       = $data['status'];
			$webhook->events       = $data['events'];
			$webhook->secret       = '' !== $data['secret'] ? $data['secret'] : WebhookModel::generate_secret();
			$webhook->save();

			$response->status  = 'success';
			$response->message = __( 'Webhook created.', 'learnpress' );
			$response->data    = array(
				'webhook' => $webhook->to_array( true ),
			);
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update a webhook.
	 *
	 * @return void
	 */
	public static function update_webhook() {
		$response = new LP_REST_Response();

		try {
			$payload    = self::check_valid();
			$webhook_id = absint( $payload['webhook_id'] ?? 0 );
			$webhook    = WebhookModel::find( $webhook_id );
			if ( ! $webhook ) {
				throw new Exception( __( 'Webhook not found.', 'learnpress' ) );
			}

			$data                  = self::normalize_webhook_payload( $payload );
			$webhook->name         = $data['name'];
			$webhook->delivery_url = $data['delivery_url'];
			$webhook->status       = $data['status'];
			$webhook->events       = $data['events'];
			if ( '' !== $data['secret'] ) {
				$webhook->secret = $data['secret'];
			}
			$webhook->save();

			$response->status  = 'success';
			$response->message = __( 'Webhook updated.', 'learnpress' );
			$response->data    = array(
				'webhook' => $webhook->to_array(),
			);
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Delete a webhook.
	 *
	 * @return void
	 */
	public static function delete_webhook() {
		$response = new LP_REST_Response();

		try {
			$payload    = self::check_valid();
			$webhook_id = absint( $payload['webhook_id'] ?? 0 );
			$webhook    = WebhookModel::find( $webhook_id );
			if ( ! $webhook || ! $webhook->delete() ) {
				throw new Exception( __( 'Could not delete webhook.', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = __( 'Webhook deleted.', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Regenerate a webhook secret and return it once.
	 *
	 * @return void
	 */
	public static function regenerate_webhook_secret() {
		$response = new LP_REST_Response();

		try {
			$payload    = self::check_valid();
			$webhook_id = absint( $payload['webhook_id'] ?? 0 );
			$webhook    = WebhookModel::find( $webhook_id );
			if ( ! $webhook ) {
				throw new Exception( __( 'Webhook not found.', 'learnpress' ) );
			}

			$secret = $webhook->regenerate_secret();

			$response->status  = 'success';
			$response->message = __( 'Webhook secret regenerated.', 'learnpress' );
			$response->data    = array(
				'secret' => $secret,
			);
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Normalize and validate mutable webhook fields.
	 *
	 * @param array<string, mixed> $payload Raw request payload.
	 *
	 * @return array<string, mixed>
	 * @throws Exception
	 */
	protected static function normalize_webhook_payload( array $payload ): array {
		$name         = sanitize_text_field( (string) ( $payload['name'] ?? '' ) );
		$delivery_url = esc_url_raw( (string) ( $payload['delivery_url'] ?? '' ) );
		$status       = sanitize_key( (string) ( $payload['status'] ?? '' ) );
		$events       = is_array( $payload['events'] ?? null ) ? WebhookEvents::sanitize( $payload['events'] ) : array();
		$secret       = (string) ( $payload['secret'] ?? '' );
		$name_length  = function_exists( 'mb_strlen' ) ? mb_strlen( $name ) : strlen( $name );
		$scheme       = wp_parse_url( $delivery_url, PHP_URL_SCHEME );

		if ( '' === $name ) {
			throw new Exception( __( 'Webhook name is required.', 'learnpress' ) );
		}

		if ( $name_length > 200 ) {
			throw new Exception( __( 'Webhook name must not exceed 200 characters.', 'learnpress' ) );
		}

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || ! wp_http_validate_url( $delivery_url ) ) {
			throw new Exception( __( 'Webhook callback URL is invalid.', 'learnpress' ) );
		}

		if ( ! in_array( $status, array( WebhookModel::STATUS_ACTIVE, WebhookModel::STATUS_PAUSED ), true ) ) {
			throw new Exception( __( 'Webhook status is invalid.', 'learnpress' ) );
		}

		if ( empty( $events ) ) {
			throw new Exception( __( 'Select at least one webhook event.', 'learnpress' ) );
		}

		if ( '' !== $secret && ( '' === trim( $secret ) || preg_match( '/[\r\n]/', $secret ) || strlen( $secret ) > 255 ) ) {
			throw new Exception( __( 'Webhook secret is invalid.', 'learnpress' ) );
		}

		return array(
			'name'         => $name,
			'delivery_url' => $delivery_url,
			'status'       => $status,
			'events'       => $events,
			'secret'       => $secret,
		);
	}
}
