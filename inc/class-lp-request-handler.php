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
	 * @var null
	 */
	protected static $_head = null;

	/**
	 * Constructor
	 */
	public static function init() {
		if ( is_admin() ) {
			add_action( 'init', array( __CLASS__, 'process_request' ), 50 );
		} else {
			//add_action( 'wp', array( __CLASS__, 'process_request' ), 50 );
			add_action( 'template_include', array( __CLASS__, 'process_request' ), 50 );
		}

		add_action( 'get_header', array( __CLASS__, 'clean_cache' ), 1000000 );
		add_action( 'save_post', array( __CLASS__, 'clean_cache' ), 1000000 );

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
		add_action( 'learn-press/purchase-course-handler/enroll', array( __CLASS__, 'do_enroll' ), 10, 3 );
		add_action( 'learn-press/enroll-course-handler/enroll', array( __CLASS__, 'do_enroll' ), 10, 3 );

		add_action( 'learn-press/add-to-cart-redirect', array( __CLASS__, 'check_checkout_page' ) );
	}

	public static function verify_nonce( $action, $nonce = '' ) {
		return wp_verify_nonce( $nonce ? $nonce : self::get_string( "{$action}-nonce" ), $action );
	}

	/**
	 * Purchase course action.
	 * Perform this action when user clicking on "Buy this course" or "Enroll" button.
	 *
	 * @param int    $course_id
	 * @param string $action
	 *
	 * @return bool
	 */
	public static function purchase_course( $course_id, $action ) {
		$course_id = apply_filters( 'learn-press/purchase-course-id', $course_id );
		$course    = learn_press_get_course( $course_id );


		if ( ! $course ) {
			return false;
		}

		LP()->session->set( 'order_awaiting_payment', '' );

		$user          = learn_press_get_current_user();
		$order         = $user->get_course_order( $course_id );
		$add_to_cart   = false;
		$enroll_course = false;
		try {
			/**
			 * If there is no order of user related to course.
			 */
			if ( ! $order ) {
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
							throw new Exception( __( 'You have purchased course and has not finished.', 'learnpress' ) );
						}

						/**
						 * If user has already purchases course but the order is processing.
						 * Just wait for order is completed.
						 */
						if ( $order->has_status( array( 'processing' ) ) ) {
							throw new Exception( __( 'You have purchased course and it is processing.', 'learnpress' ) );
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
								throw new Exception( __( 'You have to purchase course before enrolling.', 'learnpress' ) );
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
				do_action( "learn-press/{$action}-handler/enroll", $course_id, $order->get_id(), $action );
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

		$course = learn_press_get_course( $course_id );
		if ( ! $course ) {
			return false;
		}

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
		} else {
			/// Need?
			do_action( 'learn-press/add-to-cart-order-total-empty' );
			$checkout = LP()->checkout();
			$checkout->process_checkout();
		}

		return true;
	}

	public static function do_enroll( $course_id, $order_id, $action ) {
		$user = LP_Global::user();
		$user->enroll( $course_id, $order_id );
		learn_press_add_message(
			sprintf( __( 'Congrats! You have enrolled &quot;%s&quot', 'learnpress' ), get_the_title( $course_id ) ),
			'success',
			array(
				'position'  => 'fixedInOut',
				'delay-in'  => 10,
				'delay-out' => 5000
			)
		);
		wp_redirect( get_the_permalink( $course_id ) );
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
		// Only show for admin
		if ( current_user_can( 'manage_options' ) ) {
			$page_id = learn_press_get_page_id( 'checkout' );
			$page    = false;
			if ( $page_id ) {
				$page = get_post( $page_id );
			}
			if ( ! $page ) {
				learn_press_add_message( __( 'Checkout page is not setup or page does not exists.', 'learnpress' ) );
			}
		}

		return $url;
	}

	public static function clean_cache() {
		if ( strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' ) {
			add_filter( 'wp_redirect', array( __CLASS__, 'redirect' ) );
			LP_Cache::flush();
		}
	}

	public static function redirect( $url ) {
		remove_filter( 'wp_redirect', array( __CLASS__, 'redirect' ) );

		return add_query_arg( 'lp-reload', 'yes', $url );
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

	public static function register_ajax( $action, $function, $priority = 5 ) {
		//$action, $function, $priority = 5
		add_action( 'learn_press_ajax_handler_' . $action, $function, $priority );

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
				$return = ! ! $return;
				break;
			case 'string':
				$return = (string) $return;
				break;
			case 'array':
				$return = (array) $return;
				break;
		}

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
}

LP_Request::init();

// Backward compatibility
class LP_Request_Handler extends LP_Request {

}