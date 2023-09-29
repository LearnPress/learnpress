<?php
/**
 * Class Paypal Payment gateway.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @since   3.0.0
 * @version 3.0.1
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
		protected $paypal_nvp_api_live_url = 'https://api-3t.paypal.com/nvp';

		/**
		 * @var null|string
		 */
		protected $paypal_sandbox_url = 'https://www.sandbox.paypal.com/';
		/**
		 * @var null|string
		 */
		protected $paypal_payment_sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		/**
		 * @var null
		 */
		protected $paypal_nvp_api_sandbox_url = 'https://api-3t.sandbox.paypal.com/nvp';

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
		 * @var string
		 */
		protected $method = '';

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
		protected $paypal_nvp_api_url = null;

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
		 * LP_Gateway_Paypal constructor.
		 */
		public function __construct() {
			$this->id = 'paypal';

			$this->method_title       = esc_html__( 'PayPal', 'learnpress' );
			$this->method_description = esc_html__( 'Make a payment via Paypal.', 'learnpress' );
			$this->icon               = LP_PLUGIN_URL . 'assets/images/paypal-logo-preview.png';

			$this->title       = esc_html__( 'PayPal', 'learnpress' );
			$this->description = esc_html__( 'Pay with PayPal', 'learnpress' );

			// get settings
			$this->settings = LP_Settings::instance()->get_group( 'paypal', '' );

			$this->enabled = $this->settings->get( 'enable', 'no' );

			$this->init();

			parent::__construct();
		}

		/**
		 * Init.
		 */
		public function init() {
			if ( $this->is_enabled() ) {
				if ( $this->settings->get( 'paypal_sandbox', 'no' ) === 'no' ) {
					$this->paypal_url         = $this->paypal_live_url;
					$this->paypal_payment_url = $this->paypal_payment_live_url;
					$this->paypal_nvp_api_url = $this->paypal_nvp_api_live_url;
					$this->paypal_email       = $this->settings->get( 'paypal_email' );
					$this->api_url            = $this->api_live_url; //use for paypal rest api
				} else {
					$this->paypal_url         = $this->paypal_sandbox_url;
					$this->paypal_payment_url = $this->paypal_payment_sandbox_url;
					$this->paypal_nvp_api_url = $this->paypal_nvp_api_sandbox_url;
					$this->paypal_email       = $this->settings->get( 'paypal_sandbox_email' );
					$this->api_url            = $this->api_sandbox_url; //use for paypal rest api
				}
				// Use Paypal rest api
				if ( $this->settings->get( 'use_paypal_rest' ) == 'yes' ) {
					$this->client_id     = $this->settings->get( 'app_client_id' );
					$this->client_secret = $this->settings->get( 'app_client_secret' );
				}
			}

			add_filter( 'learn-press/payment-gateway/' . $this->id . '/available', array( $this, 'paypal_available' ), 10, 2 );
		}

		/**
		 * Check payment gateway available.
		 *
		 * @param bool $default
		 * @param $payment
		 *
		 * @return bool
		 */
		public function paypal_available( bool $default, $payment ): bool {
			// Empty live email and Sandbox mode also disabled
			if ( $this->settings->get( 'paypal_sandbox' ) != 'yes' && ! $this->settings->get( 'paypal_email' ) ) {
				return false;
			}

			// Enable Sandbox mode but it's email is empty
			if ( ! $this->settings->get( 'paypal_sandbox_email' ) && $this->settings->get( 'paypal_sandbox' ) == 'yes' ) {
				return false;
			}

			return $default;
		}

		/**
		 * https://developer.paypal.com/api/nvp-soap/ipn/IPNImplementation/#link-ipnlistenerrequestresponseflow
		 * Check validate IPN.
		 *
		 * @return bool
		 */
		public function validate_ipn():bool {
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
			$paypal_payment_url = '';

			$order = new LP_Order( $order_id );
			if ( $this->settings->get( 'use_paypal_rest' ) == 'no' ) {
				$paypal_args        = $this->get_paypal_args( $order );
				$paypal_payment_url = $this->paypal_url . '?' . http_build_query( $paypal_args );
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
			$data_token     = json_decode( $data_token_str );
			if ( isset( $data->error ) ) {
				throw new Exception( $data->error_description );
			}

			LP_Settings::update_option( 'paypal_token', $data_token_str );

			return $data_token;
		}

		public function get_order_args( LP_Order $order ) {
			$lp_cart    = LearnPress::instance()->get_cart();
			$cart_total = $lp_cart->calculate_totals();
			$order_id   = $order->get_id();
			$return_url = esc_url_raw(
				add_query_arg( 'paypay_express_checkout', 1, $this->get_return_url( $order ) )
			);
			$data       = [
				'intent'         => 'CAPTURE',
				'purchase_units' => [
					[
						'amount'    => [
							'currency_code' => learn_press_get_currency(),
							'value'         => $cart_total->total,
						],
						'custom_id' => $order_id,
					],
				],
				'payment_source' => [
					'paypal' => [
						'experience_context' => [
							'payment_method_preference' => 'UNRESTRICTED',
							'brand_name'                => get_bloginfo(),
							'landing_page'              => 'LOGIN',
							'user_action'               => 'PAY_NOW',
							'return_url'                => $return_url,
							'cancel_url'                => esc_url_raw( learn_press_is_enable_cart() ? learn_press_get_page_link( 'cart' ) : get_home_url() ),
						],
					],
				],
			];
			return apply_filters( 'learn-press/paypal-rest/args', $data );
		}

		/**
		 * Create Order PayPal and get checkout url
		 * @param LP_Order $order [description]
		 * @param object $data_token { scope, access_token, token_type, app_id, expires_in, nonce }
		 * @throws Exception
		 */
		public function create_payment_url( LP_Order $order, $data_token ) {
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

			$result = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $result->error ) ) {
				throw new Exception( $result->error_description );
			}

			if ( empty( $result->links ) ) {
				throw new Exception( __( 'Invalid Paypal checkout url', 'learnpress' ) );
			}

			foreach ( $result->links as $link ) {
				if ( $link->rel == 'payer-action' ) {
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
		 * check paypal transaction status use paypal oauth2 token
		 * @param  [$transaction_id string paypal transaction id]
		 * @return [void]
		 * @throws Exception
		 */
		public function check_transaction_status( $transaction_id ) {
			$data_token_str = LP_Settings::get_option( 'paypal_token' );
			$data_token     = json_decode( $data_token_str );
			if ( ! isset( $data_token->access_token ) || ! isset( $data_token->token_type ) ) {
				throw new Exception( __( 'Invalid Paypal access token', 'learnpress' ) );
			}

			$response = wp_remote_post(
				$this->api_url . 'v2/checkout/orders/' . $transaction_id . '/capture',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => $data_token->token_type . ' ' . $data_token->access_token,
					),
					'timeout' => 60,
				)
			);

			if ( $response['response']['code'] === 201 ) {
				$body        = wp_remote_retrieve_body( $response );
				$transaction = json_decode( $body );
				if ( $transaction->status == 'COMPLETED' ) {
					$order_id = $transaction->purchase_units[0]->payments->captures[0]->custom_id;
					$lp_order = learn_press_get_order( $order_id );
					$lp_order->update_status( LP_ORDER_COMPLETED );
				}
			}
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
