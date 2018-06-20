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
		add_action( 'shutdown', array( __CLASS__, 'shutdown' ) );
	}

	public static function test_emails() {
		global $wp_rewrite;
		$emailer       = LP_Emails::instance();
		$email         = $emailer->emails['LP_Email_Completed_Order_User'];
		$email->enable = true;
		$email->trigger( 2147 );
		learn_press_debug( $email );
		die();
	}

	public static function shutdown() {
		if ( $times = LP_Debug::getLogTimes() ) {
			foreach ( $times as $key => $time ) {
				echo "Execute Time ({$key}) = " . array_sum( $time ) . '(' . sizeof( $time ) . ')';
			}
		}
	}
}

LP_Unit_Test::init();