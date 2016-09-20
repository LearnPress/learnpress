<?php

/**
 * Class LP_User_Guest
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_User_Guest extends LP_Abstract_User {
	/**
	 * @param $the_user
	 */
	public function __construct( $the_user ) {
		$this->id   = $the_user;
		$this->user = new WP_User( 0 );
	}

	/**
	 * @static
	 *
	 * @return LP_User_Guest
	 */
	public static function instance() {
		static $user;
		if ( !$user ) {
			if ( !session_id() ) @session_start();
			if ( empty( $_SESSION['learn_press_temp_user_id'] ) ) {
				$_SESSION['learn_press_temp_user_id']    = time();
				$_SESSION['learn_press_temp_session_id'] = session_id();
			}
			$user = new self( $_SESSION['learn_press_temp_user_id'] );
		}
		return $user;
	}
}