<?php

class LP_Factory {
	/**
	 * @return LP_User_CURD
	 */
	public static function get_user_factory() {
		return LP_Object_Data_CURD::get( 'user' );
	}

	/**
	 * @return LP_Order_CURD
	 */
	public static function get_order_factory() {
		return LP_Object_Data_CURD::get( 'order' );
	}
}

add_filter( 'query', function ( $q ) {
	if ( ! preg_match( '!INSERT INTO `wp_learnpress_user_items`!', $q ) ) {
		return $q;
	}

	LP_Debug::instance()->add( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), '', false, true );
	LP_Debug::instance()->add( $q, '', false, true );

	return $q;
} );

//add_action('wp_redirect', function ($r){
//	learn_press_debug(debug_backtrace());die();
//});