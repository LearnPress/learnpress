<?php
use LearnPress\Helpers\Response;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_REST_Gateway_Webhook_Controller' ) ) {
	/**
	 * REST API endpoint for gateway subscription webhooks.
	 *
	 * @since 4.3.7
	 * @version 1.0.0
	 */
	class LP_REST_Gateway_Webhook_Controller extends LP_Abstract_REST_Controller {
		/**
		 * Configure REST namespace/base for gateway webhook routes.
		 *
		 * @return void
		 */
		public function __construct() {
			$this->namespace = 'lp/v1';
			$this->rest_base = 'gateways';

			parent::__construct();
		}

		/**
		 * Register public subscription webhook route.
		 *
		 * Path pattern: /lp/v1/gateways/{gateway}/subscription-webhook
		 *
		 * @return void
		 */
		public function register_routes() {
			$this->routes = array(
				'(?P<gateway>[a-zA-Z0-9_-]+)/subscription-webhook' => array(
					array(
						'methods'             => WP_REST_Server::ALLMETHODS,
						'callback'            => array( $this, 'listen_subscription_webhook' ),
						'permission_callback' => '__return_true',
					),
				),
			);

			parent::register_routes();
		}

		/**
		 * Dispatch provider subscription webhook to gateway implementation.
		 *
		 * This is the centralized entrypoint used by Stripe/PayPal webhook calls.
		 *
		 * Providers re-send a webhook only when the answer is not 2xx, so the exception
		 * code thrown by the gateway is the HTTP status answered:
		 * - 2xx: event valid but intentionally not processed (duplicate…) → not re-sent.
		 * - 4xx: bad request (signature check failed, unknown gateway…).
		 * - anything else (timeout, DB error…) → 500, so the event is delivered again.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return WP_REST_Response Body is a {@see Response} object.
		 * @since 4.3.7
		 * @version 1.0.3
		 */
		public function listen_subscription_webhook( WP_REST_Request $request ): WP_REST_Response {
			$response    = new Response();
			$status_code = 200;
			$gateway_id  = sanitize_key( (string) $request->get_param( 'gateway' ) );

			try {
				if ( empty( $gateway_id ) ) {
					throw new Exception( __( 'Gateway is required.', 'learnpress' ), 400 );
				}

				$gateway = LP_Gateways::instance()->get_gateway( $gateway_id );
				if ( ! $gateway instanceof LP_Gateway_Abstract ) {
					throw new Exception( __( 'Gateway not found.', 'learnpress' ), 404 );
				}

				if ( ! $gateway->is_enabled() ) {
					throw new Exception( __( 'Gateway is not enable.', 'learnpress' ), 404 );
				}

				/**
				 * @var LP_Gateway_Paypal|LP_Gateway_Stripe $gateway
				 */
				$gateway->capture_subscription_webhook( $request );
				$response->status = Response::STATUS_SUCCESS;
			} catch ( Throwable $e ) {
				$status_code = self::get_http_status_from_throwable( $e );

				if ( $status_code < 300 ) {
					// Event acknowledged without processing.
					$response->status  = Response::STATUS_SUCCESS;
					$response->message = $e->getMessage();
				} else {
					LP_Debug::error_log( $e );
					// Do not expose internal details (cURL, DB…) on a public endpoint.
					$response->message = $status_code >= 500
						? __( 'Webhook could not be processed.', 'learnpress' )
						: $e->getMessage();
				}
			}

			return new WP_REST_Response( $response, $status_code );
		}

		/**
		 * HTTP status for a webhook exception: its code when it is 2xx or 4xx, otherwise 500.
		 *
		 * @param Throwable $e
		 *
		 * @return int
		 * @since 4.4.14
		 * @version 1.0.0
		 */
		public static function get_http_status_from_throwable( Throwable $e ): int {
			$code = $e->getCode(); // Can be a non-numeric string, e.g. PDOException.
			$code = is_numeric( $code ) ? (int) $code : 0;

			$is_2xx = $code >= 200 && $code < 300;
			$is_4xx = $code >= 400 && $code < 500;

			return ( $is_2xx || $is_4xx ) ? $code : 500;
		}
	}
}
