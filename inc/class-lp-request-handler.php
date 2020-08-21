<?php

/**
 * Class LP_Request
 *
 * Process actions by request param
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LP_Request
 */
class LP_Request {

	/**
	 * @var bool
	 */
	public static $ajax_shutdown = true;

	/**
	 * Constructor
	 */
	public static function init() {

		self::$ajax_shutdown = learn_press_is_ajax();

		if ( is_admin() ) {
			add_action( 'init', array( __CLASS__, 'process_request' ), 50 );
		} else {
			add_action( 'template_include', array( __CLASS__, 'process_request' ), 50 );
		}

		self::register( 'lp-ajax', array( __CLASS__, 'do_ajax' ) );

		/**
		 * @see LP_Request::purchase_course()
		 */
		LP_Request::register( 'purchase-course', array( __CLASS__, 'purchase_course' ), 20 );
		LP_Request::register( 'enroll-course', array( __CLASS__, 'purchase_course' ), 20 );

		/**
		 * @see LP_Request::do_checkout()
		 */
		add_action( 'learn-press/purchase-course-handler', array( __CLASS__, 'do_checkout' ), 10, 3 );
		add_action( 'learn-press/enroll-course-handler', array( __CLASS__, 'do_checkout' ), 10, 3 );

		/**
		 * @see LP_Request::do_enroll()
		 */
		add_action( 'learn-press/purchase-course-handler/enroll', array( __CLASS__, 'do_enroll' ), 10, 4 );
		add_action( 'learn-press/enroll-course-handler/enroll', array( __CLASS__, 'do_enroll' ), 10, 4 );

		add_filter( 'learn-press/add-to-cart-redirect', array( __CLASS__, 'check_checkout_page' ) );
		add_filter( 'learn-press/checkout-no-payment-result', array( __CLASS__, 'maybe_redirect_checkout' ), 10, 2 );
		add_filter( 'learn-press/purchase-course-id', array( __CLASS__, 'maybe_enroll_course' ), 10, 2 );
		add_action( 'learn-press/add-to-cart-order-total-empty', array( __CLASS__, 'maybe_redirect_enroll' ), 10, 3 );

		add_action( 'init', array( 'LP_Forms_Handler', 'init' ), 10 );
	}

	public static function maybe_redirect_checkout( $result, $order_id ) {
		$course_id = get_transient( 'checkout_enroll_course_id' );
		if ( ! $course_id ) {
			if ( isset( $_REQUEST['enroll-course'] ) && $_REQUEST['enroll-course'] ) {
				$course_id = absint( $_REQUEST['enroll-course'] );
			}
		}
		if ( $course_id ) {
			$course       = learn_press_get_course( $course_id );
			$course_items = $course->get_items();
			$first_item   = ( $course_items[0] ) ? $course_items[0] : 0;
			self::do_enroll( $course_id, $order_id, 'enroll-course', $first_item );
			delete_transient( 'checkout_enroll_course_id' );
			unset( $result['redirect'] );
		}

		return $result;
	}

	public static function maybe_enroll_course( $course_id, $action ) {
		if ( 'enroll-course' === $action ) {
			set_transient( 'checkout_enroll_course_id', $course_id );
		}

		return $course_id;
	}

	public static function maybe_redirect_enroll( $course_id, $cart_id, $action ) {
		if ( $course = learn_press_get_course( $course_id ) ) {
			if ( $course->is_required_enroll() && ! get_current_user_id() ) {
				if ( ! $redirect = apply_filters( 'learn-press/enroll-course-redirect-login', learn_press_get_login_url( add_query_arg( 'enroll-course', $course_id, $course->get_permalink() ) ) ) ) {
					$redirect = $course->get_permalink();
				}
				wp_redirect( $redirect );
				exit();
			}
		}
	}

