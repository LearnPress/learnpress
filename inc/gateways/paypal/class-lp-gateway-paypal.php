<?php
/**
 * Class Paypal Payment gateway.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @since   3.0.0
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
		 * @var null|string
		 */
		protected $paypal_live_url = null;

		/**
		 * @var null|string
		 */
		protected $paypal_sandbox_url = null;

		/**
		 * @var null|string
		 */
		protected $paypal_payment_live_url = null;

		/**
		 * @var null|string
		 */
		protected $paypal_payment_sandbox_url = null;

		/**
		 * @var null|string
		 */
		protected $paypal_nvp_api_live_url = null;

		/**
		 * @var null
		 */
		protected $paypal_vnp_api_sandbox_url = null;

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
		protected $line_items = array();

		/**
		 * LP_Gateway_Paypal constructor.
		 */
		public function __construct() {
			$this->id = 'paypal';

			$this->method_title       = esc_html__( 'PayPal', 'learnpress' );
			$this->method_description = esc_html__( 'Make payment via Paypal.', 'learnpress' );
			$this->icon               = '';

			$this->title       = esc_html__( 'PayPal', 'learnpress' );
			$this->description = esc_html__( 'Pay with PayPal', 'learnpress' );

			// live
			$this->paypal_live_url         = 'https://www.paypal.com/';
			$this->paypal_payment_live_url = 'https://www.paypal.com/cgi-bin/webscr';
			$this->paypal_nvp_api_live_url = 'https://api-3t.paypal.com/nvp';

			// sandbox
			$this->paypal_sandbox_url         = 'https://www.sandbox.paypal.com/';
			$this->paypal_payment_sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			$this->paypal_nvp_api_sandbox_url = 'https://api-3t.sandbox.paypal.com/nvp';

			// get settings
			$this->settings = LP()->settings()->get_group( 'paypal', '' );

			$this->enabled = $this->settings->get( 'enable' );

			$this->init();

			parent::__construct();
		}

		/**
		 * Init.
		 */
		public function init() {
			if ( $this->is_enabled() ) {
				if ( $this->settings->get( 'paypal_sandbox' ) == 'no' ) {
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

				if ( did_action( 'init' ) ) {
					$this->register_web_hook();
					$this->parse_ipn();
				} else {
					add_action( 'init', array( $this, 'register_web_hook' ) );
					add_action( 'init', array( $this, 'parse_ipn' ) );
				}
				add_action( 'learn_press_web_hook_learn_press_paypal', array( $this, 'web_hook_process_paypal' ) );
			}

			add_filter( 'learn-press/payment-gateway/' . $this->id . '/available', array( $this, 'paypal_available' ), 10, 2 );
		}

		public function register_web_hook() {
			learn_press_register_web_hook( 'paypal', 'learn_press_paypal' );
		}

		public function validate_ipn() {
			$validate_ipn  = array( 'cmd' => '_notify-validate' );
			$validate_ipn += wp_unslash( $_POST );

			// Send back post vars to paypal
			$params = array(
				'body'        => $validate_ipn,
				'timeout'     => 60,
				'httpversion' => '1.1',
				'compress'    => false,
				'decompress'  => false,
			);

			// Post back to get a response
			$response = wp_safe_remote_post( ! empty( $_REQUEST['test_ipn'] ) ? $this->paypal_payment_sandbox_url : $this->paypal_payment_live_url, $params );
			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				$body = wp_remote_retrieve_body( $response );
				if ( 'VERIFIED' === $body ) {
					return true;
				}
			}

			return false;
		}

		public function web_hook_process_paypal( $request ) {
			if ( $this->validate_ipn() ) {
				if ( ! empty( $request['custom'] ) && ( $order = $this->get_order( $request['custom'] ) ) ) {
					$request['payment_status'] = strtolower( $request['payment_status'] );

					if ( isset( $request['test_ipn'] ) && 1 == $request['test_ipn'] && 'pending' == $request['payment_status'] ) {
						$request['payment_status'] = 'completed';
					}

					$method   = 'payment_status_' . $request['payment_status'];
					$callback = array( $this, $method );
					if ( is_callable( $callback ) ) {
						call_user_func( $callback, $order, $request );
					}
				}
			}
		}

		public function payment_method_name( $slug ) {
			return $slug == 'paypal-standard' ? 'Paypal' : $slug;
		}

		/**
		 * Check payment gateway available.
		 *
		 * @param $default
		 * @param $payment
		 *
		 * @return bool
		 */
		public function paypal_available( $default, $payment ) {

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

		public function get_order( $raw_custom ) {
			$raw_custom = stripslashes( $raw_custom );
			if ( ( $custom = json_decode( $raw_custom ) ) && is_object( $custom ) ) {
				$order_id  = $custom->order_id;
				$order_key = $custom->order_key;

				// Fallback to serialized data if safe. This is @deprecated in 2.3.11
			} elseif ( preg_match( '/^a:2:{/', $raw_custom ) && ! preg_match( '/[CO]:\+?[0-9]+:"/', $raw_custom ) && ( $custom = LP_Helper::maybe_unserialize( $raw_custom ) ) ) {
				$order_id  = $custom[0];
				$order_key = $custom[1];

				// Nothing was found
			} else {
				_e( 'Error: order ID and key were not found in "custom".' );

				return false;
			}

			$order = new LP_Order( $order_id );

			if ( ! $order || $order->order_key !== $order_key ) {
				printf( __( 'Error: Order Keys do not match %1$s and %2$s.' ), $order->order_key, $order_key );

				return false;
			}

			return $order;
		}

		/**
		 * Retrieve order by paypal txn_id
		 *
		 * @param $txn_id
		 *
		 * @return int
		 * @editor tungnx
		 * @modify 4.1.2
		 * @reason comment - not user
		 */
		/*public function get_order_id( $txn_id ) {

			$args = array(
				'meta_key'    => '_learn_press_transaction_method_id',
				'meta_value'  => $txn_id,
				'numberposts' => 1, // we should only have one, so limit to 1
			);

			$orders = learn_press_get_orders( $args );
			if ( $orders ) {
				foreach ( $orders as $order ) {
					return $order->ID;
				}
			}

			return 0;
		}*/

		public function parse_ipn() {
			if ( ! isset( $_REQUEST['ipn'] ) ) {
				return;
			}
		}

		/**
		 * @editor tungnx
		 * @modify 4.1.2
		 * @reason comment - not user
		 */
//		public function process_order_paypal_standard() {
//
//			if ( ! empty( $_REQUEST['learn-press-transaction-method'] ) && ( 'paypal-standard' == $_REQUEST['learn-press-transaction-method'] ) ) {
//				// if we have a paypal-nonce in $_REQUEST that meaning user has clicked go back to our site after finished the transaction
//				// so, create a new order
//				if ( ! empty( $_REQUEST['paypal-nonce'] ) && wp_verify_nonce( $_REQUEST['paypal-nonce'], 'learn-press-paypal-nonce' ) ) {
//					if ( ! empty( $_REQUEST['tx'] ) ) {
//						$transaction_id = $_REQUEST['tx'];
//					} elseif ( ! empty( $_REQUEST['txn_id'] ) ) {
//						$transaction_id = $_REQUEST['txn_id'];
//					} else {
//						$transaction_id = null;
//					}
//
//					if ( ! empty( $_REQUEST['cm'] ) ) {
//						$transient_transaction_id = $_REQUEST['cm'];
//					} elseif ( ! empty( $_REQUEST['custom'] ) ) {
//						$transient_transaction_id = $_REQUEST['custom'];
//					} else {
//						$transient_transaction_id = null;
//					}
//
//					if ( ! empty( $_REQUEST['st'] ) ) {
//						$transaction_status = $_REQUEST['st'];
//					} elseif ( ! empty( $_REQUEST['payment_status'] ) ) {
//						$transaction_status = $_REQUEST['payment_status'];
//					} else {
//						$transaction_status = null;
//					}
//
//					if ( ! empty( $transaction_id ) && ! empty( $transient_transaction_id ) && ! empty( $transaction_status ) ) {
//						$user = learn_press_get_current_user();
//
//						try {
//							// If the transient still exists, delete it and add the official transaction
//							if ( $transaction_object = learn_press_get_transient_transaction( 'lpps', $transient_transaction_id ) ) {
//
//								learn_press_delete_transient_transaction( 'lpps', $transient_transaction_id );
//								$order_id = $this->get_order_id( $transaction_id );
//								$order_id = learn_press_add_transaction(
//									array(
//										'order_id'  => $order_id,
//										'method'    => 'paypal-standard',
//										'method_id' => $transaction_id,
//										'status'    => $transaction_status,
//										'user_id'   => $user->get_id(),
//										'transaction_object' => $transaction_object['transaction_object'],
//									)
//								);
//
//								wp_redirect( ( $confirm_page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && get_post( $confirm_page_id ) ? learn_press_get_order_confirm_url( $order_id ) : get_home_url() /* SITE_URL */ );
//								die();
//							}
//						} catch ( Exception $e ) {
//							return false;
//
//						}
//					} elseif ( is_null( $transaction_id ) && is_null( $transient_transaction_id ) && is_null( $transaction_status ) ) {
//					}
//				}
//			}
//
//			wp_redirect( get_home_url() );
//			die();
//		}

		/**
		 * Handle a completed payment
		 *
		 * @param LP_Order $order
		 * @param array    $request
		 */
		protected function payment_status_completed( $order, $request ) {
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
		}

		/**
		 * Handle a pending payment
		 *
		 * @param LP_Order
		 * @param Paypal IPN params
		 */
		protected function payment_status_pending( $order, $request ) {
			$this->payment_status_completed( $order, $request );
		}

		/**
		 * @param LP_Order
		 * @param string   $txn_id
		 * @param string   $note - not use
		 */
		public function payment_complete( $order, $txn_id = '', $note = '' ) {
			$order->payment_complete( $txn_id );
		}

		public function process_payment( $order ) {
			$redirect = $this->get_request_url( $order );

			$json = array(
				'result'   => $redirect ? 'success' : 'fail',
				'redirect' => $redirect,
			);

			return $json;
		}

		protected function prepare_line_items() {
			$this->line_items = array();
			if ( $items = LP()->get_cart()->get_items() ) {
				foreach ( $items as $item ) {
					$this->add_line_item( get_the_title( $item['item_id'] ), $item['quantity'], $item['total'] );
				}
			}
		}

		protected function add_line_item( $item_name, $quantity = 1, $amount = 0, $item_number = '' ) {
			$index = ( sizeof( $this->line_items ) / 4 ) + 1;

			if ( $amount < 0 || $index > 9 ) {
				return false;
			}

			$this->line_items[ 'item_name_' . $index ]   = html_entity_decode( $item_name ? $item_name : __( 'Item', 'learnpress' ), ENT_NOQUOTES, 'UTF-8' );
			$this->line_items[ 'quantity_' . $index ]    = $quantity;
			$this->line_items[ 'amount_' . $index ]      = $amount;
			$this->line_items[ 'item_number_' . $index ] = $item_number;

			return true;
		}

		public function get_item_lines() {
			return $this->line_items;
		}

		public function get_request_url( $order_id ) {

			$order = new LP_Order( $order_id );
			$query = $this->get_paypal_args( $order );

			$paypal_payment_url = $this->paypal_url . '?' . http_build_query( $query );

			return $paypal_payment_url;
		}

		/**
		 * @param LP_Order $order
		 *
		 * @return array
		 */
		public function get_paypal_args( $order ) {
			$checkout = LP()->checkout();
			$this->prepare_line_items();
			$custom = array(
				'order_id'       => $order->get_id(),
				'order_key'      => $order->get_order_key(),
				'checkout_email' => $checkout->get_checkout_email(),
			);

			$args = array_merge(
				array(
					'cmd'           => '_cart',
					'business'      => $this->paypal_email,
					'no_note'       => 1,
					'currency_code' => learn_press_get_currency(),
					'charset'       => 'utf-8',
					'rm'            => is_ssl() ? 2 : 1,
					'upload'        => 1,
					'return'        => esc_url( $this->get_return_url( $order ) ),
					'cancel_return' => esc_url( learn_press_is_enable_cart() ? learn_press_get_page_link( 'cart' ) : get_home_url() /* SITE_URL */ ),
					'bn'            => 'LearnPress_Cart',
					'custom'        => json_encode( $custom ),
					'notify_url'    => get_home_url() . '/?' . learn_press_get_web_hook( 'paypal' ) . '=1',
				),
				$this->get_item_lines()
			);

			$args = apply_filters( 'learn_press_paypal_args', $args );

			return apply_filters( 'learn-press/paypal/args', $args );
		}

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

		public function get_icon() {
			if ( empty( $this->icon ) ) {
				$this->icon = LP()->plugin_url( 'assets/images/paypal-logo-preview.png' );
			}

			return parent::get_icon();
		}
	}
}
