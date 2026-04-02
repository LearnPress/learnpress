<?php
/**
 * REST API endpoint for gateway subscription webhooks.
 *
 * @since 4.3.4
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_REST_Gateway_Webhook_Controller' ) ) {
	class LP_REST_Gateway_Webhook_Controller extends LP_Abstract_REST_Controller {
		/**
		 * Default maximum accepted raw webhook payload size in bytes.
		 *
		 * Why this exists:
		 * - This route is public by design because payment providers call it directly.
		 * - A hard cap helps reduce memory/CPU pressure from oversized request bodies.
		 *
		 * Scope:
		 * - Applied to all subscription webhook requests before gateway-specific verification.
		 *
		 * Override:
		 * - Use filter `learn-press/rest/subscription-webhook/max-payload-bytes`
		 *   to tune per environment/provider needs.
		 *
		 * @var int
		 */
		protected $default_max_payload_bytes = 262144; // 256KB

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
						'methods'             => WP_REST_Server::CREATABLE,
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
		 * It performs route-level guard checks before handing control to the
		 * gateway-specific verifier/normalizer/processor chain.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return WP_REST_Response
		 */
		public function listen_subscription_webhook( WP_REST_Request $request ): WP_REST_Response {
			try {
				$gateway_id = sanitize_key( (string) $request->get_param( 'gateway' ) );
				if ( empty( $gateway_id ) ) {
					throw new Exception( __( 'Gateway is required.', 'learnpress' ), 400 );
				}

				$this->guard_webhook_request( $request, $gateway_id );

				$gateway = LP_Gateways::instance()->get_gateway( $gateway_id );
				if ( ! $gateway || ! $gateway instanceof LP_Gateway_Abstract ) {
					throw new Exception( __( 'Gateway not found.', 'learnpress' ), 404 );
				}

				if ( ! $gateway->is_enabled() || ! $gateway->supports_feature( LP_Gateway_Abstract::FEATURE_SUBSCRIPTION ) ) {
					throw new Exception( __( 'Gateway not found.', 'learnpress' ), 404 );
				}

				$result = $gateway->listen_webhook_subscription( $request );

				$status_code = 200;
				if ( is_array( $result ) && isset( $result['status_code'] ) ) {
					$status_code = absint( $result['status_code'] );
					unset( $result['status_code'] );
				}

				return new WP_REST_Response( $result, $status_code );
			} catch ( Throwable $e ) {
				return $this->build_error_response( $e );
			}
		}

		/**
		 * Apply shared request guards for subscription webhook endpoint.
		 *
		 * Guards:
		 * - Maximum payload size.
		 * - IP-based rate limit.
		 *
		 * @param WP_REST_Request $request
		 * @param string $gateway_id
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function guard_webhook_request( WP_REST_Request $request, string $gateway_id ): void {
			$max_payload_bytes = absint(
				apply_filters(
					'learn-press/rest/subscription-webhook/max-payload-bytes',
					$this->default_max_payload_bytes,
					$gateway_id
				)
			);
			$body              = (string) $request->get_body();
			if ( $max_payload_bytes > 0 && strlen( $body ) > $max_payload_bytes ) {
				throw new Exception( __( 'Webhook payload too large.', 'learnpress' ), 413 );
			}

			$this->check_rate_limit( $gateway_id );
		}

		/**
		 * Enforce transient-backed rate limiting for webhook requests.
		 *
		 * Limits are configurable per gateway via filters:
		 * - learn-press/rest/subscription-webhook/rate-limit-window
		 * - learn-press/rest/subscription-webhook/rate-limit-max-requests
		 *
		 * @param string $gateway_id
		 *
		 * @return void
		 * @throws Exception
		 */
		protected function check_rate_limit( string $gateway_id ): void {
			$window_seconds = absint(
				apply_filters(
					'learn-press/rest/subscription-webhook/rate-limit-window',
					60,
					$gateway_id
				)
			);
			$max_requests   = absint(
				apply_filters(
					'learn-press/rest/subscription-webhook/rate-limit-max-requests',
					60,
					$gateway_id
				)
			);

			if ( $window_seconds <= 0 || $max_requests <= 0 ) {
				return;
			}

			$ip_address = $this->get_request_ip();
			$rate_key   = 'lp_sub_wh_rl_' . md5( $gateway_id . '|' . $ip_address );
			$rate_data  = get_transient( $rate_key );
			$now        = time();

			if ( ! is_array( $rate_data ) || empty( $rate_data['started_at'] ) || empty( $rate_data['count'] ) ) {
				set_transient(
					$rate_key,
					array(
						'started_at' => $now,
						'count'      => 1,
					),
					$window_seconds
				);

				return;
			}

			$started_at = absint( $rate_data['started_at'] );
			if ( $started_at <= 0 || ( $now - $started_at ) >= $window_seconds ) {
				set_transient(
					$rate_key,
					array(
						'started_at' => $now,
						'count'      => 1,
					),
					$window_seconds
				);

				return;
			}

			$count = absint( $rate_data['count'] );
			if ( $count >= $max_requests ) {
				throw new Exception( __( 'Too many webhook requests.', 'learnpress' ), 429 );
			}

			$rate_data['count'] = $count + 1;
			$ttl                = max( 1, $window_seconds - ( $now - $started_at ) );
			set_transient( $rate_key, $rate_data, $ttl );
		}

		/**
		 * Resolve source IP used by route-level rate limiting.
		 *
		 * Default source is REMOTE_ADDR; integrators can override using
		 * learn-press/rest/subscription-webhook/request-ip filter.
		 *
		 * @return string
		 */
		protected function get_request_ip(): string {
			$ip_address = '';
			if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
			}

			$ip_address = (string) apply_filters( 'learn-press/rest/subscription-webhook/request-ip', $ip_address );
			if ( empty( $ip_address ) ) {
				$ip_address = 'unknown';
			}

			return $ip_address;
		}

		/**
		 * Build sanitized REST error response for public webhook endpoint.
		 *
		 * Internal provider error details are logged server-side, while API
		 * response returns a generic/safe message by status class.
		 *
		 * @param Throwable $error
		 *
		 * @return WP_REST_Response
		 */
		protected function build_error_response( Throwable $error ): WP_REST_Response {
			$status = absint( $error->getCode() );
			if ( $status < 100 || $status > 599 ) {
				$status = 400;
			}

			$error_code      = 'lp_subscription_webhook_error';
			$private_message = (string) $error->getMessage();
			$public_message  = __( 'Invalid webhook request.', 'learnpress' );

			if ( 429 === $status ) {
				$public_message = __( 'Too many webhook requests.', 'learnpress' );
			} elseif ( 413 === $status ) {
				$public_message = __( 'Webhook payload too large.', 'learnpress' );
			}

			error_log(
				sprintf(
					'LP subscription webhook error [%s]: %s',
					$error_code,
					$private_message
				)
			);

			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'code'    => $error_code,
					'message' => $public_message,
				),
				$status
			);
		}
	}
}