	/**
	 * Purchase course action, when user clicking on "Buy this course" or "Enroll" button.
	 *
	 * @param int    $course_id
	 * @param string $action
	 *
	 * @return bool
	 */
	public static function purchase_course( $course_id, $action ) {

		$course_id = apply_filters( 'learn-press/purchase-course-id', $course_id, $action );
		$course    = learn_press_get_course( $course_id );//echo'<pre>dsad';print_r($course->get_items());die;

		if ( ! $course ) {
			return false;
		}

		LP()->session->set( 'order_awaiting_payment', '' );

		$user          = learn_press_get_current_user();
		$order         = apply_filters( 'learn-press/get-course-order', $user->get_course_order( $course_id ), $action, $user );
		$add_to_cart   = false;
		$enroll_course = false;

		try {
			/**
			 * If there is no order of user related to course.
			 */
			if ( ! $order || $order->has_invalid_status() ) {
				$add_to_cart = true;
			} else {

				switch ( $action ) {

					// Purchase button
					case 'purchase-course':
						/**
						 * If user has already purchased course but did not finish.
						 * This mean user has to finish course before purchasing that course itself.
						 */
						if ( $order->has_status( array( 'completed' ) ) && ! $user->has_course_status( $course->get_id(), array( 'finished' ) ) ) {
							throw new Exception( __( 'You have already purchased this course and haven\'t finished it.', 'learnpress' ) );
						}

						/**
						 * If user has already purchases course but the order is processing.
						 * Just wait for order is completed.
						 */
						if ( $order->has_status( array( 'processing' ) ) ) {
							throw new Exception( __( 'You have already purchased this course and the order is still processing...', 'learnpress' ) );
						}

						/**
						 * If there is an order for this course,
						 * just update this order.
						 */
						if ( $order->has_status( 'pending' ) ) {
							// TODO: update order
							LP()->session->set( 'order_awaiting_payment', $order->get_id() );
						}
						$add_to_cart = true;

						break;

					// Enroll button
					case 'enroll-course':

						if ( $order->has_status( 'completed' ) ) {
							/**
							 * Order is completed and course is finished/enrolled
							 */
							if ( $user->has_course_status( $course->get_id(), array( 'finished', 'enrolled' ) ) ) {
								throw new Exception( __( 'You have finished course.', 'learnpress' ) );
							} else {
								// TODO: enroll
								//do_action( "learn-press/{$action}-handler", $course_id, $order->get_id() );
								$enroll_course = true;
							}
						}
						//
						if ( $order->has_status( array( 'pending', 'processing' ) ) ) {
							// If course is FREE
							if ( $course->is_free() ) {
								//do_action( "learn-press/{$action}-handler", $course_id, $order->get_id() );
								// TODO: Complete order + enroll
								$enroll_course = true;

							} else {
								throw new Exception( __( 'You have to purchase the course before enrolling.', 'learnpress' ) );
							}
						}

						if ( $order->has_status( 'cancelled' ) ) {
							$add_to_cart = true;
						}
				}
			}

			// Add to cart + add new order
			if ( $add_to_cart ) {
				$cart = LP()->cart;

				// If cart is disabled then clean the cart
				if ( ! learn_press_enable_cart() ) {
					$order_awaiting_payment = LP()->session->order_awaiting_payment;
					$cart->empty_cart();
					LP()->session->order_awaiting_payment = $order_awaiting_payment;
				}

				if ( $cart_id = $cart->add_to_cart( $course_id, 1, array() ) ) {
					/**
					 * @see LP_Request::do_checkout()
					 */
					do_action( "learn-press/{$action}-handler", $course_id, $cart_id, $action );
				}
			} elseif ( $enroll_course ) {
				/**
				 * @see LP_Request::do_enroll()
				 */
				$course_items = $course->get_items();
				$first_item   = ( $course_items[0] ) ? $course_items[0] : 0;
				do_action( "learn-press/{$action}-handler/enroll", $course_id, $order->get_id(), $action, $first_item );
			}

			if ( ! $add_to_cart && ! $enroll_course ) {
				throw new Exception( __( 'Invalid action.', 'learnpress' ) );
			}
		}
		catch ( Exception $e ) {
			if ( $e->getMessage() ) {
				learn_press_add_message( $e->getMessage(), 'error' );
			}

			// TODO: anything here?

			return false;
		}

		return true;
	}

