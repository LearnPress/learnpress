<?php

/**
 * Class LP_Helper
 */
defined( 'ABSPATH' ) || exit;
class LP_Helper {
	/**
	 * Shuffle array and keep the keys
	 *
	 * @param $array
	 *
	 * @return bool
	 */
	public static function shuffle_assoc( &$array ) {
		$keys = array_keys( $array );
		shuffle( $keys );
		foreach ( $keys as $key ) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		return true;
	}
}