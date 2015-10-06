<?php

/**
 * Class LP_User_Guest
 */
class LP_User_Guest extends LP_Abstract_User{
	/**
	 * @param $the_user
	 */
	function __construct( $the_user ){
		$this->id = $the_user;
	}

	/**
	 * @static
	 *
	 * @return LP_User_Guest
	 */
	static function instance(){
		static $user;
		if( ! $user ) {
			if ( !session_id() ) session_start();
			if ( empty( $_SESSION['learn_press_temp_user_id'] ) ) {
				$_SESSION['learn_press_temp_user_id'] = time();
			}
			$user = new self($_SESSION['learn_press_temp_user_id']);
		}
		return $user;
	}
}