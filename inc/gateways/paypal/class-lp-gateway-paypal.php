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

		const PAYPAL_TOKEN = 'paypal_token';

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
		 * @throws Exception
		 */
		public function init() {
			if ( $this->is_enabled() ) {
				if ( $this->settings->get( 'paypal_sandbox', 'no' ) === 'no' ) {
					$this->paypal_url         = $this->paypal_live_url;
					$this->paypal_payment_url = $this->paypal_payment_live_url;
					$this->paypal_email       = $this->settings->get( 'paypal_email' );
					$this->api_url            = $this->api_live_url; // use for PayPal rest api
				} else {
					$this->paypal_url         = $this->paypal_sandbox_url;
					$this->paypal_payment_url = $this->paypal_payment_sandbox_url;
					$this->paypal_email       = $this->settings->get( 'paypal_sandbox_email' );
					$this->api_url            = $this->api_sandbox_url; // use for PayPal rest api
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
		 * Listen callback, webhook payment from PayPal.
		 */
		public function check_webhook_callback() {
			if ( ! isset( $_GET['paypay_express_checkout'] ) ) {
				return;
			}

			$paypal_order_id = LP_Request::get_param( 'token' );
			if ( empty( $paypal_order_id ) ) {
				return;
			}

			try {
				$this->capture_payment_for_order( $paypal_order_id );
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}
		}

		/**
		 * https://developer.paypal.com/api/nvp-soap/ipn/IPNImplementation/#link-ipnlistenerrequestresponseflow
		 * Check validate IPN.
		 *
		 * @return bool
		 * @deprecated 4.3.8
		 */
		/*public function validate_ipn(): bool {
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
		}*/

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

			//$subscription_data = $this->resolve_subscription_payment_data( $order );

			$subscription_data = $this->is_data_for_payment_subscription( $order );
			if ( ! empty( $subscription_data ) ) {
				$subscription_res   = $this->pay_via_subscription( $order, $subscription_data );
				$paypal_payment_url = $subscription_res['redirect_url'] ?? '';
			} else {
				$data_token         = $this->get_access_token();
				$paypal_payment_url = $this->create_payment_url( $order, $data_token );
			}

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
		 * @deprecated 4.3.8
		 */
		/*public function get_paypal_args( LP_Order $order ): array {
			$checkout   = LearnPress::instance()->checkout();
			$custom     = array(
				'order_id'       => $order->get_id(),
				'order_key'      => $order->get_order_key(),
				'checkout_email' => $checkout->get_checkout_email(),
			);
			$lp_cart    = LearnPress::instance()->get_cart();
			$cart_total = $lp_cart->calculate_totals();
			$item_arg   = array(
				'item_name_1' => $order->get_order_number(),
				'quantity_1'  => 1,
				'amount_1'    => $cart_total->total,
			);
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
		}*/

		/**
		 * Get access token from PayPal
		 *
		 * Check token expire time before get new token
		 *
		 * @throws Exception
		 * @since 4.2.4
		 * @version 1.0.1
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

			// Check token expire
			$token_exist = LP_Settings::get_option( self::PAYPAL_TOKEN );
			if ( ! empty( $token_exist ) ) {
				$token_exist      = LP_Helper::json_decode( $token_exist );
				$five_minutes_ago = time() - 5 * 60;
				if ( ! empty( $token_exist->lp_time_end ) && $token_exist->lp_time_end > $five_minutes_ago ) {
					return $token_exist;
				}
			}

			$params   = array( 'grant_type' => 'client_credentials' );
			$response = wp_remote_post(
				$this->api_url . 'v1/oauth2/token',
				array(
					'body'    => $params,
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
					),
					'timeout' => 60,
				)
			);

			$data_token_str          = wp_remote_retrieve_body( $response );
			$data_token              = LP_Helper::json_decode( $data_token_str );
			$data_token->lp_time_end = time() + $data_token->expires_in;
			$data_token_str          = json_encode( $data_token );
			if ( isset( $data_token->error ) ) {
				throw new Exception( $data_token->error_description );
			}

			if ( empty( $data_token->access_token ) || empty( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid PayPal access token', 'learnpress' ) );
			}

			LP_Settings::update_option( self::PAYPAL_TOKEN, $data_token_str );

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
			$data       = array(
				'intent'         => 'CAPTURE',
				'purchase_units' => array(
					array(
						'amount'    => array(
							'currency_code' => learn_press_get_currency(),
							'value'         => strval( round( $cart_total->total, 2 ) ),
						),
						'custom_id' => $order_id,
					),
				),
				'payment_source' => array(
					'paypal' => array(
						'experience_context' => array(
							'payment_method_preference' => 'UNRESTRICTED',
							'brand_name'                => $brand_name,
							'landing_page'              => 'LOGIN',
							'user_action'               => 'PAY_NOW',
							'return_url'                => $return_url,
							'cancel_url'                => $cancel_url,
						),
					),
				),
			);

			return apply_filters( 'learn-press/paypal-rest/args', $data );
		}

		/**
		 * Create Order PayPal and get checkout url
		 *
		 * @param LP_Order $order
		 * @param object   $data_token { scope, access_token, token_type, app_id, expires_in, nonce }
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
				array(
					'body'    => json_encode( $params ),
					'headers' => array(
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 60,
				)
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
		 * @throws Exception
		 * @version 1.0.1
		 * @since 4.2.4
		 */
		public function capture_payment_for_order( string $paypal_order_id ): bool {
			$data_token = $this->get_access_token();
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
		 * @deprecated 4.3.8
		 */
		/*protected function validate_subscription_payload( array $data ): array {
			$data = parent::validate_subscription_payload( $data );

			// PayPal subscription API expects a plan id string and positive quantity.
			$data['price_id'] = (string) $data['price_id'];
			$data['quantity'] = max( 1, absint( $data['quantity'] ) );

			return $data;
		}*/

		/**
		 * Convert generic interval to PayPal interval unit.
		 *
		 * @param string $interval
		 *
		 * @return string
		 * @deprecated 4.3.8
		 */
		/*protected function map_paypal_interval_unit( string $interval ): string {

			$map = array(
				'day'   => 'DAY',
				'week'  => 'WEEK',
				'month' => 'MONTH',
				'year'  => 'YEAR',
			);

			return $map[ $interval ] ?? 'MONTH';
		}*/

		/**
		 * Convert PayPal interval unit to LearnPress interval slug.
		 *
		 * @param string $interval_unit
		 *
		 * @return string
		 * @deprecated 4.3.8
		 */
		/*protected function map_paypal_interval_slug( string $interval_unit ): string {

			$map = array(
				'DAY'   => 'day',
				'WEEK'  => 'week',
				'MONTH' => 'month',
				'YEAR'  => 'year',
			);

			$interval_unit = strtoupper( sanitize_text_field( $interval_unit ) );

			return $map[ $interval_unit ] ?? 'month';
		}*/

		/**
		 * Build normalized summary from PayPal plan payload.
		 *
		 * @param array $plan_data
		 *
		 * @return array
		 * @since 4.3.7
		 * @version 1.0.1
		 */
		protected function build_paypal_plan_summary( array $plan_data ): array {
			$summary = array(
				'plan_id'        => $plan_data['id'] ?? '',
				'status'         => strtolower( (string) ( $plan_data['status'] ?? '' ) ),
				'amount'         => 0.0,
				'currency'       => '',
				'interval'       => '',
				'interval_count' => 1,
				'setup_fee'      => 0.0,
				'product_id'     => (string) ( $plan_data['product_id'] ?? '' ),
			);

			if ( ! empty( $plan_data['payment_preferences']['setup_fee']['value'] ) ) {
				$summary['setup_fee'] = (float) $plan_data['payment_preferences']['setup_fee']['value'];
			}

			if ( ! empty( $plan_data['billing_cycles'] ) && is_array( $plan_data['billing_cycles'] ) ) {
				foreach ( $plan_data['billing_cycles'] as $billing_cycle ) {
					if ( ! is_array( $billing_cycle ) ) {
						continue;
					}
					if ( ( $billing_cycle['tenure_type'] ?? '' ) !== 'REGULAR' ) {
						continue;
					}

					$summary['amount']         = (float) ( $billing_cycle['pricing_scheme']['fixed_price']['value'] ?? 0 );
					$summary['currency']       = strtolower( (string) ( $billing_cycle['pricing_scheme']['fixed_price']['currency_code'] ?? '' ) );
					$summary['interval']       = strtolower( (string) ( $billing_cycle['frequency']['interval_unit'] ?? '' ) );
					$summary['interval_count'] = max( 1, absint( $billing_cycle['frequency']['interval_count'] ?? 1 ) );
					break;
				}
			}

			return $summary;
		}

		/**
		 * Create a PayPal subscription plan (creates product first if needed).
		 *
		 * - If `product_id` is empty, creates a new PayPal product via v1/catalogs/products API,
		 *   then creates a billing plan via v1/billing/plans API.
		 * - Supports trial period and setup fee.
		 * - Returns full PayPal product and plan data on success.
		 *
		 * @param array $data Required: name, amount, currency, interval, interval_count.
		 * Optional: description, trial_days, setup_fee, product_id, metadata.
		 *
		 * @return array With keys: status, product, plan, message.
		 * @throws Exception On API errors or validation failures. Includes full PayPal error details if available.
		 * @since 4.3.7
		 * @version 1.0.1
		 */
		public function create_plan( array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$data = $this->validate_data_plan_payload( $data );

			// PayPal-specific optional keys are normalized at gateway level.
			$description = LP_Helper::sanitize_params_submitted( $data['description'] ?? '', 'html' );
			$trial_days  = absint( $data['trial_days'] ?? 0 );
			$setup_fee   = (float) ( $data['setup_fee'] ?? 0 );

			$data_token = $this->get_access_token();

			// Create product before create plan
			$product_id   = $data['product_id'] ?? '';
			$product_data = [ 'id' => $product_id ];
			if ( empty( $product_id ) ) {
				$product_payload = array(
					'name' => $data['name'] ?? '',
					'type' => 'SERVICE',
				);

				// Add description if not empty
				if ( ! empty( $description ) ) {
					$product_payload['description'] = $description;
				}

				// Call API create product
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

				$product_body = wp_remote_retrieve_body( $product_response );
				$product_data = LP_Helper::json_decode( $product_body, true );

				// Error create product return from PayPal
				if ( ! empty( $product_data['debug_id'] ) ) {
					throw new Exception(
						__( 'Create Product: ' ) . $product_data['details'][0]['description'],
						(int) $product_data['debug_id']
					);
				}

				if ( empty( $product_data['id'] ) ) {
					throw new Exception( __( 'Invalid PayPal product response.', 'learnpress' ) );
				}

				$product_id = $product_data['id'];
			}

			$currency_code  = strtoupper( (string) $data['currency'] );
			$billing_cycles = array();
			$sequence       = 1;

			// Set trial period
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

			// Set regular price
			$billing_cycles[] = array(
				'frequency'      => array(
					'interval_unit'  => strtoupper( (string) $data['interval'] ?? 'MONTH' ),
					'interval_count' => max( 1, absint( $data['interval_count'] ?? 0 ) ),
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
				'name'                => $data['name'] ?? '',
				'status'              => 'ACTIVE',
				'billing_cycles'      => $billing_cycles,
				'payment_preferences' => array(
					'auto_bill_outstanding'     => true,
					'setup_fee'                 => array(
						'value'         => number_format( $setup_fee, 2, '.', '' ),
						'currency_code' => $currency_code,
					),
					'setup_fee_failure_action'  => 'CONTINUE',
					'payment_failure_threshold' => 3,
				),
			);

			// Add description if not empty
			if ( ! empty( $description ) ) {
				$plan_payload['description'] = $description;
			}

			// Call API create plan
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

			$body          = wp_remote_retrieve_body( $plan_response );
			$response_data = LP_Helper::json_decode( $body, true );

			// Error return from PayPal
			if ( ! empty( $response_data['debug_id'] ) ) {
				throw new Exception(
					__( 'Create Plan: ' ) . $response_data['details'][0]['description'],
					(int) $response_data['debug_id']
				);
			}

			if ( empty( $response_data['id'] ) ) {
				throw new Exception( __( 'Invalid PayPal plan response.', 'learnpress' ) );
			}

			return array(
				'status'  => 'success',
				'product' => $product_data,
				'plan'    => $response_data,
				'message' => __( 'PayPal subscription plan created.', 'learnpress' ),
			);
		}

		/**
		 * List PayPal billing plans.
		 *
		 * @param array $args Supported: page, page_size, total_required, product_id.
		 *
		 * @return array
		 * @throws Exception
		 * @deprecated 4.3.8
		 */
		/*public function list_plans( array $args = array() ): array {

			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$args = wp_parse_args(
				$args,
				array(
					'page'           => 1,
					'page_size'      => 20,
					'total_required' => false,
					'product_id'     => '',
				)
			);

			$query_args = array(
				'page'           => max( 1, absint( $args['page'] ) ),
				'page_size'      => max( 1, min( 20, absint( $args['page_size'] ) ) ),
				'total_required' => ! empty( $args['total_required'] ) ? 'true' : 'false',
			);
			if ( ! empty( $args['product_id'] ) ) {
				$query_args['product_id'] = sanitize_text_field( wp_unslash( (string) $args['product_id'] ) );
			}

			$data_token = $this->get_access_token();
			if ( empty( $data_token->access_token ) || empty( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$plans_response = wp_remote_get(
				$this->api_url . 'v1/billing/plans?' . http_build_query( $query_args ),
				array(
					'headers' => array(
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $plans_response ) ) {
				throw new Exception( $plans_response->get_error_message() );
			}

			$plans_body = LP_Helper::json_decode( wp_remote_retrieve_body( $plans_response ) );
			if ( ! is_object( $plans_body ) ) {
				throw new Exception( __( 'Invalid PayPal plans response.', 'learnpress' ) );
			}

			$plans     = array();
			$summaries = array();
			if ( ! empty( $plans_body->plans ) && is_array( $plans_body->plans ) ) {
				foreach ( $plans_body->plans as $plan ) {
					if ( ! is_object( $plan ) ) {
						continue;
					}

					$plans[]     = $plan;
					$summaries[] = $this->build_paypal_plan_summary( $plan );
				}
			}

			return array(
				'status'      => 'success',
				'plans'       => $plans,
				'summaries'   => $summaries,
				'total_items' => absint( $plans_body->total_items ?? count( $plans ) ),
				'total_pages' => absint( $plans_body->total_pages ?? 1 ),
				'message'     => __( 'PayPal subscription plans fetched.', 'learnpress' ),
			);
		}*/

		/**
		 * Get PayPal billing plan details by plan id.
		 *
		 * Returned `summary` is normalized for integration checks when external
		 * code compares local subscription config with remote provider plan values.
		 *
		 * @param string $plan_id
		 *
		 * @return array
		 * @throws Exception
		 */
		public function get_plan( string $plan_id ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$plan_id = LP_Helper::sanitize_params_submitted( $plan_id );
			if ( empty( $plan_id ) ) {
				throw new Exception( __( 'Missing subscription plan id.', 'learnpress' ) );
			}

			$data_token = $this->get_access_token();

			$plan_response = wp_remote_get(
				$this->api_url . 'v1/billing/plans/' . rawurlencode( $plan_id ),
				array(
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

			$plan_data = LP_Helper::json_decode( wp_remote_retrieve_body( $plan_response ), true );
			if ( isset( $plan_data['debug_id'] ) ) {
				throw new Exception(
					__( 'Get plan: ', 'learnpress' ) . $plan_data['details'][0]['description'],
					(int) $plan_data['debug_id']
				);
			}

			$summary = $this->build_paypal_plan_summary( $plan_data );

			return array(
				'status'  => 'success',
				'plan'    => $plan_data,
				'summary' => $summary,
				'message' => __( 'PayPal subscription plan fetched.', 'learnpress' ),
			);
		}

		/**
		 * Update PayPal billing plan values.
		 *
		 * Supported update fields:
		 * - name, description, status, setup_fee
		 * - amount (uses update-pricing-schemes endpoint for REGULAR cycle)
		 *
		 * @param string $plan_id
		 * @param array  $data
		 *
		 * @return array
		 * @throws Exception
		 * @since 4.3.7
		 * @version 1.0.1
		 */
		public function update_plan( string $plan_id, array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$plan_id = LP_Helper::sanitize_params_submitted( $plan_id );
			if ( empty( $plan_id ) ) {
				throw new Exception( __( 'Missing subscription plan id.', 'learnpress' ) );
			}

			$current      = $this->get_plan( $plan_id );
			$current_plan = $current['plan'];
			$current_sum  = $current['summary'];

			$data = $this->validate_data_plan_payload( $data );

			$data_token = $this->get_access_token();
			if ( empty( $data_token->access_token ) || empty( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$patches = array();
			if ( ! empty( $data['name'] ?? '' ) ) {
				$patches[] = array(
					'op'    => 'replace',
					'path'  => '/name',
					'value' => sanitize_text_field( wp_unslash( (string) $data['name'] ) ),
				);
			}
			if ( ! empty( $data['description'] ?? '' ) ) {
				$patches[] = array(
					'op'    => 'replace',
					'path'  => '/description',
					'value' => sanitize_text_field( wp_unslash( (string) $data['description'] ) ),
				);
			}
			if ( ! empty( $data['status'] ?? '' ) ) {
				$status = strtoupper( sanitize_text_field( wp_unslash( (string) $data['status'] ) ) );
				if ( ! in_array( $status, array( 'ACTIVE', 'INACTIVE' ), true ) ) {
					throw new Exception( __( 'Invalid PayPal subscription plan status.', 'learnpress' ) );
				}
				$patches[] = array(
					'op'    => 'replace',
					'path'  => '/status',
					'value' => $status,
				);
			}
			if ( isset( $data['setup_fee'] ) && '' !== $data['setup_fee'] ) {
				$setup_fee = (float) $data['setup_fee'];
				if ( $setup_fee < 0 ) {
					throw new Exception( __( 'Invalid subscription setup fee.', 'learnpress' ) );
				}
				$patches[] = array(
					'op'    => 'replace',
					'path'  => '/payment_preferences/setup_fee',
					'value' => array(
						'value'         => number_format( $setup_fee, 2, '.', '' ),
						'currency_code' => strtoupper( $current_sum['currency'] ?? '' ),
					),
				);
			}

			if ( ! empty( $patches ) ) {
				$patch_response = wp_remote_request(
					$this->api_url . 'v1/billing/plans/' . rawurlencode( $plan_id ),
					array(
						'method'  => 'PATCH',
						'body'    => wp_json_encode( $patches ),
						'headers' => array(
							'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
							'Content-Type'  => 'application/json',
						),
						'timeout' => 60,
					)
				);
				if ( is_wp_error( $patch_response ) ) {
					throw new Exception( $patch_response->get_error_message() );
				}

				// Empty is for success, not empty is for error
				$patch_response_body = wp_remote_retrieve_body( $patch_response );
				if ( ! empty( $patch_response_body ) ) {
					$patched_plan = LP_Helper::json_decode( $patch_response_body, true );
					if ( isset( $patched_plan['debug_id'] ) ) {
						throw new Exception(
							__( 'Update plan: ', 'learnpress' ) . $patched_plan['details'][0]['description'],
							(int) $patched_plan['debug_id']
						);
					}
				}
			}

			// Check if interval is changed will not allow to change
			if ( ! empty( $data['interval'] ?? '' ) ) {
				if ( $data['interval'] !== $current_sum['interval'] ) {
					throw new Exception( __( 'PayPal plan interval cannot be changed. Create a new plan instead.', 'learnpress' ) );
				}
			}

			// Check if interval count is changed will not allow to change
			if ( ! empty( $data['interval_count'] ?? '' ) ) {
				if ( $data['interval_count'] !== $current_sum['interval_count'] ) {
					throw new Exception( __( 'PayPal plan interval count cannot be changed. Create a new plan instead.', 'learnpress' ) );
				}
			}

			if ( ! empty( $data['amount'] ?? '' ) ) {
				$new_amount = (float) $data['amount'];
				if ( $new_amount <= 0 ) {
					throw new Exception( __( 'Invalid subscription amount.', 'learnpress' ) );
				}

				$currency_code = strtoupper( $data['currency'] ?? '' );
				if ( empty( $currency_code ) ) {
					throw new Exception( __( 'Missing subscription currency.', 'learnpress' ) );
				}

				$regular_sequence = 1;
				if ( ! empty( $current_plan['billing_cycles'] ) && is_array( $current_plan['billing_cycles'] ) ) {
					foreach ( $current_plan['billing_cycles'] as $billing_cycle ) {
						if ( ! is_array( $billing_cycle ) ) {
							continue;
						}
						if ( ( $billing_cycle['tenure_type'] ?? '' ) === 'REGULAR' ) {
							$regular_sequence = max( 1, absint( $billing_cycle['sequence'] ?? 1 ) );
							break;
						}
					}
				}

				$pricing_response = wp_remote_post(
					$this->api_url . 'v1/billing/plans/' . rawurlencode( $plan_id ) . '/update-pricing-schemes',
					array(
						'body'    => wp_json_encode(
							array(
								'pricing_schemes' => array(
									array(
										'billing_cycle_sequence' => $regular_sequence,
										'pricing_scheme' => array(
											'fixed_price' => array(
												'value' => number_format( $new_amount, 2, '.', '' ),
												'currency_code' => $currency_code,
											),
										),
									),
								),
							)
						),
						'headers' => array(
							'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
							'Content-Type'  => 'application/json',
						),
						'timeout' => 60,
					)
				);
				if ( is_wp_error( $pricing_response ) ) {
					throw new Exception( $pricing_response->get_error_message() );
				}

				if ( isset( $pricing_response['debug_id'] ) ) {
					throw new Exception(
						__( 'Update plan pricing: ', 'learnpress' ) . $pricing_response['details'][0]['description'],
						(int) $pricing_response['debug_id']
					);
				}
			}

			$updated            = $this->get_plan( $plan_id );
			$updated['message'] = __( 'PayPal subscription plan updated.', 'learnpress' );

			return $updated;
		}

		/**
		 * Deactivate PayPal billing plan.
		 *
		 * PayPal does not support hard-delete for plans; this operation marks
		 * plan status as INACTIVE.
		 *
		 * @param string $plan_id
		 *
		 * @return array
		 * @throws Exception
		 */
		public function delete_plan( string $plan_id ): array {
			$deleted            = $this->update_plan(
				$plan_id,
				array(
					'status' => 'INACTIVE',
				)
			);
			$deleted['message'] = __( 'PayPal subscription plan deactivated.', 'learnpress' );

			return $deleted;
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
		 * @deprecated 4.3.8 Use pay_via_subscription() instead.
		 */
		/*public function pay_subscription( array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$data = $this->validate_subscription_payload( $data );

			$data_token = $this->get_access_token();
			if ( empty( $data_token->access_token ) || empty( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$metadata              = array_map(
				function ( $value ) {
					if ( is_scalar( $value ) || is_null( $value ) ) {
						return (string) $value;
					}
					return wp_json_encode( $value );
				},
				(array) $data['metadata']
			);
			$order_subscription_id = absint( $metadata['lp_order_id'] ?? 0 );

			$request_body = array(
				'plan_id'             => (string) $data['price_id'],
				'quantity'            => (string) max( 1, absint( $data['quantity'] ) ),
				'custom_id'           => ! empty( $order_subscription_id ) ? (string) $order_subscription_id : '',
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
		}*/

		/**
		 * Create PayPal subscription checkout and return redirect payload.
		 *
		 * Request is sent to PayPal Billing Subscriptions API using:
		 * - plan_id = subscription price/plan id configured in PayPal.
		 * - custom_id = LearnPress parent order id for reconciliation.
		 * - return/cancel URLs = checkout callbacks.
		 *
		 * @param array $data [ 'plan_id' => string, 'quantity' => int, 'success_url' => string, 'cancel_url' => string ]
		 * Required: plan_id
		 *
		 * @return array [ 'redirect_url' => string, and data from v1/billing/subscriptions ]
		 * @throws Exception
		 * @since 4.3.7
		 * @version 1.0.1
		 */
		public function pay_via_subscription( LP_Order $lp_order, array $data ): array {
			if ( ! $this->is_subscription_enabled() ) {
				throw new Exception( __( 'PayPal subscriptions are disabled.', 'learnpress' ) );
			}

			$plan_id = $data['plan_id'] ?? '';
			if ( empty( $plan_id ) ) {
				throw new Exception( __( 'PayPal subscription plan ID is invalid.', 'learnpress' ) );
			}

			$lp_order_id = $lp_order->get_id();
			$data_token  = $this->get_access_token();

			$request_body = array(
				'plan_id'             => $plan_id,
				'quantity'            => max( 1, absint( $data['quantity'] ?? 0 ) ),
				'custom_id'           => $lp_order_id,
				'application_context' => array(
					'brand_name' => ! empty( get_bloginfo() ) ? get_bloginfo() : 'LearnPress',
					'return_url' => esc_url_raw( $data['success_url'] ?? '' ),
					'cancel_url' => esc_url_raw( $data['cancel_url'] ?? '' ),
				),
			);

			// If the user already completed a trial for this plan on a previous order.
			$user_has_trial_done = get_user_meta( $lp_order->get_user_id(), 'user_plan_trial', true );
			$user_has_trial_done = apply_filters(
				'learn-press/subscription/user-has-trial',
				$user_has_trial_done,
				$lp_order,
				$plan_id,
				$this
			);
			if ( $user_has_trial_done && $user_has_trial_done === $plan_id ) {
				LP_Debug::log_to_comment( 'Pay renew for user trial done: ' . $plan_id );
				// Fetch plan details to find the REGULAR billing cycle and its pricing.
				$plan_response = wp_remote_get(
					$this->api_url . 'v1/billing/plans/' . rawurlencode( $plan_id ),
					array(
						'headers' => array(
							'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
							'Content-Type'  => 'application/json',
						),
						'timeout' => 60,
					)
				);

				if ( ! is_wp_error( $plan_response ) ) {
					$plan_body           = LP_Helper::json_decode( wp_remote_retrieve_body( $plan_response ), true );
					$plan_billing_cycles = $plan_body['billing_cycles'] ?? array();

					LP_Debug::log_to_comment( 'Plan: ' . json_encode( $plan_body, JSON_UNESCAPED_UNICODE ) );

					$regular_cycle = null;
					foreach ( $plan_billing_cycles as $cycle ) {
						if ( ( $cycle['tenure_type'] ?? '' ) === 'REGULAR' ) {
							$regular_cycle = $cycle;
							break;
						}
					}

					if ( $regular_cycle ) {
						// Override billing_cycles with only the REGULAR cycle at sequence=1,
						// effectively skipping any trial cycle for this subscription.
						$override_cycle = array(
							'sequence' => 1,
						);
						if ( ! empty( $regular_cycle['pricing_scheme'] ) ) {
							$override_cycle['pricing_scheme'] = $regular_cycle['pricing_scheme'];
						}
						$request_body['plan']['billing_cycles'] = array( $override_cycle );
						//$request_body['plan']['payment_preferences'] = $plan_body['payment_preferences'] ?? '';
					}
				}
			}

			LP_Debug::log_to_comment( 'Payment param send: ' . json_encode( $request_body, JSON_UNESCAPED_UNICODE ) );

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
				throw new Exception( $response->get_error_message(), $response->get_error_code() );
			}

			$body          = wp_remote_retrieve_body( $response );
			$response_data = LP_Helper::json_decode( $body, true );

			LP_Debug::log_to_comment( 'Pay subscription: ' . $body );

			// Error return from PayPal
			if ( ! empty( $response_data['debug_id'] ) ) {
				throw new Exception(
					__( 'Pay PayPal subscription: ', 'learnpress' ) . $response_data['details'][0]['description'],
					(int) $response_data['debug_id']
				);
			}

			if ( empty( $response_data['id'] ) ) {
				throw new Exception( __( 'Invalid PayPal subscription response.', 'learnpress' ) );
			}

			// Update info for LP Order
			update_post_meta( $lp_order_id, self::META_SUBSCRIPTION_ID, $response_data['id'] );
			update_post_meta( $lp_order_id, self::META_SUBSCRIPTION_PLAN_ID, $plan_id );

			$response_data['redirect_url'] = $response_data['links'][0]['href'];

			return $response_data;
		}

		/**
		 * Reverse verify PayPal subscription webhook before processing.
		 *
		 * Verification uses PayPal /verify-webhook-signature endpoint and requires:
		 * - configured webhook id from merchant settings,
		 * - required PayPal transmission headers,
		 * - raw JSON payload as webhook_event.
		 *
		 * @param array $webhook_data Webhook payload + headers extracted by listener.
		 *
		 * @return array Verified webhook payload array on success.
		 * @throws Exception
		 * @deprecated 4.3.8
		 */
		/*public function verify_subscription_webhook( array $webhook_data ): array {

			if ( empty( $this->subscription_webhook_id ) ) {
				throw new Exception( __( 'PayPal subscription webhook id is missing.', 'learnpress' ), 500 );
			}

			$required_headers = array(
				'paypal-auth-algo',
				'paypal-cert-url',
				'paypal-transmission-id',
				'paypal-transmission-sig',
				'paypal-transmission-time',
			);
			$this->validate_webhook_data_contract( $webhook_data, array( 'body', 'headers' ), $required_headers );

			$payload = $webhook_data['body'] ?? null;
			if ( is_string( $payload ) ) {
				$payload = json_decode( $payload, true );
			}
			if ( empty( $payload ) || ! is_array( $payload ) ) {
				throw new Exception( __( 'Invalid PayPal webhook payload.', 'learnpress' ), 400 );
			}

			$headers_map = is_array( $webhook_data['headers'] ?? null ) ? $webhook_data['headers'] : array();
			foreach ( $required_headers as $required_header ) {
				$header_value = sanitize_text_field( (string) ( $headers_map[ $required_header ] ?? '' ) );
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
		}*/

		/**
		 * Normalize PayPal webhook event into LearnPress subscription event schema.
		 *
		 * Maps PayPal event names into LP canonical event_type values and extracts
		 * identifiers needed by Subscription Manager (event_id, subscription_id,
		 * parent_order_id, renewal_key, amount/currency).
		 *
		 * @param array|object $provider_event
		 *
		 * @return array
		 * @deprecated 4.3.8 Use normalize_subscription_data instead.
		 */
		/*public function normalize_subscription_event( $provider_event ): array {
			$event = parent::normalize_subscription_event( $provider_event );
			if ( is_object( $provider_event ) ) {
				$provider_event = (array) $provider_event;
			}

			$event['event_id'] = (string) ( $provider_event['id'] ?? '' );
			$paypal_event_type = (string) ( $provider_event['event_type'] ?? '' );
			$resource          = (array) ( $provider_event['resource'] ?? array() );
			$event['metadata'] = array();

			$order_subscription_id = '';
			if ( ! empty( $resource['custom_id'] ) ) {
				$order_subscription_id = (string) $resource['custom_id'];
			} elseif ( ! empty( $resource['custom'] ) ) {
				// Some PayPal sale resources provide `custom` instead of `custom_id`.
				$order_subscription_id = (string) $resource['custom'];
			}
			if ( '' !== $order_subscription_id ) {
				$event['metadata']['lp_order_id'] = $order_subscription_id;
				$event['parent_order_id']         = absint( $order_subscription_id );
			}

			switch ( $paypal_event_type ) {
				case 'BILLING.SUBSCRIPTION.ACTIVATED':
					$event['event_type']      = 'subscription_activated';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					break;
				case 'BILLING.SUBSCRIPTION.UPDATED':
					// This callback does not add state transition value for LP flow.
					// Keep ignored intentionally to avoid ambiguous status handling.
					$event['event_type'] = 'ignored';
					break;
				case 'BILLING.SUBSCRIPTION.CANCELLED':
					$event['event_type']      = 'subscription_cancelled';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					break;
				case 'BILLING.SUBSCRIPTION.SUSPENDED':
					$event['event_type']      = 'subscription_suspended';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					break;
				case 'BILLING.SUBSCRIPTION.EXPIRED':
					// EXPPIRED is usually for fixed-term plans (total_cycles > 0),
					// not the common auto-renew membership plan (total_cycles = 0).
					$event['event_type']      = 'subscription_expired';
					$event['subscription_id'] = (string) ( $resource['id'] ?? '' );
					break;
				case 'PAYMENT.SALE.COMPLETED':
					$event['event_type']      = 'renewal_payment_succeeded';
					$event['subscription_id'] = (string) ( $resource['billing_agreement_id'] ?? '' );
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
					$event['event_type']      = 'renewal_payment_failed';
					$event['subscription_id'] = (string) ( $resource['billing_agreement_id'] ?? '' );
					$event['transaction_id']  = (string) ( $resource['id'] ?? '' );
					if ( ! empty( $resource['id'] ) ) {
						$event['renewal_key'] = 'paypal_sale_' . sanitize_text_field( (string) $resource['id'] );
					}
					break;
				case 'BILLING.SUBSCRIPTION.PAYMENT.FAILED':
					$event['event_type']      = 'renewal_payment_failed';
					$event['subscription_id'] = (string) ( $resource['id'] ?? ( $resource['billing_agreement_id'] ?? '' ) );
					$event['transaction_id']  = (string) ( $resource['sale_id'] ?? ( $resource['transaction_id'] ?? '' ) );
					break;
				default:
					$event['event_type'] = 'ignored';
					break;
			}

			return $event;
		}*/

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
		 * @deprecated 4.3.8 Use capture_subscription_webhook instead.
		 */
		/*public function listen_webhook_subscription( WP_REST_Request $request ): array {

			$required_headers = array(
				'paypal-auth-algo',
				'paypal-cert-url',
				'paypal-transmission-id',
				'paypal-transmission-sig',
				'paypal-transmission-time',
			);
			$webhook_data     = $this->build_webhook_data_from_request( $request, $required_headers, true );

			$verified_event = $this->verify_subscription_webhook( $webhook_data );
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
		}*/

		/**
		 * Receive data from webhook.
		 * Verify, normalize, and dispatch PayPal subscription webhook event.
		 *
		 * Flow for a plan purchase lifecycle:
		 * First purchase: BILLING.SUBSCRIPTION.CREATED -> BILLING.SUBSCRIPTION.ACTIVATED -> PAYMENT.SALE.COMPLETED
		 * Renewal: PAYMENT.SALE.COMPLETED
		 *
		 * @param WP_REST_Request $request
		 *
		 * @throws Exception
		 * @since 4.3.7
		 * @version 1.0.0
		 */
		public function capture_subscription_webhook( WP_REST_Request $request ) {
			$webhook_data = LP_Helper::json_decode( $request->get_body(), true );

			// Verify data from webhook
			$webhook_data_verify = [
				'auth_algo'         => $request->get_header( 'PAYPAL-AUTH-ALGO' ) ?? '',
				'cert_url'          => $request->get_header( 'PAYPAL-CERT-URL' ) ?? '',
				'transmission_id'   => $request->get_header( 'PAYPAL-TRANSMISSION-ID' ) ?? '',
				'transmission_sig'  => $request->get_header( 'PAYPAL-TRANSMISSION-SIG' ) ?? '',
				'transmission_time' => $request->get_header( 'PAYPAL-TRANSMISSION-TIME' ) ?? '',
				'webhook_id'        => $this->subscription_webhook_id,
				'webhook_event'     => $webhook_data,
			];
			$this->verify_data_from_webhook_subscription( $webhook_data_verify );

			// Check lp order exists
			// SUBSCRIPTION return 'custom_id', PAYMENT.SALE return 'custom'
			$lp_order_id = $webhook_data['resource']['custom'] ?? $webhook_data['resource']['custom_id'] ?? '';
			$lp_order    = learn_press_get_order( $lp_order_id );
			if ( ! $lp_order ) {
				error_log( 'LearnPress order is invalid: ' . json_encode( $webhook_data, JSON_UNESCAPED_UNICODE ) );
				return;
			}
			$webhook_data['lp_order_id'] = $lp_order_id;

			// Webhook billing subscription create
			$is_billing_subscription_created = $this->capture_billing_subscription_create( $lp_order, $webhook_data );
			if ( $is_billing_subscription_created ) {
				return;
			}

			// Capture payment setup fee or renewal
			$this->capture_payment_setup_fee_or_renewal( $lp_order, $webhook_data );
			// Capture subscription data
			$this->capture_subscription_data( $webhook_data );

			$lp_payment_success         = $lp_order->get_meta( self::META_SUBSCRIPTION_DATA_PAYMENT_SUCCESS );
			$lp_subscription_status_tmp = $lp_order->get_meta( self::META_SUBSCRIPTION_STATUS_TMP );
			$lp_subscription_status     = $lp_order->get_meta( self::META_SUBSCRIPTION_STATUS );

			// Check lp order status is activated/trial will renew
			if ( $lp_order->is_completed() ) {
				if ( in_array(
					$lp_subscription_status,
					[ LP_Subscription_Manager::STATUS_ACTIVATED, LP_Subscription_Manager::STATUS_TRIAL ]
				) ) {
					LP_Debug::log_to_comment( 'Activated/Trial to renew' );
					update_post_meta( $lp_order_id, self::META_SUBSCRIPTION_STATUS, LP_Subscription_Manager::STATUS_ACTIVATED );
					$lp_subscription_status_set_to_handle = LP_Subscription_Manager::STATUS_RENEWED;
				}
			} elseif ( ! empty( $lp_payment_success )
				&& $lp_subscription_status_tmp === LP_Subscription_Manager::STATUS_ACTIVATED ) {
				// If payment success and subscription status tmp is not empty
				LP_Debug::log_to_comment( 'Payment success and subscription status tmp is not empty' );
				$lp_subscription_status_set_to_handle = LP_Subscription_Manager::STATUS_ACTIVATED;
			} elseif ( $webhook_data['lp_subscription_status'] !== LP_Subscription_Manager::STATUS_ACTIVATED ) {
				// If subscription status is not activated, use subscription status from webhook data switch
				$lp_subscription_status_set_to_handle = $webhook_data['lp_subscription_status'] ?? '';
				LP_Debug::log_to_comment( 'Subscription status is not activated: ' . $lp_subscription_status_set_to_handle );
			}

			if ( empty( $lp_subscription_status_set_to_handle ) ) {
				return;
			}

			// Dispatch webhook
			$this->process_subscription_by_status( $lp_order, $lp_subscription_status_set_to_handle, $webhook_data );
		}

		/**
		 * Reverse verify PayPal subscription webhook before processing.
		 * Doc: https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature_post
		 *
		 * @param array $webhook_data
		 *
		 * @throws Exception
		 */
		public function verify_data_from_webhook_subscription( array $webhook_data ) {
			$data_token = $this->get_access_token();

			$response = wp_remote_post(
				$this->api_url . 'v1/notifications/verify-webhook-signature',
				array(
					'body'    => wp_json_encode( $webhook_data ),
					'headers' => array(
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
						'Content-Type'  => 'application/json',
					),
					'timeout' => 60,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message(), $response->get_error_code() );
			}

			$verify_result = LP_Helper::json_decode( wp_remote_retrieve_body( $response ), true );
			$is_verified   = ! empty( $verify_result['verification_status'] )
							&& 'SUCCESS' === strtoupper( $verify_result['verification_status'] );
			if ( ! $is_verified ) {
				throw new Exception( __( 'PayPal webhook verification failed.', 'learnpress' ), 400 );
			}
		}

		/**
		 * Capture PayPal subscription state from webhook data.
		 *
		 * This method only handles PayPal webhook payloads whose resource type is
		 * `subscription`. Other webhook events can still be sent to the same endpoint,
		 * so non-subscription resources, missing resources, and completed parent orders
		 * are ignored instead of treated as errors.
		 *
		 * For `BILLING.SUBSCRIPTION.ACTIVATED`, the PayPal billing cycle data is used
		 * to decide whether the LearnPress subscription status should be trial or
		 * activated. Cancelled, suspended, and expired events are mapped to the matching
		 * LearnPress subscription statuses. The resolved status is written back into
		 * `$webhook_data['lp_subscription_status']` for later dispatch.
		 *
		 * @param array $webhook_data
		 *
		 * @return void
		 * @throws Exception When the LearnPress order is invalid, the PayPal event type is missing, or no subscription status can be resolved.
		 * @since 4.3.7
		 * @version 1.0.0
		 */
		public function capture_subscription_data( array &$webhook_data = [] ) {
			parent::normalize_subscription_data( $webhook_data );

			$lp_order_id = $webhook_data['lp_order_id'] ?? 0;
			$lp_order    = learn_press_get_order( $lp_order_id );
			if ( ! $lp_order ) {
				throw new Exception( __( 'LearnPress order is invalid.', 'learnpress' ), 400 );
			}

			if ( empty( $webhook_data['resource'] ) || ! is_array( $webhook_data['resource'] ) ) {
				return;
			}

			$resource      = $webhook_data['resource'];
			$resource_type = $webhook_data['resource_type'] ?? '';
			if ( $resource_type !== 'subscription' ) {
				return;
			}

			/*
			 * If order is completed, skip if is data webhook subscription
			 * Because if order completed with subscription activated and payment success
			 * The next for renewal only get via payment, not return subscription
			 */
			if ( $lp_order->is_completed() ) {
				throw new Exception( __( 'Ignore lp order completed with subscription activated.', 'learnpress' ), 400 );
			}

			$event_type = $webhook_data['event_type'] ?? '';
			if ( empty( $event_type ) ) {
				throw new Exception( 'PayPal subscription event type is invalid.', 400 );
			}

			$lp_subscription_status = '';
			switch ( $event_type ) {
				case 'BILLING.SUBSCRIPTION.ACTIVATED':
					// Check is trial or active
					$billing_info = $resource['billing_info'] ?? false;
					if ( ! empty( $billing_info ) ) {
						$cycle_executions = $billing_info['cycle_executions'] ?? [];
						if ( ! empty( $cycle_executions ) ) {
							$trialCycle   = null;
							$regularCycle = null;
							foreach ( $cycle_executions as $cycle ) {
								if ( $cycle['tenure_type'] === 'TRIAL' ) {
									$trialCycle = $cycle;
								} elseif ( $cycle['tenure_type'] === 'REGULAR' ) {
									$regularCycle = $cycle;
								}
							}

							// 1. Check if it's the first time in Trial
							// If trial is running (remaining > 0)
							// Or the trial just completed its last charge (completed == total_cycles) but has not yet transitioned to the next REGULAR cycle
							if ( $trialCycle ) {
								$trialCycleRemaining = $trialCycle['cycles_remaining'] ?? 0;
								$trialCycleCompleted = $trialCycle['cycles_completed'] ?? 0;
								$trialCycleTotal     = $trialCycle['total_cycles'] ?? 0;

								if ( $trialCycleRemaining > 0 ||
									( $trialCycleTotal && $trialCycleCompleted === $trialCycleTotal ) ) {
									$lp_subscription_status = LP_Subscription_Manager::STATUS_TRIAL;
								}
							}

							// 2. Check not trial
							if ( empty( $lp_subscription_status ) && $regularCycle ) {
								$lp_subscription_status = LP_Subscription_Manager::STATUS_ACTIVATED;
								// Key tmp for check if payment success and has key will set lp_subscription_status = value of subscription_status_tmp
								update_post_meta( $lp_order->get_id(), self::META_SUBSCRIPTION_STATUS_TMP, $lp_subscription_status );
							}
						}
					}
					break;
				case 'BILLING.SUBSCRIPTION.CANCELLED':
					$lp_subscription_status = LP_Subscription_Manager::STATUS_CANCELLED;
					break;
				case 'BILLING.SUBSCRIPTION.SUSPENDED':
					$lp_subscription_status = LP_Subscription_Manager::STATUS_SUSPENDED;
					break;
				case 'BILLING.SUBSCRIPTION.EXPIRED':
					$lp_subscription_status = LP_Subscription_Manager::STATUS_EXPIRED;
					break;
				default:
					$lp_subscription_status = $event_type;
					break;
			}

			if ( empty( $lp_subscription_status ) ) {
				error_log( 'Empty subscription status: ' . json_encode( $webhook_data ) );
				throw new Exception( __( 'LP PayPal get status from webhook is invalid.', 'learnpress' ), 400 );
			}

			$webhook_data['lp_subscription_status'] = $lp_subscription_status;

			LP_Debug::log_to_comment(
				'LP PayPal normalize subscription data' . json_encode( $webhook_data, JSON_UNESCAPED_UNICODE )
			);
		}

		/**
		 * Capture PayPal setup-fee or renewal payment data.
		 *
		 * This method handles `PAYMENT.SALE.COMPLETED` webhook payloads whose resource
		 * type is `sale`. It verifies that the PayPal billing agreement matches the
		 * subscription id stored on the LearnPress parent order, stores the successful
		 * payment payload for first-payment coordination when the parent order is not
		 * completed yet, and appends normalized amount/currency values to `$webhook_data`.
		 *
		 * @param LP_Order $lp_order
		 * @param array $webhook_data
		 *
		 * @return true|void
		 * @throws Exception When the sale resource is invalid or does not match the stored subscription id.
		 * @since 4.3.7
		 * @version 1.0.0
		 */
		public function capture_payment_setup_fee_or_renewal( $lp_order, array &$webhook_data ) {
			$resource_type = $webhook_data['resource_type'] ?? '';
			$event_type    = $webhook_data['event_type'] ?? '';
			if ( 'sale' !== $resource_type || 'PAYMENT.SALE.COMPLETED' !== $event_type ) {
				return;
			}

			if ( empty( $webhook_data['resource'] ) || ! is_array( $webhook_data['resource'] ) ) {
				throw new Exception( __( 'PayPal setup fee/renew payment resource is invalid.', 'learnpress' ), 400 );
			}

			$resource             = $webhook_data['resource'];
			$billing_agreement_id = $resource['billing_agreement_id'] ?? '';
			if ( empty( $billing_agreement_id ) ) {
				throw new Exception( __( 'PayPal billing agreement ID is invalid.', 'learnpress' ), 400 );
			}

			// Verify subscription id receiver sample with subscription id on LP Order
			$subscription_id = get_post_meta( $lp_order->get_id(), self::META_SUBSCRIPTION_ID, true );
			if ( $billing_agreement_id !== $subscription_id ) {
				throw new Exception( __( 'PayPal billing subscription not same with subscription on LP Order.', 'learnpress' ), 400 );
			}

			// Save payment data to combine with subscription status defined by LP later, not for renewal
			if ( ! $lp_order->is_completed() ) {
				update_post_meta(
					$lp_order->get_id(),
					self::META_SUBSCRIPTION_DATA_PAYMENT_SUCCESS,
					wp_json_encode( $webhook_data, JSON_UNESCAPED_UNICODE )
				);
			}

			$webhook_data['lp_subscription_amount']   = $resource['amount']['total'] ?? 0;
			$webhook_data['lp_subscription_currency'] = $resource['amount']['currency'] ?? '';

			// Add note to order
			$lp_order->add_note(
				sprintf(
					'LP Order: %s %s: %s. %s',
					sprintf(
						'<a href="%s">%s</a>',
						$lp_order->get_edit_link(),
						$lp_order->get_order_number()
					),
					__( 'PayPal payment setup fee/renew success created at', 'learnpress' ),
					$webhook_data['create_time'] ?? '',
					sprintf(
						__( 'Subscription ID: %s', 'learnpress' ),
						$subscription_id
					)
				)
			);

			return true;
		}

		/**
		 * Capture PayPal billing subscription creation webhook.
		 *
		 * `BILLING.SUBSCRIPTION.CREATED` is acknowledged by adding an order note and
		 * returning true so the caller can stop processing. The actual order completion
		 * and subscription status update happen later when activation/payment webhooks
		 * are received.
		 *
		 * @param LP_Order $lp_order
		 * @param array $webhook_data
		 *
		 * @return bool
		 * @since 4.3.7
		 * @version 1.0.0
		 */
		public function capture_billing_subscription_create( $lp_order, array $webhook_data ): bool {
			// If billing created, create subscription
			$event_type = $webhook_data['event_type'] ?? '';
			if ( $event_type === 'BILLING.SUBSCRIPTION.CREATED' ) {
				if ( $lp_order ) {
					// Add note to order
					$lp_order->add_note(
						sprintf(
							'LP Order: %s %s: %s. %s',
							sprintf(
								'<a href="%s">%s</a>',
								$lp_order->get_edit_link(),
								$lp_order->get_order_number()
							),
							__( 'PayPal subscription created at', 'learnpress' ),
							$webhook_data['create_time'] ?? '',
							sprintf(
								__( 'Link payment: %s', 'learnpress' ),
								$webhook_data['resource']['links'][0]['href'] ?? ''
							)
						)
					);
				}

				return true;
			}

			return false;
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
