<?php
class LP_User extends LP_Abstract_User{
	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	static function get_user( $id ){
		if( empty( self::$_users[ $id ] ) ){
			self::$_users[ $id ] = new self( $id );
		}
		return self::$_users[ $id ];
	}
}