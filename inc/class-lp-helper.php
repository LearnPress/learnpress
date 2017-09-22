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

	/**
	 * MD5 an array.
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	public static function array_to_md5( $array ) {
		settype( $array, 'array' );
		ksort( $array );

		return md5( serialize( $array ) );
	}

	public static function cache_posts( $ids ) {
		settype( $ids, 'array' );
		global $wpdb;
		$format = array_fill( 0, sizeof( $ids ), '%d' );
		$query  = $wpdb->prepare( "
			SELECT * FROM {$wpdb->posts} WHERE ID IN(" . join( ',', $format ) . ")
		", $ids );
		if ( $posts = $wpdb->get_results( $query ) ) {
			foreach ( $posts as $post ) {
				wp_cache_set( $post->ID, $post, 'posts' );
			}
		}
	}
}