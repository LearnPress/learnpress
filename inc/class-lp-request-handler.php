<?php

/**
 * Class LP_Request_Handler
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
 * Class LP_Request_Handler
 */
class LP_Request_Handler {

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
			add_action( 'wp', array( __CLASS__, 'process_request' ), 50 );
		}

		add_action( 'get_header', array( __CLASS__, 'clean_cache' ), 1000000 );
		add_action( 'save_post', array( __CLASS__, 'clean_cache' ), 1000000 );

		/**
		 * @see LP_Request_Handler::purchase_course()
		 */
		LP_Request_Handler::register( 'purchase-course', array( __CLASS__, 'purchase_course' ), 20 );
		LP_Request_Handler::register( 'enroll-course', array( __CLASS__, 'purchase_course' ), 20 );

		add_action( 'learn-press/purchase-course-handler', array( __CLASS__, 'do_checkout' ), 10, 3 );
		add_action( 'learn-press/enroll-course-handler', array( __CLASS__, 'do_checkout' ), 10, 3 );

		add_action( 'learn-press/purchase-course-handler/enroll', array( __CLASS__, 'do_enroll' ), 10, 3 );
		add_action( 'learn-press/enroll-course-handler/enroll', array( __CLASS__, 'do_enroll' ), 10, 3 );
		add_action( 'learn-press/add-to-cart-redirect', array( __CLASS__, 'check_checkout_page' ) );
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
						if ( $order->has_status( array( 'completed' ) ) ) {
							if ( ! $user->has_course_status( $course->get_id(), array( 'finished' ) ) ) {
								throw new Exception( __( 'You have purchased course and has not finished.', 'learnpress' ) );
							} else {
								$add_to_cart = true;
							}
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
						} else {
							$add_to_cart = true;
						}

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
					$cart->empty_cart();
				}

				if ( $cart_id = $cart->add_to_cart( $course_id, 1, array() ) ) {
					/**
					 * @see LP_Request_Handler::do_checkout()
					 */
					do_action( "learn-press/{$action}-handler", $course_id, $cart_id, $action );
				}
			} elseif ( $enroll_course ) {
				/**
				 * @see LP_Request_Handler::do_enroll()
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
			print_r( $e->getMessage() );
			die();

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
			do_action('learn-press/add-to-cart-order-total-empty');
			$checkout = LP()->checkout();
			$checkout->process_checkout();
		}
		return true;
	}

	public static function do_enroll( $course_id, $order_id, $action ) {
		echo __FUNCTION__;
		print_r( func_get_args() );
		die();
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
	 */
	public static function process_request() {
		if ( ! empty( $_REQUEST['lp-reload'] ) ) {
			wp_redirect( remove_query_arg( 'lp-reload' ) );
			exit();
		}
		//print_r($_SERVER['REQUEST_METHOD']);die();
		if ( ! empty( $_REQUEST ) ) {
			foreach ( $_REQUEST as $key => $value ) {
				do_action( 'learn_press_request_handler_' . $key, $value, $key );
			}
		}
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
}

LP_Request_Handler::init();