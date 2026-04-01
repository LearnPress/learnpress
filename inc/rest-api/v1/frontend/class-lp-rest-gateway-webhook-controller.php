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

		public function __construct() {
			$this->namespace = 'lp/v1';
			$this->rest_base = 'gateways';

			parent::__construct();
		}

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
		 * @param WP_REST_Request $request
		 *
		 * @return WP_REST_Response
		 */
		public function listen_subscription_webhook( WP_REST_Request $request ): WP_REST_Response {
			$gateway_id = sanitize_key( (string) $request->get_param( 'gateway' ) );
			if ( empty( $gateway_id ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => __( 'Gateway is required.', 'learnpress' ),
					),
					400
				);
			}

			$guard_result = $this->guard_webhook_request( $request, $gateway_id );
			if ( is_wp_error( $guard_result ) ) {
				return $this->build_error_response( $guard_result );
			}

			$gateway = LP_Gateways::instance()->get_gateway( $gateway_id );
			if ( ! $gateway || ! $gateway instanceof LP_Gateway_Abstract ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => __( 'Gateway not found.', 'learnpress' ),
					),
					404
				);
			}

			if ( ! $gateway->is_enabled() || ! $gateway->supports_feature( LP_Gateway_Abstract::FEATURE_SUBSCRIPTION ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'error',
						'message' => __( 'Gateway not found.', 'learnpress' ),
					),
					404
				);
			}

			$result = $gateway->listen_webhook_subscription( $request );
			if ( is_wp_error( $result ) ) {
				return $this->build_error_response( $result );
			}

			$status_code = 200;
			if ( is_array( $result ) && isset( $result['status_code'] ) ) {
				$status_code = absint( $result['status_code'] );
				unset( $result['status_code'] );
			}

			return new WP_REST_Response( $result, $status_code );
		}

		/**
		 * @param WP_REST_Request $request
		 * @param string $gateway_id
		 *
		 * @return true|WP_Error
		 */
		protected function guard_webhook_request( WP_REST_Request $request, string $gateway_id ) {
			$max_payload_bytes = absint(
				apply_filters(
					'learn-press/rest/subscription-webhook/max-payload-bytes',
					$this->default_max_payload_bytes,
					$gateway_id
				)
			);
			$body              = (string) $request->get_body();
			if ( $max_payload_bytes > 0 && strlen( $body ) > $max_payload_bytes ) {
				return new WP_Error(
					'lp_subscription_webhook_payload_too_large',
					__( 'Webhook payload too large.', 'learnpress' ),
					array( 'status' => 413 )
				);
			}

			$rate_limit_result = $this->check_rate_limit( $gateway_id );
			if ( is_wp_error( $rate_limit_result ) ) {
				return $rate_limit_result;
			}

			return true;
		}

		/**
		 * @param string $gateway_id
		 *
		 * @return true|WP_Error
		 */
		protected function check_rate_limit( string $gateway_id ) {
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
				return true;
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

				return true;
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

				return true;
			}

			$count = absint( $rate_data['count'] );
			if ( $count >= $max_requests ) {
				return new WP_Error(
					'lp_subscription_webhook_rate_limited',
					__( 'Too many webhook requests.', 'learnpress' ),
					array( 'status' => 429 )
				);
			}

			$rate_data['count'] = $count + 1;
			$ttl                = max( 1, $window_seconds - ( $now - $started_at ) );
			set_transient( $rate_key, $rate_data, $ttl );

			return true;
		}

		/**
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
		 * @param WP_Error $error
		 *
		 * @return WP_REST_Response
		 */
		protected function build_error_response( WP_Error $error ): WP_REST_Response {
			$status = absint( $error->get_error_data( 'status' ) );
			if ( $status <= 0 ) {
				$status = 400;
			}

			$error_code      = (string) $error->get_error_code();
			$private_message = (string) $error->get_error_message();
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
