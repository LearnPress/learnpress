<?php
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


function learn_press_get_web_hooks() {
	$web_hooks = empty( $GLOBALS['learn_press']['web_hooks'] ) ? array() : (array) $GLOBALS['learn_press']['web_hooks'];
	return apply_filters( 'learn_press_web_hooks', $web_hooks );
}

function learn_press_get_web_hook( $key ){
	$web_hooks = learn_press_get_web_hooks();
	$web_hook  = empty( $web_hooks[ $key ] ) ? false : $web_hooks[ $key ];
	return apply_filters( 'learn_press_web_hook', $web_hook, $key );
}


function learn_press_process_web_hooks() {
	// Grab registered web_hooks
	$web_hooks           = learn_press_get_web_hooks();
	$web_hooks_processed = false;
	// Loop through them and init callbacks

	foreach ( $web_hooks as $key => $param ) {
		if ( !empty( $_REQUEST[$param] ) ) {
			$web_hooks_processed           = true;
			$request_scheme                = is_ssl() ? 'https://' : 'http://';
			$requested_web_hook_url        = untrailingslashit( $request_scheme . $_SERVER['HTTP_HOST'] ) . $_SERVER['REQUEST_URI']; //REQUEST_URI includes the slash
			$parsed_requested_web_hook_url = parse_url( $requested_web_hook_url );
			$required_web_hook_url         = add_query_arg( $param, '1', trailingslashit( get_site_url() ) ); //add the slash to make sure we match
			$parsed_required_web_hook_url  = parse_url( $required_web_hook_url );
			$web_hook_diff                 = array_diff_assoc( $parsed_requested_web_hook_url, $parsed_required_web_hook_url );

			print_r($parsed_requested_web_hook_url);
			print_r($parsed_required_web_hook_url);
			print_r($web_hook_diff);

			if ( empty( $web_hook_diff ) ) { //No differences in the requested webhook and the required webhook
				do_action( 'learn_press_web_hook_' . $param, $_REQUEST );
				echo "XXXXXXXXXXXX";
			} else {

			}
			break; //we can stop processing here... no need to continue the foreach since we can only handle one webhook at a time
		}
	}
	if ( $web_hooks_processed ) {
		do_action( 'learn_press_web_hooks_processed' );
		wp_die( __( 'LearnPress webhook process Complete', 'learn_press' ), __( 'LearnPress webhook process Complete', 'learn_press' ), array( 'response' => 200 ) );
	}
}

add_action( 'wp', 'learn_press_process_web_hooks' );

learn_press_register_web_hook( 'cart', 'add-course-to-cart' );

function add_to_cart( $params ){
	print_r($params);
	die('xxxx');
}
add_action( 'learn_press_web_hook_add-course-to-cart', 'add_to_cart' );