	/**
	 * Function callback
	 *
	 * @param int    $course_id
	 * @param string $cart_id
	 * @param string $action
	 *
	 * @return mixed
	 */
	public static function do_checkout( $course_id, $cart_id, $action ) {

		$user   = learn_press_get_current_user();
		$course = learn_press_get_course( $course_id );
		if ( ! $course ) {
			return false;
		}
		/** # comment because maybe conflict with eduma
		 * if ( 'enroll-course' == $action ) {
		 * if ( ! $user->can_enroll_course( $course_id ) ) {
		 * learn_press_add_message(
		 * sprintf( __( 'You can not enroll course &quot;%s&quot', 'learnpress' ), get_the_title( $course_id ) ),
		 * 'error'
		 * );
		 *
		 * return false;
		 * }
		 * }
		 **/
		$cart = LP()->cart;

		if ( ! $cart->get_items() ) {
			return false;
		}

		/**
		 * Redirect to checkout page if cart total is greater than 0
		 */
		if ( 0 < $cart->total ) {
			if ( $redirect = apply_filters( 'learn-press/add-to-cart-redirect', learn_press_get_page_link( 'checkout' ), $course_id, $cart_id, $action ) ) {
				wp_redirect( $redirect );
				exit();
			}

			learn_press_add_message( __( 'Checkout page hasn\'t been setup' ) );
		} else {
			/// Need?
			if ( 'enroll-course' == $action ) {
				if ( ! $user->can_enroll_course( $course_id ) ) {
					learn_press_add_message(
						sprintf( __( 'You can not enroll course &quot;%s&quot', 'learnpress' ), get_the_title( $course_id ) ),
						'error'
					);

					return false;
				}
			}
			do_action( 'learn-press/add-to-cart-order-total-empty', $course_id, $cart_id, $action );
			$checkout = LP()->checkout();
			$checkout->process_checkout();
		}

		return true;
	}

	/**
	 * @param int    $course_id
	 * @param int    $order_id
	 * @param string $action
	 */
	public static function do_enroll( $course_id, $order_id, $action, $item_id = 0 ) {

		//		if ( ! LP_Nonce_Helper::verify_course( LP_Request::get_string( 'enroll-course-nonce' ), 'enroll' ) ) {
		//			wp_die( __( 'Invalid request!', 'learnpress' ) );
		//		}

		if ( ! $course = learn_press_get_course( $course_id ) ) {
			wp_die( __( 'Invalid request!', 'learnpress' ) );
		}

		$user     = LP_Global::user();
		$redirect = get_the_permalink( $course_id );

		if ( ! $user->can_enroll_course( $course_id ) && 'enroll-course' == $action ) {
			learn_press_add_message(
				sprintf( __( 'You can not enroll course &quot;%s&quot', 'learnpress' ), get_the_title( $course_id ) ),
				'error'
			);
		} else {
			$thing = $user->enroll( $course_id, $order_id );

			if ( is_wp_error( $thing ) ) {
				learn_press_add_message(
					$thing->get_error_message(),
					'error'
				);

				if ( $thing->get_error_code() == 10002 ) {
					$redirect = apply_filters( 'learn-press/enroll-course-redirect-login', learn_press_get_login_url( add_query_arg( 'enroll-course', $course_id, $redirect ) ) );
				}
			} elseif ( $thing ) {
				learn_press_add_message(
					sprintf( '%s &quot;%s&quot', __( 'Congrats! You have enrolled ', 'learnpress' ), get_the_title( $course_id ) ),
					'success'
				);
				if ( $item_id ) {
					$redirect = learn_press_get_course_item_permalink( $course_id, $item_id );
				}
			}

		}
		wp_redirect( apply_filters( 'learn-press/enroll-course-redirect', $redirect ) );
		exit();
	}

	/**
	 * Filter to add-to-cart redirect to show message for admin
	 * if the checkout page is not setup.
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function check_checkout_page( $url ) {
		$page_id = learn_press_get_page_id( 'checkout' );
		$page    = false;
		if ( $page_id ) {
			$page = get_post( $page_id );
		}
		if ( ! $page ) {
			// Only show for admin
			if ( current_user_can( 'manage_options' ) ) {
				learn_press_add_message( __( 'Checkout page hasn\'t been setup or page does not exists.', 'learnpress' ) );
			} else {
				learn_press_add_message( __( 'Checkout error! Please contact with admin for getting more information.', 'learnpress' ) );
			}
		}

		return $url;
	}

	public static function get_header() {
		ob_start();
	}

	/**
	 * Process actions
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public static function process_request( $template ) {

		if ( ! empty( $_REQUEST ) ) {
			foreach ( $_REQUEST as $key => $value ) {
				do_action( 'learn_press_request_handler_' . $key, $value, $key );
			}
		}

		return $template;
	}

	/**
	 * Register new request
	 *
	 * @param string|array $action
	 * @param mixed        $function
	 * @param int          $priority
	 */
	public static function register( $action, $function = '', $priority = 5 ) {
		if ( is_array( $action ) ) {
			foreach ( $action as $item ) {
				$item = wp_parse_args( $item, array( 'action' => '', 'callback' => '', 'priority' => 5 ) );
				if ( ! $item['action'] || ! $item['callback'] ) {
					continue;
				}
				list( $action, $callback, $priority ) = array_values( $item );
				add_action( 'learn_press_request_handler_' . $action, $callback, $priority, 2 );
			}
		} else {
			add_action( 'learn_press_request_handler_' . $action, $function, $priority, 2 );
		}
	}

