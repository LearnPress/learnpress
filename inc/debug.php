<?php
/**
 * Search regexp \'[a-z]+ - [a-z]+\'
 */

/**
 * use http://example.com?debug=yes to execute the code in this file
 */
class LP_Unit_Test {
	public static function init() {
		//add_action( 'get_header', array( __CLASS__, 'test_emails' ) );
	}

	public static function test_emails() {
		global $wp_rewrite;
		if ( get_post_type( 2147 ) === LP_ORDER_CPT ) {
			$emailer       = LP_Emails::instance();
			$email         = $emailer->emails['LP_Email_Completed_Order_User'];
			$email->enable = true;
			$email->trigger();
			learn_press_debug( $email );
			die();
		}
	}
}

LP_Unit_Test::init();