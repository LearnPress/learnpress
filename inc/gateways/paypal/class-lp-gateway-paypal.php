<?php
/**
 * Class LP_Gateway_Paypal
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

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
	protected $paypal_email = null;

	/**
	 * @var null
	 */
	protected $settings = null;

	/**
	 * @var array
	 */
	protected $line_items = array();

	/**
	 *
	 */
	public function __construct() {
		$this->id = 'paypal';

		$this->method_title       = 'Paypal';
		$this->method_description = 'Make payment via Paypal';

		$this->title       = 'Paypal';
		$this->description = __( 'Pay with Paypal', 'learnpress' );

		// live
		$this->paypal_live_url         = 'https://www.paypal.com/';
		$this->paypal_payment_live_url = 'https://www.paypal.com/cgi-bin/webscr';
		$this->paypal_nvp_api_live_url = 'https://api-3t.paypal.com/nvp';

		// sandbox
		$this->paypal_sandbox_url         = 'https://www.sandbox.paypal.com/';
		$this->paypal_payment_sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		$this->paypal_nvp_api_sandbox_url = 'https://api-3t.sandbox.paypal.com/nvp';


		$this->settings = LP()->settings;

		$this->init();
		parent::__construct();
	}

	public function init() {
		if ( $this->settings->get( 'paypal_enable' ) ) {
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

			//add_action( 'learn_press_take_course_paypal', array( $this, 'process_payment' ) );
			//add_action( 'learn_press_do_transaction_paypal-standard-secure', array( $this, 'process_order_paypal_standard_secure' ) );
			//add_action( 'learn_press_do_transaction_paypal-standard', array( $this, 'process_order_paypal_standard' ) );
			//add_action('learn_press_update_order_status', array($this, 'remove_transient'), 5, 2);
			//add_action('learn_press_payment_gateway_form_paypal', array($this, 'payment_form'));

			add_action( 'learn_press_web_hook_learn_press_paypal', array( $this, 'web_hook_process_paypal' ) );

		}


		if ( is_admin() ) {
			ob_start();
			?>
			<script>
				$('#learn_press_paypal_enable').change(function () {
					var $rows = $(this).closest('tr').siblings('tr');
					if (this.checked) {
						$rows.css("display", "");
					} else {
						$rows.css("display", "none");
					}
				}).trigger('change');
			</script>
			<?php
			$script = ob_get_clean();
			$script = preg_replace( '!</?script>!', '', $script );
			learn_press_enqueue_script( $script );
		}
		add_filter( 'learn_press_payment_gateway_available_paypal', array( $this, 'paypal_available' ), 10, 2 );
	}

	public function register_web_hook() {
		learn_press_register_web_hook( 'paypal', 'learn_press_paypal' );
	}

	public function validate_ipn() {
		$validate_ipn = array( 'cmd' => '_notify-validate' );
		$validate_ipn += wp_unslash( $_POST );

		// Send back post vars to paypal
		$params = array(
			'body'        => $validate_ipn,
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false
		);

		// Post back to get a response
		$response = wp_safe_remote_post( !empty( $_REQUEST['test_ipn'] ) ? $this->paypal_payment_sandbox_url : $this->paypal_payment_live_url, $params );
		if ( !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$body = wp_remote_retrieve_body( $response );
			if ( 'VERIFIED' === $body ) {
				return true;
			}
		}
		return false;
	}

	public function web_hook_process_paypal( $request ) {
		if ( $this->validate_ipn() ) {
			if ( !empty( $request['custom'] ) && ( $order = $this->get_order( $request['custom'] ) ) ) {
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

	public function paypal_available( $a, $b ) {
		return LP()->settings->get( 'paypal_enable' ) == 'yes';
	}

	public function get_order( $raw_custom ) {
		$raw_custom = stripslashes( $raw_custom );
		if ( ( $custom = json_decode( $raw_custom ) ) && is_object( $custom ) ) {
			$order_id  = $custom->order_id;
			$order_key = $custom->order_key;

			// Fallback to serialized data if safe. This is @deprecated in 2.3.11
		} elseif ( preg_match( '/^a:2:{/', $raw_custom ) && !preg_match( '/[CO]:\+?[0-9]+:"/', $raw_custom ) && ( $custom = maybe_unserialize( $raw_custom ) ) ) {
			$order_id  = $custom[0];
			$order_key = $custom[1];

			// Nothing was found
		} else {
			_e( 'Error: order ID and key were not found in "custom".' );
			return false;
		}

		if ( !$order = LP_Order::instance( $order_id ) ) {
			//$order_id = hb_get_order_id_by_key( $order_key );
			//$order    = LP_Order::instance( $order_id );
		}

		if ( !$order || $order->order_key !== $order_key ) {
			printf( __( 'Error: Order Keys do not match %s and %s.' ), $order->order_key, $order_key );
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
	 */
	public function get_order_id( $txn_id ) {

		$args = array(
			'meta_key'    => '_learn_press_transaction_method_id',
			'meta_value'  => $txn_id,
			'numberposts' => 1, //we should only have one, so limit to 1
		);

		$orders = learn_press_get_orders( $args );
		print_r( $orders );
		if ( $orders ) foreach ( $orders as $order ) {
			return $order->ID;
		}
		return 0;
	}

	public function parse_ipn() {
		if ( !isset( $_REQUEST['ipn'] ) ) {
			return;
		}
		require_once( 'paypal-ipn/ipn.php' );
	}

	public function get_payment_form() {
		$output = $this->get_description();;
		$error  = false;
		if ( $this->settings->get( 'paypal_sandbox' ) == 'yes' ) {
			if ( false == is_email( $this->settings->get( 'paypal_sandbox_email' ) ) ) {
				$error = true;
			}
		} else {
			if ( false == is_email( $this->settings->get( 'paypal_email' ) ) ) {
				$error = true;
			}
		}
		if ( $error ) {
			$output .= learn_press_get_message( __( 'Paypal settings is not setup', 'learnpress' ), 'error' );
			$output .= '<input type="hidden" name="payment_method_paypal-error" value="yes" />';
		}
		return $output;
	}

	public function process_order_paypal_standard() {

		if ( !empty( $_REQUEST['learn-press-transaction-method'] ) && ( 'paypal-standard' == $_REQUEST['learn-press-transaction-method'] ) ) {
			// if we have a paypal-nonce in $_REQUEST that meaning user has clicked go back to our site after finished the transaction
			// so, create a new order
			if ( !empty( $_REQUEST['paypal-nonce'] ) && wp_verify_nonce( $_REQUEST['paypal-nonce'], 'learn-press-paypal-nonce' ) ) {
				if ( !empty( $_REQUEST['tx'] ) ) //if PDT is enabled
					$transaction_id = $_REQUEST['tx'];
				else if ( !empty( $_REQUEST['txn_id'] ) ) //if PDT is not enabled
					$transaction_id = $_REQUEST['txn_id'];
				else
					$transaction_id = NULL;

				if ( !empty( $_REQUEST['cm'] ) )
					$transient_transaction_id = $_REQUEST['cm'];
				else if ( !empty( $_REQUEST['custom'] ) )
					$transient_transaction_id = $_REQUEST['custom'];
				else
					$transient_transaction_id = NULL;

				if ( !empty( $_REQUEST['st'] ) ) //if PDT is enabled
					$transaction_status = $_REQUEST['st'];
				else if ( !empty( $_REQUEST['payment_status'] ) ) //if PDT is not enabled
					$transaction_status = $_REQUEST['payment_status'];
				else
					$transaction_status = NULL;


				if ( !empty( $transaction_id ) && !empty( $transient_transaction_id ) && !empty( $transaction_status ) ) {
					$user = learn_press_get_current_user();


					try {
						//If the transient still exists, delete it and add the official transaction
						if ( $transaction_object = learn_press_get_transient_transaction( 'lpps', $transient_transaction_id ) ) {

							learn_press_delete_transient_transaction( 'lpps', $transient_transaction_id );
							$order_id = $this->get_order_id( $transaction_id );
							$order_id = learn_press_add_transaction(
								array(
									'order_id'           => $order_id,
									'method'             => 'paypal-standard',
									'method_id'          => $transaction_id,
									'status'             => $transaction_status,
									'user_id'            => $user->ID,
									'transaction_object' => $transaction_object['transaction_object']
								)
							);

							wp_redirect( ( $confirm_page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && get_post( $confirm_page_id ) ? learn_press_get_order_confirm_url( $order_id ) : get_site_url() );
							die();
						}

					} catch ( Exception $e ) {
						return false;

					}

				} else if ( is_null( $transaction_id ) && is_null( $transient_transaction_id ) && is_null( $transaction_status ) ) {
				}
			}
		}

		wp_redirect( get_site_url() );
		die();
	}

	/**
	 * Handle a completed payment
	 *
	 * @param LP_Order
	 * @param Paypal IPN params
	 */
	protected function payment_status_completed( $order, $request ) {

		// order status is already completed
		if ( $order->has_status( 'completed' ) ) {
			exit;
		}

		if ( 'completed' === $request['payment_status'] ) {
			$this->payment_complete( $order, ( !empty( $request['txn_id'] ) ? $request['txn_id'] : '' ), __( 'IPN payment completed', 'learnpress' ) );
			// save paypal fee
			if ( !empty( $request['mc_fee'] ) ) {
				update_post_meta( $order->post->ID, '_transaction_fee', $request['mc_fee'] );
			}
		} else {
		}
	}

	/**
	 * Handle a pending payment
	 *
	 * @param  LP_Order
	 * @param  Paypal IPN params
	 */
	protected function payment_status_pending( $order, $request ) {
		$this->payment_status_completed( $order, $request );
	}

	/**
	 * @param        LP_Order
	 * @param string $txn_id
	 * @param string $note - not use
	 */
	public function payment_complete( $order, $txn_id = '', $note = '' ) {
		$order->payment_complete( $txn_id );
	}

	public function process_payment( $order ) {

		$redirect = $this->get_request_url( $order );

		$json = array(
			'result'   => $redirect ? 'success' : 'fail',
			'redirect' => $redirect
		);

		return $json;
	}

	protected function prepare_line_items() {
		$this->line_items = array();
		if ( $items = LP()->get_checkout_cart()->get_items() ) {
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

		$this->line_items['item_name_' . $index]   = html_entity_decode( $item_name ? $item_name : __( 'Item', 'learnpress' ), ENT_NOQUOTES, 'UTF-8' );
		$this->line_items['quantity_' . $index]    = $quantity;
		$this->line_items['amount_' . $index]      = $amount;
		$this->line_items['item_number_' . $index] = $item_number;

		return true;
	}

	public function get_item_lines() {
		return $this->line_items;
	}

	public function get_request_url( $order_id ) {

		$order = LP_Order::instance( $order_id );
		$query = $this->get_paypal_args( $order );

		$paypal_payment_url = $this->paypal_url . '?' . http_build_query( $query );

		return $paypal_payment_url;
	}

	public function get_paypal_args( $order ) {
		$this->prepare_line_items();
		$user   = learn_press_get_current_user();
		$nonce  = wp_create_nonce( 'learn-press-paypal-nonce' );
		$custom = array( 'order_id' => $order->id, 'order_key' => $order->order_key );

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
				'cancel_return' => esc_url( learn_press_is_enable_cart() ? learn_press_get_page_link( 'cart' ) : get_site_url() ),
				'bn'            => 'LearnPress_Cart',
				//'invoice'       => $order->id,
				'custom'        => json_encode( $custom ),
				'notify_url'    => get_site_url() . '/?' . learn_press_get_web_hook( 'paypal' ) . '=1',
				'email'         => $user->user_email
			),
			$this->get_item_lines()
		);
		//print_r($args);die();
		return apply_filters( 'learn_press_paypal_args', $args );
	}

	public function __toString() {
		return 'Paypal';
	}
}