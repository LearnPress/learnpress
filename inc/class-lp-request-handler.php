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
	}

	/**
	 * Purchase course action.
	 * Perform this action when user clicking on "Buy this course" or "Enroll" button.
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 */
	public static function purchase_course( $course_id ) {

		$course_id = apply_filters( 'learn-press/purchase-course-id', $course_id );
		$course    = learn_press_get_course( $course_id );

		if ( ! $course ) {
			return false;
		}

		print_r( $course->get_data( 'require_enrollment' ) );


		//LP()->cart->purchase_course_handler( $course_id );
		$cart = LP()->cart;
		$cart->add_to_cart( $course_id, 1, $_POST );

		print_r( $cart );
		learn_press_print_messages();
		die();
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
				do_action( 'learn_press_request_handler_' . $key, $value, $_REQUEST );
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
				add_action( 'learn_press_request_handler_' . $action, $callback, $priority );
			}
		} else {
			add_action( 'learn_press_request_handler_' . $action, $function, $priority );
		}
	}

	public static function register_ajax( $action, $function, $priority = 5 ) {
		//$action, $function, $priority = 5
		add_action( 'learn_press_ajax_handler_' . $action, $function, $priority );

	}
}

LP_Request_Handler::init();