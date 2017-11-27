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


//add_action('wp_redirect', function ($r){
//	learn_press_debug(debug_backtrace());die();
//});