	/**
	 * Register ajax action.
	 * Add ajax into queue by an action and then LP check if there is a request
	 * with key lp-ajax=action-name then do the action "action-name". By default,
	 * ajax action is called if user is logged. But, it can be call in case user
	 * is not logged in if the action is passed with key :nopriv at the end.
	 *
	 * E.g:
	 *      + Only for user is logged in
	 *      LP_Request::register_ajax( 'action', 'function_to_call', 5 )
	 *
	 *      + For guest
	 *      LP_Request::register_ajax( 'action:nopriv', 'function_to_call', 5 )
	 *
	 * @param string $action
	 * @param mixed  $function
	 * @param int    $priority
	 */
	public static function register_ajax( $action, $function, $priority = 5 ) {

		if ( is_array( $action ) ) {
			foreach ( $action as $args ) {
				if ( ! empty( $args['action'] ) && ! empty( $args['callback'] ) ) {
					self::register_ajax( $args['action'], $args['callback'], ! empty( $args['priority'] ) ? $args['priority'] : 5 );
				}
			}

			return;
		}
		$actions = self::parse_action( $action );

		if ( isset( $actions['nonce'] ) ) {
			add_filter( 'learn-press/ajax/verify-none/' . $actions['action'], array( __CLASS__, 'verify_nonce' ) );
		}

		//$action, $function, $priority = 5
		add_action( 'learn-press/ajax/' . $actions['action'], $function, $priority );

		/**
		 * No requires logged in?
		 */
		if ( isset( $actions['nopriv'] ) ) {
			//$action, $function, $priority = 5
			add_action( 'learn-press/ajax/no-priv/' . $actions['action'], $function, $priority );
		}

		/**
		 * @deprecated
		 */
		add_action( 'learn_press_ajax_handler_' . $action, $function, $priority );
	}

	/**
	 * Do ajax if there is a 'lp-ajax' in $_REQUEST
	 *
	 * @param string $action
	 */
	public static function do_ajax( $action ) {

		if ( ! defined( 'LP_DOING_AJAX' ) ) {
			define( 'LP_DOING_AJAX', true );
		}

		LP_Gateways::instance()->get_available_payment_gateways();

		if ( has_filter( 'learn-press/ajax/verify-none/' . $action ) ) {
			if ( ! self::verify_nonce( $action ) ) {
				die( '0' );
			}
		}

		if ( is_user_logged_in() ) {
			/**
			 * @deprecated
			 */
			do_action( 'learn_press_ajax_handler_' . $action );

			$has_action = has_action( 'learn-press/ajax/' . $action );

			/**
			 * @since 3.0
			 */
			do_action( 'learn-press/ajax/' . $action );
		} else {

			$has_action = has_action( 'learn-press/ajax/no-priv/' . $action );

			/**
			 * @since 3.0
			 */
			do_action( 'learn-press/ajax/no-priv/' . $action );
		}

		if ( $has_action && self::$ajax_shutdown ) {
			die( '{END_AJAX}' );
		}
	}

	public static function verify_nonce( $action, $nonce = '' ) {
		return wp_verify_nonce( $nonce ? $nonce : self::get_string( "{$action}-nonce" ), $action );
	}

	public static function parse_action( $action ) {
		$args    = explode( ':', $action );
		$actions = array(
			'action' => $args[0]
		);

		if ( sizeof( $args ) > 1 ) {
			array_shift( $args );
			foreach ( $args as $arg ) {
				$actions[ $arg ] = $arg;
			}
		}

		return $actions;
	}

