<?php
/**
 * Class LP_Checkout
 *
 * @author  ThimPress <Nhamdv>
 * @package LearnPress/Classes
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

class LP_Checkout {

	/**
	 * @var LP_Checkout object instance
	 * @access protected
	 */
	protected static $_instance = null;

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
	 * @var array
	 * @since 4.0.0
	 */
	protected $login_data = array();

	/**
	 * @var array
	 * @since 4.0.0
	 */
	protected $register_data = array();

	/**
	 * @var string
	 * @since 4.0.0
	 */
	protected $guest_email = '';

	/**
	 * @var string
	 * @since 4.0.0
	 */
	protected $checkout_action = '';

	/**
	 * @var array
	 * @since 4.0.0
	 */
	protected $checkout_form_data = array();

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_filter( 'learn-press/validate-checkout-field', array( $this, 'validate_fields' ), 10, 3 );
		add_filter( 'learn-press/validate-checkout-fields', array( $this, 'check_validate_fields' ), 10, 3 );
		//add_filter( 'learn-press/payment-successful-result', array( $this, 'process_customer' ), 10, 2 );

		if ( ! is_null( LearnPress::instance()->session ) ) {
			$this->_checkout_email = LearnPress::instance()->session->get( 'checkout-email' );
		}
	}

	/**
	 * Process customer when checking out.
	 *
	 * @param array $result
	 * @param int   $order_id
	 *
	 * @return mixed
	 * @editor tungnx
	 * @modify 4.1.4 - merge to function create_order
	 */
	/*public function process_customer( $result, $order_id ) {
		try {
			if ( ! $this->is_enable_guest_checkout() ) {
				throw new Exception( '' );
			}

			$order = learn_press_get_order( $order_id );

			if ( ! $order ) {
				throw new Exception( '' );
			}

			$user_id         = 0;
			$checkout_option = LP_Request::get_string( 'checkout-email-option' );

			switch ( $checkout_option ) {
				case 'new-account':
					if ( $this->checkout_email_exists() ) {
						throw new Exception( 'NEW ACCOUNT EMAIL IS EXISTED', 0 );
					}

					$order->set_meta( '_create_account', 'yes' );

					$user_id = $this->_create_account();

					if ( ! is_wp_error( $user_id ) ) {
						wp_new_user_notification( $user_id, null, apply_filters( 'learn-press/email-create-new-user-when-checkout', 'user' ) );
					}
					break;
			}

			if ( $user_id ) {
				$order->set_user_id( $user_id );
				$order->save();
			}
		} catch ( Exception $ex ) {
			if ( $ex->getCode() && $ex->getMessage() ) {
				$result['message'] = $ex->getMessage();
			}
		}

		return $result;
	}*/

	/**
	 * Create account when checking out with user guest and tick create account.
	 *
	 * @return int|WP_Error
	 */
	protected function create_account(): int {
		$user_id = 0;

		try {
			$email    = $this->get_checkout_email();
			$password = wp_generate_password();
			$user_id  = wp_create_user( $email, $password, $email );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $user_id;
	}

	/**
	 * @param array       $errors
	 * @param array       $fields
	 * @param LP_Checkout $checkout
	 *
	 * @return array
	 */
	public function check_validate_fields( $errors, $fields, $checkout ) {
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		try {
			$session    = LearnPress::instance()->session;
			$cart       = LearnPress::instance()->cart;
			$cart_items = $cart->get_items();

			switch ( $this->checkout_action ) {
				case 'checkout-login':
					$this->checkout_form_data['remember'] = isset( $_POST['rememberme'] );

					$login_info = $this->checkout_form_data;

					$user = wp_signon(
						array(
							'user_login'    => $login_info['username'],
							'user_password' => $login_info['password'],
							'remember'      => $login_info['remember'],
						),
						is_ssl()
					);

					if ( is_wp_error( $user ) ) {
						throw new Exception( $user->get_error_message() );
					} else {
						wp_set_current_user( $user->ID );
					}
					break;
				case 'checkout-register':
					$default_fields = array();

					if ( isset( $_POST['reg_first_name'] ) ) {
						$default_fields['first_name'] = LP_Helper::sanitize_params_submitted( $_POST['reg_first_name'] );
					}

					if ( isset( $_POST['reg_last_name'] ) ) {
						$default_fields['last_name'] = LP_Helper::sanitize_params_submitted( $_POST['reg_last_name'] );
					}

					if ( isset( $_POST['reg_display_name'] ) ) {
						$default_fields['display_name'] = LP_Helper::sanitize_params_submitted( $_POST['reg_display_name'] );
					}

					$update_meta = isset( $_POST['_lp_custom_register_form'] ) ? LP_Helper::sanitize_params_submitted( $_POST['_lp_custom_register_form'] ) : array();

					$user_id = LP_Forms_Handler::learnpress_create_new_customer(
						$this->checkout_form_data['reg_email'],
						$this->checkout_form_data['reg_username'],
						$this->checkout_form_data['reg_password'],
						$this->checkout_form_data['reg_password2'],
						$default_fields,
						$update_meta
					);

					if ( is_wp_error( $user_id ) ) {
						$errors['create_user_error'] = $user_id->get_error_message();
					} else {
						$user = wp_signon(
							array(
								'user_login'    => $this->checkout_form_data['reg_email'],
								'user_password' => $this->checkout_form_data['reg_password'],
								'remember'      => 1,
							),
							is_ssl()
						);

						if ( is_wp_error( $user ) ) {
							throw new Exception( $user->get_error_message() );
						} else {
							wp_set_current_user( $user->ID );
						}
					}
					break;
				case 'guest-checkout':
			}

				// Set session, cart for user have just login/register success.
			if ( in_array( $this->checkout_action, [ 'checkout-login', 'checkout-register' ] ) ) {
				$cart->empty_cart();
				$session->set( 'cart', $cart_items, true );
			}
		} catch ( Throwable $e ) {
			$errors = new WP_Error( 'checkout-error', $e->getMessage() );
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
		$email = $this->get_checkout_email();

		if ( ! $email ) {
			return false;
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return false;
		}

		return $user->ID;
	}

	public function verify_nonce() {
		if ( empty( $_POST['learn-press-checkout-nonce'] ) ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			$actions = array( 'guest-checkout', 'checkout-register', 'checkout-login' );
		} else {
			$actions = array( 'guest-checkout', 'checkout-register', 'user-logged' );
		}

		foreach ( $actions as $action ) {
			if ( wp_verify_nonce( $_REQUEST['learn-press-checkout-nonce'], "learn-press-{$action}" ) ) {
				$this->checkout_action = $action;

				return true;
			}
		}

		return false;
	}

	/**
	 * Checkout fields.
	 *
	 * @return array
	 */
	public function get_checkout_fields() {
		if ( ! $this->verify_nonce() ) {
			$this->checkout_fields['invalid_request'] = new WP_Error( 'invalid_request', __( 'Your session has expired.', 'learnpress' ) );
		} else {
			if ( $this->checkout_action === 'checkout-register' ) {
				$this->checkout_fields['reg_email']     = esc_html__( 'Email', 'learnpress' );
				$this->checkout_fields['reg_username']  = esc_html__( 'Username', 'learnpress' );
				$this->checkout_fields['reg_password']  = esc_html__( 'Password', 'learnpress' );
				$this->checkout_fields['reg_password2'] = esc_html__( 'Confirm Password', 'learnpress' );
			} elseif ( $this->checkout_action === 'checkout-login' ) {
				$this->checkout_fields['username'] = esc_html__( 'Username', 'learnpress' );
				$this->checkout_fields['password'] = esc_html__( 'Password', 'learnpress' );
			} elseif ( $this->checkout_action === 'guest-checkout' ) {
				$this->checkout_fields['guest_email'] = esc_html__( 'Email', 'learnpress' );
			}
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
		$order = learn_press_get_order( $order_id );

		if ( $order_id > 0 && $order && $order->has_status( array( 'pending', 'failed' ) ) ) {
			return $order;
		}

		return false;
	}

	/**
	 * Create LP Order.
	 *
	 * @since 3.0.0
	 * @version 4.0.1
	 * @return mixed|WP_Error
	 * @throws Exception
	 */
	public function create_order() {
		$cart       = LearnPress::instance()->cart;
		$cart_total = $cart->calculate_totals();

		try {
			/*// Insert or update the post data
			$order_id = absint( LearnPress::instance()->session->get( 'order_awaiting_payment' ) );

			// Resume the unpaid order if its pending
			$order = $this->_is_resume_order( $order_id );

			if ( $order ) {
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
			}*/

			$order   = new LP_Order();
			$user_id = 0;

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$checkout_option = LP_Request::get_param( 'checkout-email-option' );
				// Set user id for Order if buy with Guest and email exists on the user
				$user_id = $this->checkout_email_exists();

				// Create new user if buy with Guest and tick "Create new Account"
				if ( $checkout_option === 'new-account' ) {
					if ( $user_id ) {
						throw new Exception( __( 'New account email is existed', 'learnpress' ), 0 );
					}

					//$order->set_meta( '_create_account', 'yes' );

					$user_id = $this->create_account();
					if ( $user_id ) {
						// Notify mail create user success
						wp_new_user_notification( $user_id, null, apply_filters( 'learn-press/email-create-new-user-when-checkout', 'user' ) );
					} else {
						throw new Exception( __( 'Create account failed', 'learnpress' ), 0 );
					}
				}

				// Get user_id Guest
				if ( ! $user_id ) {
					/**
					 * @var LP_User_Guest $user_guest
					 */
					$user_guest = learn_press_get_user();
					$user_id    = $user_guest->get_id();
				}
			}

			$order->set_customer_note( $this->order_comment );
			$order->set_status( LP_ORDER_PENDING );
			$order->set_total( $cart_total->total );
			$order->set_subtotal( $cart_total->subtotal );
			$order->set_user_ip_address( learn_press_get_ip() );
			$order->set_user_agent( learn_press_get_user_agent() );
			$order->set_created_via( 'checkout' );
			$order->set_user_id( apply_filters( 'learn-press/checkout/default-user', $user_id ) );
			if ( $this->payment_method instanceof LP_Gateway_Abstract ) {
				$order->set_data( 'payment_method', $this->payment_method->get_id() );
				$order->set_data( 'payment_method_title', $this->payment_method->get_title() );
			}

			if ( $this->is_enable_guest_checkout() && $this->get_checkout_email() ) {
				$order->set_checkout_email( $this->get_checkout_email() );
			}

			$order_id = $order->save();

			// Store the line items to the order
			foreach ( $cart->get_items() as $item ) {
				$item_type = get_post_type( $item['item_id'] );

				if ( ! in_array( $item_type, learn_press_get_item_types_can_purchase() ) ) {
					continue;
				}

				$item_id = $order->add_item( $item );

				if ( ! $item_id ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'learnpress' ), 402 ) );
				}

				do_action( 'learn-press/checkout/add-order-item-meta', $item_id, $item );
			}

			if ( ! empty( $this->user_id ) ) {
				do_action( 'learn-press/checkout/update-user-meta', $this->user_id );
			}

			do_action( 'learn-press/checkout/update-order-meta', $order_id );

			if ( ! $order_id || is_wp_error( $order_id ) ) {
				learn_press_add_message( __( 'Unable to checkout. Order creation failed.', 'learnpress' ) );
			}
		} catch ( Exception $e ) {
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
	 */
	public function is_enable_guest_checkout() {
		return apply_filters( 'learn-press/checkout/enable-guest', LP_Settings::instance()->get( 'guest_checkout', 'no' ) == 'yes' );
	}

	/**
	 * Enable user can login in checkout page?
	 *
	 * @return bool
	 * @since 3.0.0
	 */
	public function is_enable_login() {
		return apply_filters( 'learn-press/checkout/enable-login', in_array( LP_Settings::instance()->get( 'enable_login_checkout' ), array( '', 'yes' ) ) );
	}

	/**
	 * Enable user can register in checkout page?
	 *
	 * @return bool
	 * @since 3.0.0
	 */
	public function is_enable_register() {
		return apply_filters( 'learn-press/checkout/enable-register', in_array( LP_Settings::instance()->get( 'enable_registration_checkout' ), array( '', 'yes' ) ) && get_option( 'users_can_register' ) );
	}

	/**
	 * Validate fields
	 *
	 * @param bool   $validate
	 * @param mixed  $field
	 * @param string $name
	 *
	 * @return bool|WP_Error
	 */
	public function validate_fields( $validate, $field, $name ) {
		switch ( $this->checkout_action ) {
			case 'checkout-register':
				if ( $name === 'reg_username' ) {
					if ( empty( $_POST['reg_username'] ) ) {
						return new WP_Error( 'username_empty', sprintf( __( '%s is required field.', 'learnpress' ), $field ) );
					} elseif ( username_exists( $_POST['reg_username'] ) ) {
						return new WP_Error( 'username_exists', sprintf( __( '%s is exists.', 'learnpress' ), $_POST['reg_username'] ) );
					} elseif ( ! validate_username( $_POST['reg_username'] ) ) {
						return new WP_Error( 'username_invalid', sprintf( __( '%s is not a valid username.', 'learnpress' ), $_POST['reg_username'] ) );
					}
				} elseif ( $name === 'reg_email' ) {
					if ( empty( $_POST['reg_email'] ) ) {
						return new WP_Error( 'email_empty', sprintf( __( '%s is required field.', 'learnpress' ), $field ) );
					} elseif ( email_exists( $_POST['reg_email'] ) ) {
						return new WP_Error( 'email_exists', sprintf( __( '%s is exists.', 'learnpress' ), $_POST['reg_email'] ) );
					} elseif ( ! is_email( $_POST['reg_email'] ) ) {
						return new WP_Error( 'email_invalid', sprintf( __( '%s is not a valid email.', 'learnpress' ), $_POST['reg_email'] ) );
					}
				} elseif ( $name === 'reg_password' ) {
					if ( empty( $_POST['reg_password'] ) ) {
						return new WP_Error( 'password_empty', sprintf( __( '%s is required field.', 'learnpress' ), $field ) );
					}
				} elseif ( $name === 'reg_password2' ) {
					if ( empty( $_POST['reg_password2'] ) ) {
						return new WP_Error( 'password2_empty', sprintf( __( '%s is required field.', 'learnpress' ), $field ) );
					}
				}

				$this->checkout_form_data[ $name ] = LP_Helper::maybe_unserialize( $_POST[ $name ] );

				break;
			case 'checkout-login':
				if ( $name === 'username' ) {
					if ( empty( $_POST['username'] ) ) {
						return new WP_Error( 'email_empty', sprintf( __( '%s is required field.', 'learnpress' ), $field ) );
					}

					if ( ! username_exists( $_POST['username'] ) && ! email_exists( $_POST['username'] ) ) {
						return new WP_Error( 'username_exists', sprintf( __( '%s is not exist.', 'learnpress' ), $field ) );
					}
				} elseif ( $name === 'password' ) {
					if ( empty( $_POST['password'] ) ) {
						return new WP_Error( 'email_empty', sprintf( __( '%s is required field.', 'learnpress' ), $field ) );
					}
				}

				$this->checkout_form_data[ $name ] = LP_Helper::maybe_unserialize( $_POST[ $name ] );
				break;
			case 'guest-checkout':
				if ( empty( $_POST['guest_email'] ) ) {
					return new WP_Error( 'email_empty', sprintf( __( '%s is required field.', 'learnpress' ), $field ) );
				} elseif ( ! is_email( $_POST['guest_email'] ) ) {
					return new WP_Error( 'email_invalid', __( 'Your email is not valid.', 'learnpress' ) );
				}

				$this->guest_email     = LP_Helper::maybe_unserialize( $_POST[ $name ] );
				$this->_checkout_email = LP_Helper::maybe_unserialize( $_POST[ $name ] );
		}

		return $validate;
	}

	/**
	 * Process checkout from request.
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function process_checkout_handler() {
		if ( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ) {
			return false;
		}

		/**
		 * Set default fields from request.
		 */
		$this->payment_method  = LP_Request::get_string( 'payment_method' );
		$this->user_login      = LP_Request::get_string( 'username' );
		$this->user_pass       = LP_Request::get_string( 'password' );
		$this->order_comment   = LP_Request::get_string( 'order_comments' );
		$this->_checkout_email = LP_Request::get_email( 'checkout-email' );

		if ( LP_Request::get_int( 'terms_conditions_field', 0 ) ) {
			$this->checkout_fields['terms_conditions'] = LP_Request::get_string( 'terms_conditions', '' );
		}

		if ( $this->_checkout_email ) {
			LearnPress::instance()->session->set( 'checkout-email', $this->_checkout_email );
		}

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
		$fields       = $this->get_checkout_fields();

		if ( $fields ) {
			foreach ( $fields as $name => $field ) {
				if ( ! is_wp_error( $field ) ) {
					$error = apply_filters( 'learn-press/validate-checkout-field', true, $field, $name );
				} else {
					$error = $field;
				}

				if ( is_wp_error( $error ) ) {
					$this->errors[ $name ] = $error;
				} elseif ( ! $error ) {
					$this->errors[ $name ] = new WP_Error( 'invalid_field', __( 'Invalid field', 'learnpress' ) );
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
	 */
	public function validate_payment() {
		$cart     = LearnPress::instance()->cart;
		$validate = true;

		if ( $cart->needs_payment() ) {
			if ( ! $this->payment_method instanceof LP_Gateway_Abstract ) {
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
		try {
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 0 ); // @codingStandardsIgnoreLine
			}

			$lp_session = LearnPress::instance()->session;

			do_action( 'learn-press/before-checkout' );

			$cart = LearnPress::instance()->cart;
			if ( $cart->is_empty() ) {
				throw new Exception( __( 'Your cart is currently empty.', 'learnpress' ) );
			}

			foreach ( $cart->get_items() as $item ) {
				$item_type = get_post_type( $item['item_id'] );

				if ( ! in_array( $item_type, learn_press_get_item_types_can_purchase() ) ) {
					throw new Exception( __( 'Type item buy invalid!', 'learnpress' ) );
				}
			}

			if ( ! $this->validate_checkout_fields() ) {
				foreach ( $this->errors as $key => $error ) {
					if ( is_wp_error( $error ) ) {
						$error = $error->get_error_message();
						throw new Exception( $error );
					}
					//$messages[ $key ] = array( $error, 'error' );
				}
			} else {
				//LearnPress::instance()->cart->calculate_totals();
				// maybe throw new exception
				$this->validate_payment();

				// Create order if not handle done.
				$order_id = $lp_session->get( 'order_awaiting_payment', 0 );
				if ( ! $order_id ) {
					$order_id = $this->create_order();
					if ( is_wp_error( $order_id ) ) {
						throw new Exception( $order_id->get_error_message() );
					}

					$lp_session->set( 'order_awaiting_payment', $order_id, true );
				}

				// allow Third-party hook: send email and more...
				do_action( 'learn-press/checkout-order-processed', $order_id, $this );

				if ( $this->payment_method instanceof LP_Gateway_Abstract ) {
					// Process Payment
					$result = $this->payment_method->process_payment( $order_id );
					if ( isset( $result['result'] ) ) {
						if ( 'success' === $result['result'] ) {
							// Clear cart.
							LearnPress::instance()->get_cart()->empty_cart();
							// Clear order_awaiting_payment.
							//$lp_session->remove( 'order_awaiting_payment', true );
							$result = apply_filters( 'learn-press/payment-successful-result', $result, $order_id );
						}

						if ( learn_press_is_ajax() ) {
							learn_press_send_json( $result );
						} else {
							wp_redirect( $result['redirect'] );
							exit;
						}
					}
				} else {
					// For case enroll course free.
					$order = new LP_Order( $order_id );
					if ( $order->payment_complete() ) {
						$is_guest_checkout = (bool) $this->guest_email;
						$redirect          = $order->get_checkout_order_received_url();

						if ( ! $is_guest_checkout ) {
							$course_id = get_transient( 'checkout_enroll_course_id' );

							if ( ! $course_id ) {
								if ( ! empty( $_REQUEST['enroll-course'] ) ) {
									$course_id = absint( $_REQUEST['enroll-course'] );
								}
							}

							if ( $course_id ) {
								delete_transient( 'checkout_enroll_course_id' );
								$redirect = get_the_permalink( $course_id );
							}
						}

						// Clear cart.
						LearnPress::instance()->get_cart()->empty_cart();
						// Clear order_awaiting_payment.
						//$lp_session->remove( 'order_awaiting_payment', true );

						$result = array(
							'result'   => 'success',
							'redirect' => $redirect,
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
			$result = array(
				'result'   => 'fail',
				'messages' => $e->getMessage(),
			);
			learn_press_send_json( $result );
		}
	}

	/**
	 * Singleton
	 *
	 * @return LP_Checkout
	 */
	public static function instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

