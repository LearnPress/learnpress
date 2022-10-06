<?php
/**
 * Class Paypal Payment gateway.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @since   3.0.0
 * @version 3.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Gateway_Paypal' ) ) {
	/**
	 * Class LP_Gateway_Paypal.
	 */
	class LP_Gateway_Paypal extends LP_Gateway_Abstract {
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
		 * @var array
		 */
		//protected $line_items = array();

		/**
		 * LP_Gateway_Paypal constructor.
		 */
		public function __construct() {
			$this->id = 'paypal';

			$this->method_title       = esc_html__( 'PayPal', 'learnpress' );
			$this->method_description = esc_html__( 'Make a payment via Paypal.', 'learnpress' );
			$this->icon               = '';

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
				} else {
					$this->paypal_url         = $this->paypal_sandbox_url;
					$this->paypal_payment_url = $this->paypal_payment_sandbox_url;
					$this->paypal_nvp_api_url = $this->paypal_nvp_api_sandbox_url;
					$this->paypal_email       = $this->settings->get( 'paypal_sandbox_email' );
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
			if ( ! $this->is_enabled() ) {
				return false;
			}

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
		 * Handle a completed payment
		 *
		 * @param LP_Order $order
		 * @param array    $request
		 */
		/*protected function payment_status_completed( $order, $request ) {
			if ( $order->has_status( 'completed' ) ) {
				exit;
			}

			if ( 'completed' === $request['payment_status'] ) {
				$this->payment_complete( $order, ( ! empty( $request['txn_id'] ) ? $request['txn_id'] : '' ), __( 'IPN payment completed', 'learnpress' ) );
				// save paypal fee
				if ( ! empty( $request['mc_fee'] ) ) {
					update_post_meta( $order->get_id(), '_transaction_fee', $request['mc_fee'] );
				}
			} else {
			}
		}*/

		/**
		 * Handle a pending payment
		 *
		 * @param LP_Order
		 * @param Paypal IPN params
		 */
		/*protected function payment_status_pending( $order, $request ) {
			$this->payment_status_completed( $order, $request );
		}*/

		/**
		 * @param LP_Order
		 * @param string   $txn_id
		 * @param string   $note - not use
		 */
		/*public function payment_complete( $order, $txn_id = '', $note = '' ) {
			$order->payment_complete( $txn_id );
		}*/

		/**
		 * Handle payment.
		 *
		 * @param int $order_id
		 *
		 * @return array
		 */
		public function process_payment( $order_id = 0 ): array {
			$paypal_payment_url = '';

			try {
				$order       = new LP_Order( $order_id );
				$paypal_args = $this->get_paypal_args( $order );

				$paypal_payment_url = $this->paypal_url . '?' . http_build_query( $paypal_args );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return array(
				'result'   => ! empty( $paypal_payment_url ) ? 'success' : 'fail',
				'redirect' => $paypal_payment_url,
			);
		}

		/**
		 * Prepare args to send to PayPal
		 *
		 * @param LP_Order $order
		 *
		 * @return array
		 */
		public function get_paypal_args( LP_Order $order ): array {
			$checkout = LearnPress::instance()->checkout();
			$custom   = array(
				'order_id'       => $order->get_id(),
				'order_key'      => $order->get_order_key(),
				'checkout_email' => $checkout->get_checkout_email(),
			);

			// Item
			$items    = LearnPress::instance()->get_cart()->get_items();
			$item_arg = [
				'item_name_1' => $order->get_order_number(),
				'quantity_1'  => 0,
				'amount_1'    => 0,
			];
			if ( $items ) {
				foreach ( $items as $item ) {
					$item_arg['quantity_1'] += $item['quantity'];
					$item_arg['amount_1']   += $item['total'];
				}
			}

			$args = array_merge(
				array(
					'cmd'           => '_cart',
					'business'      => $this->paypal_email,
					'no_note'       => 1,
					'currency_code' => learn_press_get_currency(),
					'charset'       => 'utf-8',
					'rm'            => is_ssl() ? 2 : 1,
					'upload'        => 1,
					'return'        => esc_url_raw( $this->get_return_url( $order ) ),
					'cancel_return' => esc_url_raw( learn_press_is_enable_cart() ? learn_press_get_page_link( 'cart' ) : get_home_url() /* SITE_URL */ ),
					'bn'            => 'LearnPress_Cart',
					'custom'        => json_encode( $custom ),
					'notify_url'    => get_home_url() . '/?paypal_notify=1',
				),
				$item_arg
			);

			return apply_filters( 'learn-press/paypal/args', $args );
		}

		/**
		 * Settings form fields for this gateway
		 *
		 * @return array
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/gateway-payment/paypal/settings',
				array(
					array(
						'type' => 'title',
					),
					array(
						'title'   => esc_html__( 'Enable/Disable', 'learnpress' ),
						'id'      => '[enable]',
						'default' => 'no',
						'type'    => 'checkbox',
						'desc'    => esc_html__( 'Enable PayPal Standard', 'learnpress' ),
					),
					array(
						'title' => esc_html__( 'PayPal email', 'learnpress' ),
						'id'    => '[paypal_email]',
						'type'  => 'text',
					),
					array(
						'title'   => esc_html__( 'Sandbox mode', 'learnpress' ),
						'id'      => '[paypal_sandbox]',
						'default' => 'no',
						'type'    => 'checkbox',
						'desc'    => esc_html__( 'Enable PayPal sandbox', 'learnpress' ),
					),
					array(
						'title' => esc_html__( 'Sandbox email address', 'learnpress' ),
						'id'    => '[paypal_sandbox_email]',
						'type'  => 'text',
					),
					array(
						'type' => 'sectionend',
					),
				)
			);
		}

		/**
		 * Icon for the gateway
		 *
		 * @return string
		 */
		public function get_icon() {
			if ( empty( $this->icon ) ) {
				$this->icon = LearnPress::instance()->plugin_url( 'assets/images/paypal-logo-preview.png' );
			}

			return parent::get_icon();
		}
	}
}
