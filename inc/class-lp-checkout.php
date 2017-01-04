<?php

/**
 * Class LP_Checkout
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Checkout {

	/**
	 * @var LP_Checkout object instance
	 * @access protected
	 */
	static protected $_instance = null;

	/**
	 * Payment method
	 *
	 * @var string
	 */
	public $payment_method = '';

	/**
	 * @var array|mixed|null|void
	 */
	public $checkout_fields = array();

	/**
	 * @var null
	 */
	public $user_login = null;

	/**
	 * @var null
	 */
	public $user_pass = null;

	/**
	 * @var null
	 */
	public $order_comment = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( !is_user_logged_in() ) {
			$this->checkout_fields['user_login']    = __( 'Username', 'learnpress' );
			$this->checkout_fields['user_password'] = __( 'Password', 'learnpress' );
		}
		$this->checkout_fields = apply_filters( 'learn_press_checkout_fields', $this->checkout_fields );

		add_filter( 'learn_press_checkout_validate_field', array( $this, 'validate_fields' ), 10, 3 );
	}

	/**
	 * Creates temp new order if needed
	 *
	 * @return mixed|WP_Error
	 * @throws Exception
	 */
	public function create_order() {
		global $wpdb;
		// Third-party can be controls to create a order
		if ( $order_id = apply_filters( 'learn_press_create_order', null, $this ) ) {
			return $order_id;
		}

		try {
			// Start transaction if available
			//$wpdb->query( 'START TRANSACTION' );

			$order_data = array(
				'status'      => apply_filters( 'learn_press_default_order_status', 'pending' ),
				'user_id'     => get_current_user_id(),
				'user_note'   => $this->order_comment,
				'created_via' => 'checkout'
			);

			// Insert or update the post data
			$order_id = absint( LP()->session->order_awaiting_payment );
			// Resume the unpaid order if its pending
			if ( $order_id > 0 && ( $order = learn_press_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {

				$order_data['ID'] = $order_id;
				$order            = learn_press_update_order( $order_data );

				if ( is_wp_error( $order ) ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'learnpress' ), 401 ) );
				} else {
					$order->remove_order_items();
					//do_action( 'learn_press_resume_order', $order_id );
				}

			} else {
				$order = learn_press_create_order( $order_data );
				if ( is_wp_error( $order ) ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'learnpress' ), 400 ) );
				} else {
					$order_id = $order->id;
					do_action( 'learn_press_new_order', $order_id );
				}
			}

			// Store the line items to the new/resumed order
			foreach ( LP()->cart->get_items() as $item ) {
				if ( empty( $item['order_item_name'] ) && !empty( $item['item_id'] ) && ( $course = LP_Course::get_course( $item['item_id'] ) ) ) {
					$item['order_item_name'] = $course->get_title();
				} else {
					throw new Exception( sprintf( __( 'Item does not exists!', 'learnpress' ), 402 ) );
				}
				$item_id = $order->add_item( $item );

				if ( !$item_id ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'learnpress' ), 402 ) );
				}

				// Allow plugins to add order item meta
				do_action( 'learn_press_add_order_item_meta', $item_id, $item );
			}

			$order->set_payment_method( $this->payment_method );

			// Update user meta
			if ( !empty( $this->user_id ) ) {
				if ( apply_filters( 'learn_press_checkout_update_user_data', true, $this ) ) {
					// TODO: update user meta
				}
				do_action( 'learn_press_checkout_update_user_meta', $this->user_id, $_REQUEST );
			}

			// Third-party add meta data
			do_action( 'learn_press_checkout_update_order_meta', $order_id, $_REQUEST );

			//$wpdb->query( 'COMMIT' );

		} catch ( Exception $e ) {
			// There was an error adding order data!
			$wpdb->query( 'ROLLBACK' );
			echo $e->getMessage();
			return false; //$e->getMessage();
		}


		return $order_id;
	}

	/**
	 * Validate fields
	 *
	 * @param bool
	 * @param $field
	 * @param LP_Checkout instance
	 *
	 * @return bool
	 */
	public function validate_fields( $validate, $field, $checkout ) {
		if ( $field['name'] == 'user_login' && empty( $this->user_login ) ) {
			$validate = false;
			learn_press_add_message( __( 'Please enter user login', 'learnpress' ) );
		}
		if ( $field['name'] == 'user_password' && empty( $this->user_pass ) ) {
			$validate = false;
			learn_press_add_message( __( 'Please enter user password', 'learnpress' ) );
		}

		return $validate;
	}

	/**
	 * Process checkout from request
	 */
	public function process_checkout_handler() {
		if ( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ) {
			return;
		}
		/**
		 * Set default fields from request
		 */
		$this->payment_method = !empty( $_REQUEST['payment_method'] ) ? $_REQUEST['payment_method'] : '';
		$this->user_login     = !empty( $_POST['user_login'] ) ? $_POST['user_login'] : '';
		$this->user_pass      = !empty( $_POST['user_password'] ) ? $_POST['user_password'] : '';
		$this->order_comment  = isset( $_REQUEST['order_comments'] ) ? $_REQUEST['order_comments'] : '';

		// do checkout
		return $this->process_checkout();
	}

	/**
	 * Process checkout
	 *
	 * @return array|mixed|void
	 * @throws Exception
	 */
	public function process_checkout() {
		try {
			// Prevent timeout
			@set_time_limit( 0 );

			do_action( 'learn_press_before_checkout_process' );

			$success = true;
			if ( LP()->cart->is_empty() ) {
				return apply_filters( 'learn_press_checkout_cart_empty',
					array(
						'result'   => 'success',
						'redirect' => learn_press_get_page_link( 'checkout' )
					)
				);
			}

			if ( LP()->cart->needs_payment() && empty( $this->payment_method ) ) {
				$success = 5;
				learn_press_add_message( __( 'Please select a payment method', 'learnpress' ), 'error' );
			} else {
				//$this->payment_method = !empty( $_REQUEST['payment_method'] ) ? $_REQUEST['payment_method'] : '';
				if ( $this->checkout_fields ) foreach ( $this->checkout_fields as $name => $field ) {
					if ( !apply_filters( 'learn_press_checkout_validate_field', true, array( 'name' => $name, 'text' => $field ), $this ) ) {
						$success = 10;
					}
				}
				if ( !is_user_logged_in() && isset( $this->checkout_fields['user_login'] ) && isset( $this->checkout_fields['user_password'] ) ) {
					$creds                  = array();
					$creds['user_login']    = $this->user_login;
					$creds['user_password'] = $this->user_pass;
					$creds['remember']      = true;
					$user                   = wp_signon( $creds, is_ssl() );
					if ( is_wp_error( $user ) ) {
						learn_press_add_message( $user->get_error_message(), 'error' );
						$success = 15;
					}
				}
				LP()->session->set( 'chosen_payment_method', $this->payment_method );
			}

			foreach ( LP()->cart->get_items() as $item ) {
				$item = LP_Course::get_course( $item['item_id'] );
				if ( !$item ) {
					$success = 20;
					learn_press_add_message( __( 'Item %s does not exists.', 'learnpress' ), 'error' );
				} elseif ( !$item->is_purchasable() ) {
					learn_press_add_message( sprintf( __( 'Item "%s" is not purchasable.', 'learnpress' ), get_the_title( $item->id ) ), 'error' );
					$success = 25;
				}
			}
			if ( $success === true && LP()->cart->needs_payment() ) {

				if ( !$this->payment_method instanceof LP_Gateway_Abstract ) {
					// Payment Method
					$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();
					if ( !isset( $available_gateways[$this->payment_method] ) ) {
						$this->payment_method = '';
						learn_press_add_message( __( 'Invalid payment method.', 'learnpress' ), 'error' );
					} else {
						$this->payment_method = $available_gateways[$this->payment_method];
					}
				}
				if ( $this->payment_method ) {
					$success = $this->payment_method->validate_fields() ? true : 30;
				}
			}
			if ( $success === true ) {

				$order_id = $this->create_order();

				// allow Third-party hook
				do_action( 'learn_press_checkout_order_processed', $order_id, $this );
				if ( $this->payment_method ) {
					// Store the order is waiting for payment and each payment method should clear it
					LP()->session->order_awaiting_payment = $order_id;
					// Process Payment
					$result  = $this->payment_method->process_payment( $order_id );
					$success = !empty( $result['result'] ) ? $result['result'] == 'success' : 35;
				} else {
					// ensure that no order is waiting for payment
					$order = new LP_Order( $order_id );
					if ( $order && $order->payment_complete() ) {
						$result = array( 'result' => 'success', 'redirect' => $order->get_checkout_order_received_url() );
					}
				}
				// Redirect to success/confirmation/payment page
				if ( $success === true ) {
					$result = apply_filters( 'learn_press_checkout_success_result', $result, $order_id );
					if ( learn_press_is_ajax() ) {
						learn_press_send_json( $result );
					} else {
						wp_redirect( $result['redirect'] );
						exit;
					}

				}
			}

		} catch ( Exception $e ) {
			$has_error = $e->getMessage();
			if ( !empty( $has_error ) ) {
				learn_press_add_message( $has_error, 'error' );
			}
			$success = 40;
		}

		// Get all messages
		$error_messages = '';
		if ( $success !== true ) {
			ob_start();
			learn_press_print_notices();
			$error_messages = ob_get_clean();
		}

		$result = array(
			'result'   => $success === true ? 'success' : 'fail',
			'code'     => $success,
			'messages' => $error_messages,
			'redirect' => ''
		);
		return $result;
	}

	/**
	 * Get unique instance for this object
	 *
	 * @return LP_Checkout
	 */
	public static function instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new LP_Checkout();
		}
		return self::$_instance;
	}
}

