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
		if(strtolower($_SERVER['REQUEST_METHOD']) == 'post'){
			LP_Cache::flush();
			//wp_cache_delete( 'course-curriculum', 'learnpress');
		}
		//add_action( 'wp_loaded', array( __CLASS__, 'get_header' ), - 1000 );
		//add_action( 'wp_head', array( __CLASS__, 'process_request' ), 1000 );

		//add_action( 'wp_loaded', array( __CLASS__, 'get_header' ), - 1000 );
		//add_action( 'admin_head', array( __CLASS__, 'process_request' ), 1000 );
		if ( is_admin() ) {
			add_action( 'init', array( __CLASS__, 'process_request' ), 50 );
		} else {
			add_action( 'wp', array( __CLASS__, 'process_request' ), 50 );
		}

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
	 * @param string|array $action
	 * @param mixed        $function
	 * @param int          $priority
	 */
	public static function register( $action, $function = '', $priority = 5 ) {
		if ( is_array( $action ) ) {
			foreach ( $action as $item ) {
				$item = wp_parse_args( $item, array( 'action' => '', 'callback' => '', 'priority' => 5 ) );
				if ( !$item['action'] || !$item['callback'] ) {
					continue;
				}
				list( $action, $callback, $priority ) = array_values($item);
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