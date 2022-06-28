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
	 * @depecated 4.1.6.4
	 */
	public static function update_meta_cache( $ids, $type = 'post', $limit = 500 ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.1.6.4' );
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
	 * @depecated 4.1.6.4
	 */
	public static function cache_posts( $post_ids ) {
		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.1.6.4' );
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

		// self::update_meta_cache( $post_ids );
		learn_press_cache_add_post_type( $post_types );

		return $posts;
	}
}
