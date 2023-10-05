<?php
/**
 * Class LP_Checkout
 *
 * @author  ThimPress <Nhamdv>
 * @package LearnPress/Classes
 * @version 4.0.0
 */

use LearnPress\Helpers\Singleton;

defined( 'ABSPATH' ) || exit;

class LP_Checkout {
	use Singleton;
	/**
	 * Payment method
	 *
	 * @var LP_Gateway_Abstract
	 */
	public $payment_method = null;
	/**
	 * Payment method string when user choice
	 *
	 * @var string $payment_method_str
	 */
	public $payment_method_str = '';
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
	 * @var string
	 * @since 4.0.0
	 */
	protected $guest_email = '';
	/**
	 * @var string
	 * @since 4.0.0
	 */
	protected $checkout_action = '';

	public function init() {
		//add_filter( 'learn-press/validate-checkout-field', array( $this, 'validate_fields' ), 10, 3 );
		add_filter( 'learn-press/validate-checkout-fields', array( $this, 'check_validate_fields' ), 10 );

		if ( ! is_null( LearnPress::instance()->session ) ) {
			$this->_checkout_email = LearnPress::instance()->session->get( 'checkout-email' );
		}
	}

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
	 * Check valid fields login/register/guest checkout.
	 * Store session guest before login success.
	 * When user login success, will update session data of guest for user.
	 *
	 * @throws Exception
	 */
	public function check_validate_fields() {
		$session                        = LearnPress::instance()->session;
		$data_session_before_user_login = $session->get_session_data();
		$checkout_account_type          = LP_Request::get_param( 'checkout-account-switch-form' );
		$this->checkout_action          = $checkout_account_type;

		switch ( $this->checkout_action ) {
			case 'login':
				$user = wp_signon(
					array(
						'user_login'    => LP_Request::get_param( 'username' ),
						'user_password' => LP_Request::get_param( 'password' ),
						'remember'      => LP_Request::get_param( 'remember', false ),
					),
					is_ssl()
				);

				if ( is_wp_error( $user ) ) {
					throw new Exception( $user->get_error_message() );
				} else {
					wp_set_current_user( $user->ID );
				}
				break;
			case 'register':
				$default_fields = array(
					'reg_email'     => LP_Request::get_param( 'reg_email' ),
					'reg_username'  => LP_Request::get_param( 'reg_username' ),
					'reg_password'  => LP_Request::get_param( 'reg_password' ),
					'reg_password2' => LP_Request::get_param( 'reg_password2' ),
				);

				if ( isset( $_POST['reg_first_name'] ) ) {
					$default_fields['first_name'] = LP_Request::get_param( 'reg_first_name' );
				}

				if ( isset( $_POST['reg_last_name'] ) ) {
					$default_fields['last_name'] = LP_Request::get_param( 'reg_last_name' );
				}

				if ( isset( $_POST['reg_display_name'] ) ) {
					$default_fields['display_name'] = LP_Request::get_param( 'reg_display_name' );
				}

				$update_meta = isset( $_POST['_lp_custom_register_form'] ) ? LP_Helper::sanitize_params_submitted( $_POST['_lp_custom_register_form'] ) : array();

				$user_id = LP_Forms_Handler::learnpress_create_new_customer(
					$default_fields['reg_email'],
					$default_fields['reg_username'],
					$default_fields['reg_password'],
					$default_fields['reg_password2'],
					$default_fields,
					$update_meta
				);

				if ( is_wp_error( $user_id ) ) {
					throw new Exception( $user_id->get_error_message() );
				} else {
					$user = wp_signon(
						array(
							'user_login'    => $default_fields['reg_email'],
							'user_password' => $default_fields['reg_password'],
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
			case 'guest':
				$email_guest = LP_Request::get_param( 'guest_email' );
				if ( ! is_email( $email_guest ) ) {
					throw new Exception( __( 'Your email is not valid!', 'learnpress' ) );
				}

				$this->guest_email     = $email_guest;
				$this->_checkout_email = $email_guest;
				break;
		}

		// Set session, cart for user have just login/register success.
		if ( in_array( $this->checkout_action, [ 'login', 'register' ] ) ) {
			foreach ( $data_session_before_user_login as $key => $item ) {
				$session->set( $key, maybe_unserialize( $item ) );
			}
			$session->save_data();
		}
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

	/**
	 * Create LP Order.
	 *
	 * @since 3.0.0
	 * @version 4.0.2
	 * @return mixed|string
	 * @throws Exception
	 */
	public function create_order() {
		$cart       = LearnPress::instance()->cart;
		$cart_total = $cart->calculate_totals();
		$order      = new LP_Order();
		$user_id    = 0;

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
				throw new Exception( sprintf( __( 'Error %d: Unable to add item to order. Please try again.', 'learnpress' ), 402 ) );
			}

			do_action( 'learn-press/checkout/add-order-item-meta', $item_id, $item );
		}

		if ( ! empty( $this->user_id ) ) {
			do_action( 'learn-press/checkout/update-user-meta', $this->user_id );
		}

		do_action( 'learn-press/checkout/update-order-meta', $order_id );

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
	 * Enable user can log in checkout page?
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
		$this->payment_method_str = LP_Request::get_param( 'payment_method' );
		//$this->user_login      = LP_Request::get_param( 'username' );
		//$this->user_pass       = LP_Request::get_param( 'password' );
		$this->order_comment   = LP_Request::get_param( 'order_comments' );
		$this->_checkout_email = LP_Request::get_param( 'checkout-email' );

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
	 * Addon Upsell 4.0.1 use argument $errors
	 * Must update to 4.0.2 to use throw Error instead.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function validate_checkout_fields() {
		$this->errors = array();
		//$fields       = $this->get_checkout_fields();
		$fields = [];

		/*if ( $fields ) {
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
		}*/

		// Check nonce
		$nonce = LP_Request::get_param( 'learn-press-checkout-nonce' );
		if ( ! wp_verify_nonce( $nonce, 'learn-press-checkout' ) ) {
			throw new Exception( __( 'Your session has expired.', 'learnpress' ) );
		}

		$error = apply_filters( 'learn-press/validate-checkout-fields', $this->errors, $fields, $this );
		if ( is_wp_error( $error ) ) {
			$this->errors[] = $error;
			return false;
		}

		return true;

		//$this->errors = apply_filters( 'learn-press/validate-checkout-fields', $this->errors, $fields, $this );

		//return ! sizeof( $this->errors );
	}

	/**
	 * Validate checkout payment.
	 *
	 * @throws Exception
	 */
	public function validate_payment() {
		$cart = LearnPress::instance()->cart;
		//$validate = true;

		if ( $cart->needs_payment() ) {
			if ( ! $this->payment_method instanceof LP_Gateway_Abstract ) {
				$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();

				if ( ! isset( $available_gateways[ $this->payment_method_str ] ) ) {
					throw new Exception( __( 'No payment method is selected', 'learnpress' ), LP_ERROR_NO_PAYMENT_METHOD_SELECTED );
				} else {
					$this->payment_method = $available_gateways[ $this->payment_method_str ];
				}

				$this->payment_method->validate_fields();
			}

			/*if ( $this->payment_method ) {
				$validate = $this->payment_method->validate_fields();
			}*/
		}

		//return $validate;
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
				}
			} else {

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
						$redirect = $order->get_checkout_order_received_url();
						if ( ! empty( $this->guest_email ) ) {
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
		} catch ( Throwable $e ) {
			$result = array(
				'result'   => 'fail',
				'messages' => $e->getMessage(),
			);
			learn_press_send_json( $result );
		}
	}
}

