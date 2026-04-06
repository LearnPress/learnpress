<?php
/**
 * Class Paypal Payment gateway.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @since   3.0.0
 * @version 3.0.3
 */

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Gateway_Paypal' ) ) {
	/**
	 * Class LP_Gateway_Paypal.
	 */
	class LP_Gateway_Paypal extends LP_Gateway_Abstract {
		use Singleton;

		/**
		 * @var string
		 */
		public $id = 'paypal';
		/**
		 * @var null|string
		 */
		protected $paypal_live_url = 'https://www.paypal.com/';
		/**
		 * @var null|string
		 */
		protected $paypal_payment_live_url = 'https://www.paypal.com/cgi-bin/webscr';
		/**
		 * @var null|string
		 */
		protected $paypal_sandbox_url = 'https://www.sandbox.paypal.com/';
		/**
		 * @var null|string
		 */
		protected $paypal_payment_sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		/**
		 * @var string
		 */
		protected $api_sandbox_url = 'https://api-m.sandbox.paypal.com/';
		/**
		 * @var string
		 */
		protected $api_live_url = 'https://api-m.paypal.com/';
		/**
		 * @var string|null
		 */
		protected $api_url = null;
		/**
		 * @var null
		 */
		protected $paypal_url = null;
		/**
		 * @var null
		 */
		protected $paypal_payment_url = null;
		/**
		 * @var null
		 */
		protected $paypal_email = '';
		/**
		 * @var null
		 */
		protected $settings = null;
		/**
		 * @var null
		 */
		protected $client_id = null;
		/**
		 * @var null
		 */
		protected $client_secret = null;
		/**
		 * @var string
		 */
		protected $subscription_webhook_id = '';

		/**
		 * LP_Gateway_Paypal constructor.
		 */
		public function __construct() {
			$this->method_title       = esc_html__( 'PayPal', 'learnpress' );
			$this->method_description = esc_html__( 'Make a payment via Paypal.', 'learnpress' );
			$this->icon               = LP_PLUGIN_URL . 'assets/images/paypal-logo-preview.png';

			$this->title       = esc_html__( 'PayPal', 'learnpress' );
			$this->description = esc_html__( 'Pay with PayPal', 'learnpress' );

			parent::__construct();

			$this->init();
		}

		/**
		 * Init.
		 */
		public function init() {
			if ( $this->is_enabled() ) {
				if ( $this->settings->get( 'paypal_sandbox', 'no' ) === 'no' ) {
					$this->paypal_url         = $this->paypal_live_url;
					$this->paypal_payment_url = $this->paypal_payment_live_url;
					$this->paypal_email       = $this->settings->get( 'paypal_email' );
					$this->api_url            = $this->api_live_url; //use for PayPal rest api
				} else {
					$this->paypal_url         = $this->paypal_sandbox_url;
					$this->paypal_payment_url = $this->paypal_payment_sandbox_url;
					$this->paypal_email       = $this->settings->get( 'paypal_sandbox_email' );
					$this->api_url            = $this->api_sandbox_url; //use for PayPal rest api
				}

				// Use PayPal rest api
				$this->client_id               = $this->settings->get( 'app_client_id' );
				$this->client_secret           = $this->settings->get( 'app_client_secret' );
				$this->subscription_webhook_id = (string) $this->settings->get( 'subscription_webhook_id', '' );
			} else {
				return;
			}

			$this->check_webhook_callback();
		}

		/**
		 * Check whether PayPal subscription mode is enabled in gateway settings.
		 *
		 * @return bool
		 */
		public function is_subscription_enabled(): bool {
			return $this->settings->get( 'enable_subscriptions', 'no' ) === 'yes';
		}

		/**
		 * Listen callback, webhook form PayPal.
		 */
		public function check_webhook_callback() {
			if ( ! isset( $_GET['paypay_express_checkout'] ) ) {
				return;
			}

			$paypal_order_id = LP_Request::get_param( 'token' );
			if ( empty( $paypal_order_id ) ) {
				return;
			}

			$this->capture_payment_for_order( $paypal_order_id );
		}

		/**
		 * https://developer.paypal.com/api/nvp-soap/ipn/IPNImplementation/#link-ipnlistenerrequestresponseflow
		 * Check validate IPN.
		 *
		 * @return bool
		 */
		public function validate_ipn(): bool {
			$validate_ipn  = array( 'cmd' => '_notify-validate' );
			$validate_ipn += wp_unslash( $_POST );

			$params = array(
				'body'    => $validate_ipn,
				'timeout' => 60,
			);

			// Post back to get a response
			$response = wp_remote_post( $this->paypal_payment_url, $params );

			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				$body = wp_remote_retrieve_body( $response );
				if ( 'VERIFIED' === $body ) {
					return true;
				}
			} else {
				error_log( 'Error code paypal validate ipn: ' . $response['response']['code'] );
				error_log( 'Error code paypal validate ipn: ' . $response->get_error_message() );
			}

			return false;
		}

		/**
		 * Handle payment.
		 *
		 * @param int $order_id
		 *
		 * @return array
		 * @throws Exception
		 */
		public function process_payment( $order_id = 0 ): array {
			$order = new LP_Order( $order_id );

			$subscription_data = $this->resolve_subscription_payment_data( $order );

			if ( ! empty( $subscription_data ) ) {
				$subscription_res = $this->pay_subscription( $subscription_data );

				update_post_meta( $order_id, self::META_SUBSCRIPTION_STATUS, 'pending' );
				if ( ! empty( $subscription_res['subscription_id'] ) ) {
					update_post_meta( $order_id, self::META_SUBSCRIPTION_ID, sanitize_text_field( (string) $subscription_res['subscription_id'] ) );
				}

				return array(
					'result'   => 'success',
					'redirect' => esc_url_raw( (string) ( $subscription_res['redirect_url'] ?? '' ) ),
				);
			}

			$data_token         = $this->get_access_token();
			$paypal_payment_url = $this->create_payment_url( $order, $data_token );

			$result['result']   = 'success';
			$result['redirect'] = $paypal_payment_url;

			return $result;
		}

		/**
		 * Prepare args to send to PayPal
		 *
		 * @param LP_Order $order
		 *
		 * @return array
		 * @since 3.0.0
		 * @version 1.0.1
		 */
		public function get_paypal_args( LP_Order $order ): array {
			$checkout   = LearnPress::instance()->checkout();
			$custom     = array(
				'order_id'       => $order->get_id(),
				'order_key'      => $order->get_order_key(),
				'checkout_email' => $checkout->get_checkout_email(),
			);
			$lp_cart    = LearnPress::instance()->get_cart();
			$cart_total = $lp_cart->calculate_totals();
			$item_arg   = [
				'item_name_1' => $order->get_order_number(),
				'quantity_1'  => 1,
				'amount_1'    => $cart_total->total,
			];
			$args       = array_merge(
				array(
					'cmd'           => '_cart',
					'business'      => $this->paypal_email,
					'no_note'       => 1,
					'currency_code' => learn_press_get_currency(),
					'charset'       => 'utf-8',
					'rm'            => is_ssl() ? 2 : 1,
					'upload'        => 1,
					'return'        => esc_url_raw( $this->get_return_url( $order ) ),
					'cancel_return' => esc_url_raw( learn_press_is_enable_cart() ? learn_press_get_page_link( 'cart' ) : get_home_url() ),
					'bn'            => 'LearnPress_Cart',
					'custom'        => json_encode( $custom ),
					'notify_url'    => get_home_url() . '/?paypal_notify=1',
				),
				$item_arg
			);

			return apply_filters( 'learn-press/paypal/args', $args );
		}

		/**
		 * Get access token from PayPal
		 *
		 * @throws Exception
		 * @since 4.2.4
		 * @version 1.0.0
		 */
		public function get_access_token() {
			$client_id     = $this->client_id;
			$client_secret = $this->client_secret;

			if ( empty( $client_id ) ) {
				throw new Exception( __( 'Paypal Client id is required.', 'learnpress' ) );
			}

			if ( ! $client_secret ) {
				throw new Exception( __( 'Paypal Client secret is required', 'learnpress' ) );
			}

			$params   = [ 'grant_type' => 'client_credentials' ];
			$response = wp_remote_post(
				$this->api_url . 'v1/oauth2/token',
				[
					'body'    => $params,
					'headers' => [
						'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
					],
					'timeout' => 60,
				]
			);

			$data_token_str = wp_remote_retrieve_body( $response );
			$data_token     = LP_Helper::json_decode( $data_token_str );
			if ( isset( $data_token->error ) ) {
				throw new Exception( $data_token->error_description );
			}

			LP_Settings::update_option( 'paypal_token', $data_token_str );

			return $data_token;
		}

		/**
		 * create args to create PayPal order
		 *
		 * @param LP_Order $order
		 *
		 * @return array
		 * @since 4.2.4
		 * @version 1.0.2
		 */
		public function get_order_args( LP_Order $order ): array {
			$lp_cart    = LearnPress::instance()->get_cart();
			$cart_total = $lp_cart->calculate_totals();
			$order_id   = $order->get_id();
			$return_url = esc_url_raw(
				add_query_arg( 'paypay_express_checkout', 1, $this->get_return_url( $order ) )
			);
			$cancel_url = esc_url_raw(
				learn_press_is_enable_cart() ? learn_press_get_page_link( 'cart' ) : get_home_url()
			);
			$brand_name = ! empty( get_bloginfo() ) ? get_bloginfo() : 'LearnPress';
			$data       = [
				'intent'         => 'CAPTURE',
				'purchase_units' => [
					[
						'amount'    => [
							'currency_code' => learn_press_get_currency(),
							'value'         => strval( round( $cart_total->total, 2 ) ),
						],
						'custom_id' => $order_id,
					],
				],
				'payment_source' => [
					'paypal' => [
						'experience_context' => [
							'payment_method_preference' => 'UNRESTRICTED',
							'brand_name'                => $brand_name,
							'landing_page'              => 'LOGIN',
							'user_action'               => 'PAY_NOW',
							'return_url'                => $return_url,
							'cancel_url'                => $cancel_url,
						],
					],
				],
			];

			return apply_filters( 'learn-press/paypal-rest/args', $data );
		}

		/**
		 * Create Order PayPal and get checkout url
		 *
		 * @param LP_Order $order
		 * @param object $data_token { scope, access_token, token_type, app_id, expires_in, nonce }
		 *
		 * @return string
		 * @throws Exception
		 * @since 4.2.4
		 * @version 1.0.0
		 */
		public function create_payment_url( LP_Order $order, $data_token ): string {
			$checkout_url = '';
			$params       = $this->get_order_args( $order );

			if ( ! isset( $data_token->access_token ) || ! isset( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$response = wp_remote_post(
				$this->api_url . 'v2/checkout/orders',
				[
					'body'    => json_encode( $params ),
					'headers' => [
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					],
					'timeout' => 60,
				]
			);

			$result = LP_Helper::json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $result->error ) ) {
				throw new Exception( $result->error_description );
			}

			/**
			 * Error response
			 *
			 * https://developer.paypal.com/api/rest/reference/orders/v2/errors/
			 */
			if ( isset( $result->name ) && isset( $result->details[0] ) ) {
				throw new Exception( $result->details[0]->description );
			}

			if ( empty( $result->id ) ) {
				throw new Exception( __( 'Invalid Paypal checkout', 'learnpress' ) );
			}

			foreach ( $result->links as $link ) {
				if ( $link->rel === 'payer-action' ) {
					$checkout_url = $link->href;
					break;
				}
			}

			if ( empty( $checkout_url ) ) {
				throw new Exception( __( 'Invalid Paypal checkout url', 'learnpress' ) );
			}

			return $checkout_url;
		}

		/**
		 * Capture payment for order
		 *
		 * @param string $paypal_order_id
		 * https://developer.paypal.com/docs/api/orders/v2/#orders_capture
		 *
		 * @return bool True when capture is completed and order status is updated.
		 * @since 4.2.4
		 * @version 1.0.0
		 */
		public function capture_payment_for_order( string $paypal_order_id ): bool {
			$data_token_str = LP_Settings::get_option( 'paypal_token' );
			$data_token     = json_decode( $data_token_str );
			if ( ! isset( $data_token->access_token ) || ! isset( $data_token->token_type ) ) {
				return false;
			}

			$response = wp_remote_post(
				$this->api_url . 'v2/checkout/orders/' . $paypal_order_id . '/capture',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
					),
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response_code = absint( $response['response']['code'] ?? 0 );
			if ( 201 !== $response_code ) {
				return false;
			}

			$body        = wp_remote_retrieve_body( $response );
			$transaction = LP_Helper::json_decode( $body );
			if ( empty( $transaction ) || ! is_object( $transaction ) || ( $transaction->status ?? '' ) !== 'COMPLETED' ) {
				return false;
			}

			$order_id = absint( $transaction->purchase_units[0]->payments->captures[0]->custom_id ?? 0 );
			if ( $order_id <= 0 ) {
				return false;
			}

			$lp_order = learn_press_get_order( $order_id );
			if ( $lp_order instanceof LP_Order ) {
				$lp_order->update_status( LP_ORDER_COMPLETED );
				return true;
			}

			return false;
		}

		/**
		 * Validate subscription payload for PayPal checkout.
		 *
		 * This override keeps gateway-specific assumptions close to PayPal flow
		 * while still reusing the shared contract validation from abstract gateway.
		 *
		 * @param array $data
		 *
		 * @return array
		 * @throws Exception
		 */
		protected function validate_subscription_payload( array $data ): array {
			$data = parent::validate_subscription_payload( $data );

			// PayPal subscription API expects a plan id string and positive quantity.
			$data['price_id'] = (string) $data['price_id'];
			$data['quantity'] = max( 1, absint( $data['quantity'] ) );

			return $data;
		}

		/**
		 * Convert generic interval to PayPal interval unit.
		 *
		 * @param string $interval
		 *
		 * @return string
		 */
		protected function map_paypal_interval_unit( string $interval ): string {
			$map = array(
				'day'   => 'DAY',
				'week'  => 'WEEK',
				'month' => 'MONTH',
				'year'  => 'YEAR',
			);

			return $map[ $interval ] ?? 'MONTH';
		}

		/**
		 * Create PayPal billing plan resource (catalog product + billing plan).
		 *
		 * Returned `plan` object contains PayPal `plan_id` (`$plan->id`) for later
		 * subscription checkout via `pay_subscription()`.
		 *
		 * @param array $data
		 *
		 * @return array
		 * @throws Exception
		 */
		public function create_plan( array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$data = $this->validate_data_plan_payload( $data );

			// PayPal-specific optional keys are normalized at gateway level.
			$description = sanitize_text_field( wp_unslash( (string) ( $data['description'] ?? '' ) ) );
			$trial_days  = absint( $data['trial_days'] ?? 0 );

			// PayPal billing plans require a plan name.
			if ( empty( $data['name'] ) ) {
				throw new Exception( __( 'Missing subscription plan name.', 'learnpress' ) );
			}

			$data_token = $this->get_access_token();
			if ( empty( $data_token->access_token ) || empty( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$product_id   = (string) $data['product_id'];
			$product_body = (object) array( 'id' => $product_id );
			if ( empty( $product_id ) ) {
				$product_payload = array(
					'name' => (string) $data['name'],
					'type' => 'SERVICE',
				);
				if ( ! empty( $description ) ) {
					$product_payload['description'] = (string) $description;
				}

				$product_response = wp_remote_post(
					$this->api_url . 'v1/catalogs/products',
					array(
						'body'    => wp_json_encode( $product_payload ),
						'headers' => array(
							'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
							'Content-Type'  => 'application/json',
						),
						'timeout' => 60,
					)
				);
				if ( is_wp_error( $product_response ) ) {
					throw new Exception( $product_response->get_error_message() );
				}

				$product_body = LP_Helper::json_decode( wp_remote_retrieve_body( $product_response ) );
				if ( empty( $product_body->id ) ) {
					$error_message = __( 'Invalid PayPal product response.', 'learnpress' );
					if ( ! empty( $product_body->message ) ) {
						$error_message = (string) $product_body->message;
					}

					throw new Exception( $error_message );
				}

				$product_id = (string) $product_body->id;
			}

			$currency_code  = (string) $data['currency'];
			$billing_cycles = array();
			$sequence       = 1;
			if ( $trial_days > 0 ) {
				$billing_cycles[] = array(
					'frequency'      => array(
						'interval_unit'  => 'DAY',
						'interval_count' => $trial_days,
					),
					'tenure_type'    => 'TRIAL',
					'sequence'       => 1,
					'total_cycles'   => 1,
					'pricing_scheme' => array(
						'fixed_price' => array(
							'value'         => '0',
							'currency_code' => $currency_code,
						),
					),
				);
				$sequence         = 2;
			}

			$billing_cycles[] = array(
				'frequency'      => array(
					'interval_unit'  => $this->map_paypal_interval_unit( (string) $data['interval'] ),
					'interval_count' => max( 1, absint( $data['interval_count'] ) ),
				),
				'tenure_type'    => 'REGULAR',
				'sequence'       => $sequence,
				'total_cycles'   => 0,
				'pricing_scheme' => array(
					'fixed_price' => array(
						'value'         => number_format( (float) $data['amount'], 2, '.', '' ),
						'currency_code' => $currency_code,
					),
				),
			);

			$plan_payload = array(
				'product_id'          => $product_id,
				'name'                => (string) $data['name'],
				'status'              => 'ACTIVE',
				'billing_cycles'      => $billing_cycles,
				'payment_preferences' => array(
					'auto_bill_outstanding'     => true,
					'setup_fee_failure_action'  => 'CONTINUE',
					'payment_failure_threshold' => 3,
				),
			);
			if ( ! empty( $description ) ) {
				$plan_payload['description'] = (string) $description;
			}

			$plan_response = wp_remote_post(
				$this->api_url . 'v1/billing/plans',
				array(
					'body'    => wp_json_encode( $plan_payload ),
					'headers' => array(
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 60,
				)
			);
			if ( is_wp_error( $plan_response ) ) {
				throw new Exception( $plan_response->get_error_message() );
			}

			$plan_body = LP_Helper::json_decode( wp_remote_retrieve_body( $plan_response ) );
			if ( empty( $plan_body->id ) ) {
				$error_message = __( 'Invalid PayPal plan response.', 'learnpress' );
				if ( ! empty( $plan_body->message ) ) {
					$error_message = (string) $plan_body->message;
				}

				throw new Exception( $error_message );
			}

			return array(
				'status'  => 'success',
				'product' => $product_body,
				'plan'    => $plan_body,
				'message' => __( 'PayPal subscription plan created.', 'learnpress' ),
			);
		}

		/**
		 * Create PayPal subscription checkout and return redirect payload.
		 *
		 * Request is sent to PayPal Billing Subscriptions API using:
		 * - plan_id = subscription price/plan id configured in PayPal.
		 * - custom_id = LearnPress parent order id for reconciliation.
		 * - return/cancel URLs = checkout callbacks.
		 *
		 * @param array $data Normalized payload from get_subscription_context().
		 *
		 * @return array{
		 *     status:string,
		 *     redirect_url:string,
		 *     provider_reference:string,
		 *     subscription_id:string,
		 *     message:string
		 * }
		 * @throws Exception
		 */
		public function pay_subscription( array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$data = $this->validate_subscription_payload( $data );

			$data_token = $this->get_access_token();
			if ( empty( $data_token->access_token ) || empty( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$metadata = array_map(
				function ( $value ) {
					if ( is_scalar( $value ) || is_null( $value ) ) {
						return (string) $value;
					}
					return wp_json_encode( $value );
				},
				(array) $data['metadata']
			);
			$order_id = absint( $metadata['lp_order_id'] ?? 0 );

			$request_body = array(
				'plan_id'             => (string) $data['price_id'],
				'quantity'            => (string) max( 1, absint( $data['quantity'] ) ),
				'custom_id'           => ! empty( $order_id ) ? (string) $order_id : '',
				'application_context' => array(
					'brand_name' => ! empty( get_bloginfo() ) ? get_bloginfo() : 'LearnPress',
					'return_url' => esc_url_raw( (string) $data['success_url'] ),
					'cancel_url' => esc_url_raw( (string) $data['cancel_url'] ),
				),
			);

			$response = wp_remote_post(
				$this->api_url . 'v1/billing/subscriptions',
				array(
					'body'    => wp_json_encode( $request_body ),
					'headers' => array(
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$body = wp_remote_retrieve_body( $response );
			$data = LP_Helper::json_decode( $body );

			if ( empty( $data->id ) ) {
				$error_message = __( 'Invalid PayPal subscription response.', 'learnpress' );
				if ( ! empty( $data->message ) ) {
					$error_message = $data->message;
				}
				throw new Exception( $error_message );
			}

			$approve_url = '';
			if ( ! empty( $data->links ) && is_array( $data->links ) ) {
				foreach ( $data->links as $link ) {
					if ( ! empty( $link->rel ) && 'approve' === $link->rel ) {
						$approve_url = $link->href;
						break;
					}
				}
			}

			if ( empty( $approve_url ) ) {
				throw new Exception( __( 'Invalid PayPal subscription approve URL.', 'learnpress' ) );
			}

			return array(
				'status'             => 'success',
				'redirect_url'       => esc_url_raw( $approve_url ),
				'provider_reference' => (string) $data->id,
				'subscription_id'    => (string) $data->id,
				'message'            => __( 'Redirecting to PayPal subscription checkout.', 'learnpress' ),
			);
		}

		/**
		 * Reverse verify PayPal subscription webhook before processing.
		 *
		 * Verification uses PayPal /verify-webhook-signature endpoint and requires:
		 * - configured webhook id from merchant settings,
		 * - required PayPal transmission headers,
		 * - raw JSON payload as webhook_event.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return array Verified webhook payload array on success.
		 * @throws Exception
		 */
		public function verify_subscription_webhook( WP_REST_Request $request ): array {
			if ( empty( $this->subscription_webhook_id ) ) {
				throw new Exception( __( 'PayPal subscription webhook id is missing.', 'learnpress' ), 500 );
			}

			$payload = json_decode( $request->get_body(), true );
			if ( empty( $payload ) || ! is_array( $payload ) ) {
				throw new Exception( __( 'Invalid PayPal webhook payload.', 'learnpress' ), 400 );
			}

			$required_headers = array(
				'paypal-auth-algo',
				'paypal-cert-url',
				'paypal-transmission-id',
				'paypal-transmission-sig',
				'paypal-transmission-time',
			);
			$headers_map      = array();
			foreach ( $required_headers as $required_header ) {
				$header_value = sanitize_text_field( (string) $request->get_header( $required_header ) );
				if ( '' === $header_value ) {
					throw new Exception( __( 'Invalid PayPal webhook headers.', 'learnpress' ), 400 );
				}
				$headers_map[ $required_header ] = $header_value;
			}

			$cert_url  = esc_url_raw( $headers_map['paypal-cert-url'] );
			$cert_host = wp_parse_url( $cert_url, PHP_URL_HOST );
			if (
				empty( $cert_url ) ||
				stripos( $cert_url, 'https://' ) !== 0 ||
				empty( $cert_host ) ||
				! preg_match( '/(^|\.)paypal\.com$/i', (string) $cert_host )
			) {
				throw new Exception( __( 'Invalid PayPal webhook certificate URL.', 'learnpress' ), 400 );
			}

			$data_token = $this->get_access_token();
			if ( empty( $data_token->access_token ) || empty( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$verify_payload = array(
				'auth_algo'         => $headers_map['paypal-auth-algo'],
				'cert_url'          => $cert_url,
				'transmission_id'   => $headers_map['paypal-transmission-id'],
				'transmission_sig'  => $headers_map['paypal-transmission-sig'],
				'transmission_time' => $headers_map['paypal-transmission-time'],
				'webhook_id'        => (string) $this->subscription_webhook_id,
				'webhook_event'     => $payload,
			);

			$response = wp_remote_post(
				$this->api_url . 'v1/notifications/verify-webhook-signature',
				array(
					'body'    => wp_json_encode( $verify_payload ),
					'headers' => array(
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message(), 400 );
			}

			$verify_result = LP_Helper::json_decode( wp_remote_retrieve_body( $response ), true );
			$is_verified   = ! empty( $verify_result['verification_status'] ) && 'SUCCESS' === strtoupper( $verify_result['verification_status'] );
			if ( ! $is_verified ) {
				throw new Exception( __( 'PayPal webhook verification failed.', 'learnpress' ), 400 );
			}

			return $payload;
		}

		/**
		 * Normalize PayPal webhook event into LearnPress subscription event schema.
		 *
		 * Maps PayPal event names into LP canonical event_type values and extracts
		 * identifiers needed by Subscription Manager (event_id, subscription_id,
		 * parent_order_id, renewal_key, amount/currency, status).
		 *
		 * @param array|object $provider_event
		 *
		 * @return array
		 */
		public function normalize_subscription_event( $provider_event ): array {
			$event = parent::normalize_subscription_event( $provider_event );
			if ( is_object( $provider_event ) ) {
				$provider_event = (array) $provider_event;
			}

			$event['event_id'] = (string) ( $provider_event['id'] ?? '' );
			$paypal_event_type = (string) ( $provider_event['event_type'] ?? '' );
			$resource          = (array) ( $provider_event['resource'] ?? array() );
			$event['metadata'] = array();

			if ( ! empty( $resource['custom_id'] ) ) {
				$event['metadata']['lp_order_id'] = (string) $resource['custom_id'];
				$event['parent_order_id']         = absint( $resource['custom_id'] );
			}

			switch ( $paypal_event_type ) {
				case 'BILLING.SUBSCRIPTION.ACTIVATED':
					$event['event_type']      = 'subscription_activated';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					$event['status']          = 'active';
					break;
				case 'BILLING.SUBSCRIPTION.UPDATED':
					$event['event_type']      = 'subscription_updated';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					$event['status']          = (string) ( $resource['status'] ?? '' );
					break;
				case 'BILLING.SUBSCRIPTION.CANCELLED':
				case 'BILLING.SUBSCRIPTION.SUSPENDED':
					$event['event_type']      = 'subscription_cancelled';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					$event['status']          = 'cancelled';
					break;
				case 'BILLING.SUBSCRIPTION.EXPIRED':
					$event['event_type']      = 'subscription_expired';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					$event['status']          = 'expired';
					break;
				case 'PAYMENT.SALE.COMPLETED':
				case 'BILLING.SUBSCRIPTION.PAYMENT.COMPLETED':
					$event['event_type']      = 'renewal_payment_succeeded';
					$event['subscription_id'] = (string) ( $resource['billing_agreement_id'] ?? ( $resource['id'] ?? '' ) );
					$event['transaction_id']  = (string) ( $resource['id'] ?? '' );
					if ( ! empty( $resource['id'] ) ) {
						$event['renewal_key'] = 'paypal_sale_' . sanitize_text_field( (string) $resource['id'] );
					}
					if ( ! empty( $resource['amount'] ) ) {
						$amount            = (array) $resource['amount'];
						$event['amount']   = (float) ( $amount['total'] ?? ( $amount['value'] ?? 0 ) );
						$event['currency'] = strtoupper( (string) ( $amount['currency'] ?? ( $amount['currency_code'] ?? '' ) ) );
					}
					break;
				case 'PAYMENT.SALE.DENIED':
				case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
					$event['event_type']      = 'renewal_payment_failed';
					$event['subscription_id'] = (string) ( $resource['billing_agreement_id'] ?? '' );
					$event['transaction_id']  = (string) ( $resource['id'] ?? '' );
					if ( ! empty( $resource['id'] ) ) {
						$event['renewal_key'] = 'paypal_sale_' . sanitize_text_field( (string) $resource['id'] );
					}
					break;
				default:
					$event['event_type'] = 'ignored';
					break;
			}

			return $event;
		}

		/**
		 * Verify, normalize, and dispatch PayPal subscription webhook event.
		 *
		 * Returns ignored status for unsupported event types and delegates valid
		 * mapped events to LP_Subscription_Manager for idempotent processing.
		 *
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 * @throws Exception
		 */
		public function listen_webhook_subscription( WP_REST_Request $request ): array {
			$verified_event = $this->verify_subscription_webhook( $request );
			$event          = $this->normalize_subscription_event( $verified_event );
			if ( empty( $event['event_type'] ) || 'ignored' === $event['event_type'] ) {
				return array(
					'status'      => 'ignored',
					'event_id'    => $event['event_id'] ?? '',
					'event_type'  => $event['event_type'] ?? 'ignored',
					'status_code' => 200,
				);
			}

			if ( ! class_exists( 'LP_Subscription_Manager' ) ) {
				throw new Exception( __( 'Subscription manager is not available.', 'learnpress' ), 500 );
			}

			return LP_Subscription_Manager::instance()->process_webhook_event( $this, $event );
		}

		/**
		 * Build manage-subscription URL shown in LearnPress order/profile context.
		 *
		 * @param LP_Order $order
		 *
		 * @return string
		 */
		public function get_manage_subscription_url( LP_Order $order ): string {
			$subscription_id = get_post_meta( $order->get_id(), self::META_SUBSCRIPTION_ID, true );
			if ( empty( $subscription_id ) ) {
				return parent::get_manage_subscription_url( $order );
			}

			$manage_url = trailingslashit( $this->paypal_url ) . 'myaccount/autopay/';

			return (string) apply_filters( 'learn-press/paypal/subscription/manage-url', $manage_url, $order, $subscription_id, $this );
		}

		/**
		 * Settings form fields for this gateway
		 *
		 * @return array
		 */
		public function get_settings(): array {
			return Config::instance()->get( $this->id, 'settings/gateway' );
		}
	}
}
