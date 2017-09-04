<?php

/**
 * Class LP_Helper
 */
defined( 'ABSPATH' ) || exit;

class LP_Helper {
	/**
	 * Shuffle array and keep the keys
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	public static function shuffle_assoc( &$array ) {
		$keys = array_keys( $array );
		shuffle( $keys );
		$new = array();
		foreach ( $keys as $key ) {
			$new[ $key ] = $array[ $key ];
		}
		$array = $new;

		return true;
	}

	/**
	 * Sanitize array by removing empty and/or duplicating values.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	public static function sanitize_array( $array ) {
		$array = array_filter( $array );
		$array = array_unique( $array );

		return $array;
	}
}