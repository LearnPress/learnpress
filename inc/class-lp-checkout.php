<?php

/**
 * Class LP_Checkout
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
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
	 * @var string|LP_Gateway_Abstract
	 */
	public $payment_method = null;

	/**
	 * @var array|mixed|null|void
	 */
	protected $checkout_fields = array();

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
	 * Handle the errors when checking out.
	 *
	 * @var array
	 */
	public $errors = array();

	/**
	 * @var string
	 */
	protected $_checkout_email = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'learn_press_checkout_validate_field', array( $this, 'validate_fields' ), 10, 3 );
		add_filter( 'learn-press/validate-checkout-fields', array( $this, 'check_validate_fields' ), 10, 3 );
		add_filter( 'learn-press/payment-successful-result', array( $this, 'process_customer' ), 10, 2 );

		$this->_checkout_email = LP()->session->get( 'checkout-email' );
	}

	/**
	 * Process customer when checking out.
	 *
	 * @param array $result
	 * @param int $order_id
	 *
	 * @return mixed
	 */
	public function process_customer( $result, $order_id ) {

		try {
			if ( ! $this->is_enable_guest_checkout() ) {
				throw new Exception( '' );
			}

			if ( ! $order = learn_press_get_order( $order_id ) ) {
				throw new Exception( '' );
			}
			$user_id         = 0;
			$checkout_option = LP_Request::get_string( 'checkout-email-option' );
			$order->delete_meta( '_create_account' );
			switch ( $checkout_option ) {
				case 'existing-account':
					if ( ! $user_id = $this->checkout_email_exists() ) {
						throw new Exception( '' );
					}

					break;
				case 'new-account':
					if ( $this->checkout_email_exists() ) {
						throw new Exception( 'NEW ACCOUNT EMAIL IS EXISTED', 0 );
					}

					$order->set_meta( '_create_account', 'yes' );
					LP()->session->set( 'user_waiting_payment', $this->get_checkout_email() );

					$user_id = $this->_create_account();
					if ( ! is_wp_error( $user_id ) ) {
						wp_new_user_notification( $user_id, null, apply_filters( 'learn-press/email-create-new-user-when-checkout', 'user' ) );
					}
					break;
			}

			if ( $user_id ) {
				$order->set_user_id( $user_id );
			}

			$order->save();
		} catch ( Exception $ex ) {
			if ( $ex->getCode() && $message = $ex->getMessage() ) {
				$result['message'] = $message;
			}
		}

		return $result;
	}

	/**
	 * @return mixed|null
	 */
	public function get_user_waiting_payment() {
		return LP()->session->get( 'user_waiting_payment' );
	}

	/**
	 * @return int|WP_Error
	 */
	protected function _create_account() {
		$email    = $this->get_checkout_email();
		$password = wp_generate_password( 12, true );
		$user_id  = wp_create_user( $email, $password, $email );

		return $user_id;
	}

	/**
	 * @param array $errors
	 * @param array $fields
	 * @param LP_Checkout $checkout
	 *
	 * @return array
	 */
	public function check_validate_fields( $errors, $fields, $checkout ) {
		/* check guest email */
		if ( wp_verify_nonce( sanitize_key( LP_Request::get_string( 'guest-checkout' ) ), 'guest-checkout' ) ) {
			if ( $this->is_enable_guest_checkout() && ! is_user_logged_in() && empty( $this->_checkout_email ) ) {
				$errors[] = __( 'Please enter your email.', 'learnpress' );
			}
		}
		/* check chose term and conditions or not */
		if ( isset( $fields['terms_conditions'] ) && $fields['terms_conditions'] == '' ) {
			$errors[] = __( 'You must accept our Terms & Conditions.', 'learnpress' );
		}

		return $errors;
	}

	/**
	 * Get email of user is being checkout.
	 *
	 * @return string
	 */
	public function get_checkout_email() {
		if ( $this->_checkout_email ) {
			return $this->_checkout_email;
		} elseif ( is_user_logged_in() ) {
			$user = learn_press_get_current_user( false );

			return $user->get_email();
		}

		return false;
	}

	/**
	 * @return bool|int
	 */
	public function checkout_email_exists() {
		if ( ! $email = $this->get_checkout_email() ) {
			return false;
		}

		if ( ! $user = get_user_by( 'email', $email ) ) {
			return false;
		}

		return $user->ID;
	}

	/**
	 * Checkout fields.
	 *
	 * @return array
	 */
	public function get_checkout_fields() {
		if ( ! is_user_logged_in() ) {
			$this->checkout_fields['user_login']    = __( 'Username', 'learnpress' );
			$this->checkout_fields['user_password'] = __( 'Password', 'learnpress' );
		}

		$this->checkout_fields = apply_filters( 'learn_press_checkout_fields', $this->checkout_fields );

		return $this->checkout_fields;
	}

	/**
	 * Check if an order is pending or failed.
	 *
	 * @param $order_id
	 *
	 * @return LP_Order|bool
	 */
	protected function _is_resume_order( $order_id ) {
		if ( $order_id > 0 && ( $order = learn_press_get_order( $order_id ) ) && $order->has_status( array(
				'pending',
				'failed'
			) )
		) {
			return $order;
		}

		return false;
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
		$order_id = apply_filters( 'learn-press/checkout/create-order', null, $this );

		// @deprecated
		$order_id = apply_filters( 'learn_press_create_order', null, $this );

		if ( $order_id ) {
			return $order_id;
		}
		$cart = LP()->cart;
		try {
			// Start transaction if available
			$wpdb->query( 'START TRANSACTION' );

			// Insert or update the post data
			$order_id = absint( LP()->session->get( 'order_awaiting_payment' ) );

			// Resume the unpaid order if its pending
			if ( $order = $this->_is_resume_order( $order_id ) ) {

				if ( is_wp_error( $order ) ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'learnpress' ), 401 ) );
				}

				$order->remove_order_items();
				do_action( 'learn-press/checkout/resume-order', $order_id );

			} else {
				$order = new LP_Order();
				if ( is_wp_error( $order ) ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'learnpress' ), 400 ) );
				}

				$order_id = $order->get_id();
				do_action( 'learn-press/checkout/new-order', $order_id );
			}

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = 0;
			}

			$order->set_customer_note( $this->order_comment );
			$order->set_status( learn_press_default_order_status( 'lp-' ) );
			$order->set_total( $cart->total );
			$order->set_subtotal( $cart->subtotal );
			$order->set_user_ip_address( learn_press_get_ip() );
			$order->set_user_agent( learn_press_get_user_agent() );
			$order->set_created_via( 'checkout' );
			$order->set_user_id( apply_filters( 'learn-press/checkout/default-user', $user_id ) );

			if ( $this->is_enable_guest_checkout() && $checkout_email = $this->get_checkout_email() ) {
				$order->set_checkout_email( $checkout_email );
			}

			$order_id = $order->save();

			// Store the line items to the new/resumed order
			foreach ( $cart->get_items() as $item ) {
				if ( empty( $item['order_item_name'] ) && ! empty( $item['item_id'] ) && ( $course = learn_press_get_course( $item['item_id'] ) ) ) {
					$item['order_item_name'] = apply_filters( 'learn-press/checkout/oder_item_name', $course->get_title(), $item );
				} else {
					throw new Exception( sprintf( __( 'Item does not exist!', 'learnpress' ), 402 ) );
				}

				$item_id = $order->add_item( $item );

				if ( ! $item_id ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'learnpress' ), 402 ) );
				}

				// @since 3.0.0
				do_action( 'learn-press/checkout/add-order-item-meta', $item_id, $item );
			}

			$order->set_payment_method( $this->payment_method );

			// Update user meta
			if ( ! empty( $this->user_id ) ) {
				do_action( 'learn-press/checkout/update-user-meta', $this->user_id );
			}

			// Third-party add meta data
			do_action( 'learn-press/checkout/update-order-meta', $order_id );

			if ( ! $order_id || is_wp_error( $order_id ) ) {
				learn_press_add_message( __( 'Unable to checkout. Order creation failed.', 'learnpress' ) );
			}
			$wpdb->query( 'COMMIT' );

		} catch ( Exception $e ) {
			// There was an error adding order data!
			$wpdb->query( 'ROLLBACK' );
			learn_press_add_message( $e->getMessage() );

			return false;
		}


		return $order_id;
	}

	/**
	 * Guest checkout is enable?
	 *
	 * @return mixed
	 * @since 3.0.0
	 *
	 */
	public function is_enable_guest_checkout() {
		return apply_filters(
			'learn-press/checkout/enable-guest',
			LP()->settings()->get( 'guest_checkout' ) == 'yes'
		);
	}

	/**
	 * Enable user can login in checkout page?
	 *
	 * @return bool
	 * @since 3.0.0
	 *
	 */
	public function is_enable_login() {
		return apply_filters(
			'learn-press/checkout/enable-login',
			in_array(
				LP()->settings()->get( 'enable_login_checkout' ),
				array( '', 'yes' )
			)
		);
	}

	/**
	 * Enable user can register in checkout page?
	 *
	 * @return bool
	 * @since 3.0.0
	 *
	 */
	public function is_enable_register() {
		return apply_filters(
			'learn-press/checkout/enable-register',
			in_array(
				LP()->settings()->get( 'enable_registration_checkout' ),
				array( '', 'yes' )
			) && get_option( 'users_can_register' )
		);
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
			learn_press_add_message( __( 'Please enter username.', 'learnpress' ) );
		}
		if ( $field['name'] == 'user_password' && empty( $this->user_pass ) ) {
			$validate = false;
			learn_press_add_message( __( 'Please enter user password', 'learnpress' ) );
		}

		if ( wp_verify_nonce( 'guest-checkout', sanitize_key( LP_Request::get_string( 'guest-checkout' ) ) ) ) {
			if ( empty( $this->_checkout_email ) ) {
				learn_press_add_message( __( 'Please enter your email.', 'learnpress' ) );
			}
		}

		$validate = false;


		return $validate;
	}

	/**
	 * Process checkout from request.
	 *
	 * @return mixed
	 */
	public function process_checkout_handler() {
		if ( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ) {
			return false;
		}

		/**
		 * Set default fields from request
		 */
		$this->payment_method  = LP_Request::get_string( 'payment_method' );
		$this->user_login      = LP_Request::get_string( 'user_login' );
		$this->user_pass       = LP_Request::get_string( 'user_password' );
		$this->order_comment   = LP_Request::get_string( 'order_comments' );
		$this->_checkout_email = LP_Request::get_email( 'checkout-email' );
		if ( LP_Request::get_int( 'terms_conditions_field', 0 ) ) {
			$this->checkout_fields['terms_conditions'] = LP_Request::get_string( 'terms_conditions', '' );
		}

		if ( $this->_checkout_email ) {
			LP()->session->set( 'checkout-email', $this->_checkout_email );
		}
		// do checkout
		$this->process_checkout();

		return true;
	}

	/**
	 * Validate fields.
	 *
	 * @return bool
	 */
	public function validate_checkout_fields() {
		$this->errors = array();
		if ( $fields = $this->get_checkout_fields() ) {
			foreach ( $fields as $name => $field ) {
				$error = apply_filters( 'learn-press/validate-checkout-field', $field );
				if ( is_wp_error( $error ) ) {
					$this->errors[ $name ] = $error;
				}
			}
		}

		$this->errors = apply_filters( 'learn-press/validate-checkout-fields', $this->errors, $fields, $this );

		return ! sizeof( $this->errors );
	}

	/**
	 * Validate checkout payment.
	 *
	 * @return bool
	 * @throws Exception
	 *
	 */
	public function validate_payment() {
		$cart     = LP()->cart;
		$validate = true;
		if ( $cart->needs_payment() ) {

			if ( ! $this->payment_method instanceof LP_Gateway_Abstract ) {
				// Payment Method
				$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();

				if ( ! isset( $available_gateways[ $this->payment_method ] ) ) {
					$this->payment_method = '';
					throw new Exception( __( 'No payment method is selected', 'learnpress' ), LP_ERROR_NO_PAYMENT_METHOD_SELECTED );
				} else {
					$this->payment_method = $available_gateways[ $this->payment_method ];
				}
			}

			if ( $this->payment_method ) {
				$validate = $this->payment_method->validate_fields();
			}
		}

		return $validate;
	}

	/**
	 * Process checkout.
	 *
	 * @throws Exception
	 */
	public function process_checkout() {
		$has_error = false;
		try {
			// Prevent timeout
			@set_time_limit( 0 );

			/**
			 * @deprecated
			 */
			do_action( 'learn_press_before_checkout_process' );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/before-checkout' );

			$cart   = LP()->cart;
			$result = false;

			// There is no course in cart
			if ( $cart->is_empty() ) {
				throw new Exception( __( 'Your cart is currently empty.', 'learnpress' ) );
			}

			// Validate courses
			foreach ( $cart->get_items() as $item ) {
				$course = learn_press_get_course( $item['item_id'] );
				if ( ! $course || ! $course->is_purchasable() ) {
					throw new Exception( sprintf( '%s "%s" %s', __( 'Item', 'learnpress' ), $course->get_title(), __( 'is not purchasable.', 'learnpress' ) ) );
				}
			}

			// Validate extra fields
			if ( ! $this->validate_checkout_fields() ) {
				foreach ( $this->errors as $error ) {
					learn_press_add_message( $error, 'error' );
				}
			} else {

				// maybe throw new exception
				$this->validate_payment();

				// Create order
				$order_id = $this->create_order();

				if ( is_wp_error( $order_id ) ) {
					throw new Exception( $order_id->get_error_message() );
				}

				// allow Third-party hook
				do_action( 'learn-press/checkout-order-processed', $order_id, $this );
				if ( $this->payment_method ) {
					// Store the order is waiting for payment and each payment method should clear it
					LP()->session->order_awaiting_payment = $order_id;
					// Process Payment
					$result = $this->payment_method->process_payment( $order_id );

					if ( isset( $result['result'] ) ) {
						if ( 'success' === $result['result'] ) {
							$result = apply_filters( 'learn-press/payment-successful-result', $result, $order_id );

							if ( learn_press_is_ajax() ) {
								learn_press_send_json( $result );
							} else {
								wp_redirect( $result['redirect'] );
								exit;
							}
						} elseif ( learn_press_is_ajax() ) {
							learn_press_send_json( $result );
						}
					}

				} else {
					// ensure that no order is waiting for payment
					$order = new LP_Order( $order_id );
					if ( $order && $order->payment_complete() ) {

						$result = apply_filters( 'learn-press/checkout-no-payment-result',
							array(
								'result'   => 'success',
								'redirect' => $order->get_checkout_order_received_url()
							),
							$order->get_id()
						);

						if ( learn_press_is_ajax() ) {
							learn_press_send_json( $result );
						} else {
							wp_redirect( $result['redirect'] );
							exit;
						}
					}
				}
			}
		} catch ( Exception $e ) {
			$has_error = $e->getMessage();
			learn_press_add_message( $has_error, 'error' );
		}

		$is_error = ! ! learn_press_message_count( 'error' );

		$result = apply_filters( 'learn-press/checkout-error',
			array(
				'result'   => ! $is_error ? 'success' : 'fail',
				'messages' => learn_press_get_messages()
			)
		);

		learn_press_maybe_send_json( $result, 'learn_press_remove_messages' );
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

