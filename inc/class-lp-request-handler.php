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

class LP_Request_Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		add_action( 'wp_loaded', array( __CLASS__, 'process_request' ), 99999 );

	}

	/**
	 * Process actions
	 */
	static function process_request() {
		if ( !empty( $_REQUEST ) ) foreach ( $_REQUEST as $key => $value ) {
			do_action( 'learn_press_request_handler_' . $key, $value, $_REQUEST );
		}
	}

	/**
	 * Register new request
	 *
	 * @param     $action
	 * @param     $function
	 * @param int $priority
	 */
	static function register( $action, $function, $priority = 5 ) {
		add_action( 'learn_press_request_handler_' . $action, $function, $priority );
	}

	static function register_ajax($action, $function, $priority = 5){
		//$action, $function, $priority = 5
		add_action( 'learn_press_ajax_handler_' . $action, $function, $priority );

	}
}

new LP_Request_Handler();