	/**
	 * Get variable value from Server environment.
	 *
	 * @param string $var
	 * @param mixed  $default
	 * @param string $type
	 * @param string $env
	 *
	 * @return mixed
	 */
	public static function get( $var, $default = false, $type = '', $env = 'request' ) {
		switch ( strtolower( $env ) ) {
			case 'post':
				$env = $_POST;
				break;
			case 'get':
				$env = $_GET;
				break;
			case 'wp':
				global $wp;
				$env = $wp->query_vars;
				break;
			default:
				$env = $_REQUEST;
		}
		$return = array_key_exists( $var, $env ) ? $env[ $var ] : $default;
		switch ( $type ) {
			case 'int':
				$return = intval( $return );
				break;
			case 'float':
				$return = floatval( $return );
				break;
			case 'bool':
				try {
					$value = strtolower( $return );
				}
				catch ( Exception $e ) {
					$value = $return;
				}

				if ( in_array( $value, array( 'true', 'yes', 'on', 'enable' ) ) ) {
					$return = true;
				} elseif ( in_array( $value, array( 'false', 'no', 'off', 'disable' ) ) ) {
					$return = false;
				} else {
					$return = ! ! $return;
				}
				break;
			case 'string':
				$return = (string) $return;
				break;
			case 'array':
				$return = (array) $return;
				break;
		}

		LP_Helper::sanitize_params_submitted( $return );

		return $return;
	}

	/**
	 * Get value int from environment.
	 *
	 * @param string $var
	 * @param mixed  $default
	 * @param string $env
	 *
	 * @return int
	 */
	public static function get_int( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'int', $env );
	}

	/**
	 * Get value float from environment.
	 *
	 * @param string $var
	 * @param mixed  $default
	 * @param string $env
	 *
	 * @return float
	 */
	public static function get_float( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'float', $env );
	}

	/**
	 * Get value bool from environment.
	 *
	 * @param string $var
	 * @param mixed  $default
	 * @param string $env
	 *
	 * @return bool
	 */
	public static function get_bool( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'bool', $env );
	}

	/**
	 * Get value string from environment.
	 *
	 * @param string $var
	 * @param mixed  $default
	 * @param string $env
	 *
	 * @return string
	 */
	public static function get_string( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'string', $env );
	}

	/**
	 * Get value array from environment.
	 *
	 * @param string $var
	 * @param mixed  $default
	 * @param string $env
	 *
	 * @return array
	 */
	public static function get_array( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'array', $env );
	}

	/**
	 * Get value from $_POST.
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get_post( $var, $default = false, $type = '' ) {
		return self::get( $var, $default, $type, 'post' );
	}

	/**
	 * Get value int from $_POST.
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return int
	 */
	public static function get_post_int( $var, $default = false ) {
		return self::get_post( $var, $default, 'int' );
	}

	/**
	 * Get value float from $_POST.
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return float
	 */
	public static function get_post_float( $var, $default = false ) {
		return self::get_post( $var, $default, 'float' );
	}

	/**
	 * Get value bool from $_POST.
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return bool
	 */
	public static function get_post_bool( $var, $default = false ) {
		return self::get_post( $var, $default, 'bool' );
	}

	/**
	 * Get value string from $_POST.
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return string
	 */
	public static function get_post_string( $var, $default = false ) {
		return self::get_post( $var, $default, 'string' );
	}

	/**
	 * Get value array from $_POST.
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return array
	 */
	public static function get_post_array( $var, $default = false ) {
		return self::get_post( $var, $default, 'array' );
	}

	/**
	 * Get email field and validate.
	 *
	 * @param string $var
	 * @param bool   $default
	 *
	 * @return bool|string
	 */
	public static function get_email( $var, $default = false ) {
		$email = self::get_string( $var, $default );
		if ( ! is_email( $email ) ) {
			$email = $default;
		}

		return $email;
	}

	/**
	 * Get a batch of params from request into an array.
	 *
	 * @return array
	 */
	public static function get_list() {
		if ( func_num_args() < 1 ) {
			return array();
		}

		$list = array();
		foreach ( func_get_args() as $key ) {
			$list[ $key ] = self::get( $key );
		}

		return $list;
	}

	/**
	 * Parse string from request to array by comma.
	 *
	 * @param string $var
	 * @param string $separator
	 *
	 * @return array
	 */
	public static function get_list_array( $var, $separator = ',' ) {
		$list = self::get_string( $var );

		if ( ! $list ) {
			return array();
		}

		if ( $separator === ',' ) {
			$list = preg_split( '!\s?,\s?!', $list );
		} else {
			$list = explode( $separator, $list );
		}

		return array_map( 'trim', $list );
	}

	/**
	 * Get param 'redirect' in request.
	 *
	 * @param string $default
	 *
	 * @return string
	 */
	public static function get_redirect( $default = '' ) {
		if ( $redirect = self::get_string( 'redirect' ) ) {
			$redirect = urldecode( $redirect );
		} else {
			$redirect = $default;
		}

		return $redirect;
	}
}

LP_Request::init();

// Backward compatibility for 3.2.6.5
class LP_Request_Handler extends LP_Request {

}