<?php
/**
 * Common functions to process the webhooks
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

/**
 * Register a webhook
 *
 * @param $key
 * @param $param
 */
function learn_press_register_web_hook( $key, $param ) {
	if ( !$key ) {
		return;
	}
	if ( empty ( $GLOBALS['learn_press']['web_hooks'] ) ) {
		$GLOBALS['learn_press']['web_hooks'] = array();
	}
	$GLOBALS['learn_press']['web_hooks'][$key] = $param;
	do_action( 'learn_press_register_web_hook', $key, $param );
}

/**
 * Return all registered webhooks
 *
 * @return mixed|void
 */
function learn_press_get_web_hooks() {
	$web_hooks = empty( $GLOBALS['learn_press']['web_hooks'] ) ? array() : (array) $GLOBALS['learn_press']['web_hooks'];
	return apply_filters( 'learn_press_web_hooks', $web_hooks );
}

/**
 * Return a registered webhook by it's name
 *
 * @param $key
 *
 * @return mixed|void
 */
function learn_press_get_web_hook( $key ) {
	$web_hooks = learn_press_get_web_hooks();
	$web_hook  = empty( $web_hooks[$key] ) ? false : $web_hooks[$key];
	return apply_filters( 'learn_press_web_hook', $web_hook, $key );
}

/**
 * Process all registered webhooks
 */
function learn_press_process_web_hooks() {
	$web_hooks           = learn_press_get_web_hooks();
	$web_hooks_processed = false;
	foreach ( $web_hooks as $key => $param ) {
		if ( !empty( $_REQUEST[$param] ) ) {
			$web_hooks_processed           = true;
			$request_scheme                = is_ssl() ? 'https://' : 'http://';
			$requested_web_hook_url        = untrailingslashit( $request_scheme . $_SERVER['HTTP_HOST'] ) . $_SERVER['REQUEST_URI'];
			$parsed_requested_web_hook_url = parse_url( $requested_web_hook_url );
			$required_web_hook_url         = add_query_arg( $param, '1', trailingslashit( get_site_url() ) );
			$parsed_required_web_hook_url  = parse_url( $required_web_hook_url );
			$web_hook_diff                 = array_diff_assoc( $parsed_requested_web_hook_url, $parsed_required_web_hook_url );

			if ( empty( $web_hook_diff ) ) {
				do_action( 'learn_press_web_hook_' . $param, $_REQUEST );
			} else {

			}
			break;
		}
	}
	if ( $web_hooks_processed ) {
		do_action( 'learn_press_web_hooks_processed' );
		wp_die( __( 'LearnPress webhook process Complete', 'learnpress' ), __( 'LearnPress webhook process Complete', 'learnpress' ), array( 'response' => 200 ) );
	}
}

add_action( 'wp_loaded', 'learn_press_process_web_hooks', 999 );