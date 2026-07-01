<?php
namespace LearnPress\Ajax\Gateway;

use LearnPress\Ajax\AbstractAjax;
use LP_Gateway_Paypal;
use LP_REST_Response;
use LP_Settings;
use Throwable;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Handle PayPal subscription webhook creation requests through lp-load-ajax transport.
 */
class PaypalWebhookAjax extends AbstractAjax {
	/**
	 * @var string
	 */
	protected static $required_capability = 'manage_options';

	/**
	 * Create (or reuse) the PayPal subscription webhook and persist its id.
	 *
	 * Persists the returned webhook id into the `paypal` gateway settings group
	 * so it is available immediately without a full settings-form save.
	 *
	 * @return void
	 */
	public static function paypal_create_subscription_webhook() {
		$response = new LP_REST_Response();

		try {
			if ( ! current_user_can( self::$required_capability ) ) {
				throw new Exception( __( 'You are not allowed to manage PayPal settings.', 'learnpress' ) );
			}

			$webhook_id = LP_Gateway_Paypal::instance()->create_subscription_webhook();

			$paypal_settings = LP_Settings::instance()->get_group( 'paypal' );
			$paypal_settings->set( 'subscription_webhook_id', $webhook_id );
			LP_Settings::update_option( 'paypal', $paypal_settings->get() );

			$response->status  = 'success';
			$response->message = __( 'PayPal webhook created.', 'learnpress' );
			$response->data    = array(
				'webhook_id' => $webhook_id,
			);
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Check whether the saved PayPal subscription webhook is still alive.
	 *
	 * Reports whether PayPal still has the webhook registered, and flags any
	 * drift between what is registered and what LearnPress currently expects
	 * (listener URL, subscribed event types).
	 *
	 * @return void
	 */
	public static function paypal_check_subscription_webhook_status() {
		$response = new LP_REST_Response();

		try {
			if ( ! current_user_can( self::$required_capability ) ) {
				throw new Exception( __( 'You are not allowed to manage PayPal settings.', 'learnpress' ) );
			}

			$status = LP_Gateway_Paypal::instance()->check_subscription_webhook_status();

			$notes = array();
			if ( ! $status['url_matches'] ) {
				$notes[] = __( 'The webhook URL registered on PayPal does not match this site\'s current webhook URL.', 'learnpress' );
			}
			if ( ! empty( $status['missing_event_types'] ) ) {
				$notes[] = sprintf(
					/* translators: %s: comma-separated list of PayPal event type names. */
					__( 'Missing event types: %s.', 'learnpress' ),
					implode( ', ', $status['missing_event_types'] )
				);
			}

			$message = __( 'Webhook is active on PayPal.', 'learnpress' );
			if ( ! empty( $notes ) ) {
				$message .= ' ' . implode( ' ', $notes );
			}

			$response->status  = 'success';
			$response->message = $message;
			$response->data    = $status;
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
