<?php

/**
 * Class LP_User
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_User extends LP_Abstract_User {

	/**
	 * @param      $the_user
	 *
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public static function get_user( $the_user, $force = false ) {
		if ( is_numeric( $the_user ) ) {

		} elseif ( $the_user instanceof LP_Abstract_User ) {
			$the_user = $the_user->id;
		} elseif ( isset( $the_user->ID ) ) {
			$the_user = $the_user->ID;
		}

		if ( $the_user ) {
			if ( empty( self::$_users[$the_user] ) || $force ) {
				self::$_users[$the_user] = new self( $the_user );
			}
		} else {
			self::$_users[$the_user] = LP_User_Guest::instance();
		}
		return self::$_users[$the_user];
	}

	/**
	 * @return mixed
	 */
	public static function get_current_user() {
		return self::get_user( get_current_user_id() );
	}
}