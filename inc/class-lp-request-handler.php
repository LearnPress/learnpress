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

if ( !defined( 'ABSPATH' ) ) {
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

		//add_action( 'wp_loaded', array( __CLASS__, 'get_header' ), - 1000 );
		//add_action( 'wp_head', array( __CLASS__, 'process_request' ), 1000 );

		//add_action( 'wp_loaded', array( __CLASS__, 'get_header' ), - 1000 );
		//add_action( 'admin_head', array( __CLASS__, 'process_request' ), 1000 );
		add_action( 'wp', array( __CLASS__, 'process_request' ), 50 );
		LP_Request_Handler::register( 'purchase-course', 'learn_press_purchase_course_handler', 20 );
		LP_Request_Handler::register( 'enroll-course', 'learn_press_purchase_course_handler', 20 );
	}

	public static function get_header() {
		ob_start();
	}

	/**
	 * Process actions
	 */
	public static function process_request() {
		//self::$_head = ob_get_clean();
		if ( !empty( $_REQUEST ) ) foreach ( $_REQUEST as $key => $value ) {
			do_action( 'learn_press_request_handler_' . $key, $value, $_REQUEST );
		}
		//echo self::$_head;
	}

	/**
	 * Register new request
	 *
	 * @param     $action
	 * @param     $function
	 * @param int $priority
	 */
	public static function register( $action, $function, $priority = 5 ) {
		add_action( 'learn_press_request_handler_' . $action, $function, $priority );
	}

	public static function register_ajax( $action, $function, $priority = 5 ) {
		//$action, $function, $priority = 5
		add_action( 'learn_press_ajax_handler_' . $action, $function, $priority );

	}
}

LP_Request_Handler::init();