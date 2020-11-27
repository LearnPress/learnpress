<?php

/**
 * Class LP_Helper_CURD
 *
 * @since 3.0.0
 */
class LP_Helper_CURD {
	/**
	 * @var int
	 */
	protected static $_time = 0;

	/**
	 * Update meta into cache by ids.
	 *
	 * @since 3.0.0
	 *
	 * @param string    $type - E.g: post, user, ...
	 * @param array|int $ids
	 * @param int       $limit
	 */
	public static function update_meta_cache( $ids, $type = 'post', $limit = 500 ) {

		if ( ! $ids ) {
			return;
		}

		//@since 3.3.0
		settype( $ids, 'array' );

		sort( $ids );
		$cache_key = md5( serialize( $ids ) );

		if ( false === ( $meta_data = LP_Object_Cache::get( $cache_key, "{$type}-meta" ) ) ) {
			$meta_data = array();

			if ( $limit > 0 ) {
				$chunks = array_chunk( $ids, $limit );
				foreach ( $chunks as $chunk ) {
					$cache     = update_meta_cache( $type, $chunk );
					$meta_data = $meta_data + $cache;// array_merge( $meta_data, $cache );
				}
			} else {
				$meta_data = update_meta_cache( $type, $ids );
			}

			LP_Object_Cache::set( $cache_key, $meta_data, "{$type}-meta" );
		}

		foreach ( $ids as $id ) {
			if ( ! isset( $meta_data[ $id ] ) ) {
				$meta_data[ $id ] = array();
			}

			wp_cache_set( $id, $meta_data[ $id ], "{$type}_meta" );
		}

	}

	/**
	 * Load posts from database into cache.
	 *
	 * @param int|array $post_ids
	 *
	 * @return mixed
	 */
	public static function cache_posts( $post_ids ) {
		global $wpdb;
		settype( $post_ids, 'array' );

		// Remove the posts has already cached
		for ( $n = sizeof( $post_ids ), $i = $n - 1; $i >= 0; $i -- ) {
			if ( false !== wp_cache_get( $post_ids[ $i ], 'posts' ) ) {
				unset( $post_ids[ $i ] );
			}
		}

		if ( ! sizeof( $post_ids ) ) {
			return false;
		}

		$format = array_fill( 0, sizeof( $post_ids ), '%d' );
		$query  = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->posts}
			WHERE ID IN(" . join( ',', $format ) . ")
		", $post_ids );

		if ( false === ( $post_types = LP_Object_Cache::get( 'post-types', 'learn-press' ) ) ) {
			$post_types = array();
		}

		if ( $posts = $wpdb->get_results( $query ) ) {
			foreach ( $posts as $post ) {
				$post                    = sanitize_post( $post, 'raw' );
				$post_types[ $post->ID ] = $post->post_type;

				wp_cache_set( $post->ID, $post, 'posts' );
			}
		}

		self::update_meta_cache( $post_ids );
		learn_press_cache_add_post_type( $post_types );

		return $posts;
	}

	public static function init() {
		add_action( 'shutdown', array( __CLASS__, 'log' ) );
	}

	public static function log() {

		return;
		//echo "Time = " . self::$_time;
		ini_set( 'memory_limit', '2G' );
		ob_start();
		print_r( debug_backtrace() );
		$content = ob_get_clean();

		$lines = preg_split( '!\n!', $content );

		$calls = array( array(), array() );
		for ( $i = 0, $n = sizeof( $lines ); $i < $n; $i ++ ) {
			if ( ! preg_match( '!\[function\] => (.*)!', $lines[ $i ], $m ) ) {
				continue;
			}

			$class = '';
			$func  = $m[1];
			$i ++;

			if ( preg_match( '!\[class\] => (.*)!', $lines[ $i ], $m ) ) {
				$class = $m[1];
			}

			$call = $class ? "{$class}::$func" : $func;
			$k    = 1;
			if ( $class ) {
				$k = 0;
			}
			if ( empty( $calls[ $k ][ $call ] ) ) {
				$calls[ $k ][ $call ] = 1;
			} else {
				$calls[ $k ][ $call ] ++;
			}
		}
		arsort( $calls[0] );
		arsort( $calls[1] );
		print_r( array_merge( $calls[0], $calls[1] ) );
	}
}

LP_Helper_CURD::init();