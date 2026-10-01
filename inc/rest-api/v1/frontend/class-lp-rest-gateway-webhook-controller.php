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
		 * @param WP_REST_Request $request
		 *
		 * @return Response
		 * @since 4.3.7
		 * @version 1.0.2
		 */
		public function listen_subscription_webhook( WP_REST_Request $request ): Response {
			$response   = new Response();
			$gateway_id = sanitize_key( (string) $request->get_param( 'gateway' ) );

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

				//LP_Debug::log_to_comment( 'Webhook payload: ' . json_encode( $request->get_body(), JSON_UNESCAPED_UNICODE ) );

				/**
				 * @var LP_Gateway_Paypal|LP_Gateway_Stripe $gateway
				 */
				$gateway->capture_subscription_webhook( $request );
				$response->status = Response::STATUS_SUCCESS;
			} catch ( Throwable $e ) {
				LP_Debug::log_to_comment( 'Webhook error: ' . $gateway_id . ' - ' . $e->getMessage() );
				LP_Debug::error_log( $e );
				$response->message = $e->getMessage();
			}

			return $response;
		}
	}
}
