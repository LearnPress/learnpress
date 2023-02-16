<?php

/**
 * Class LP_Cache
 *
 * @author tungnx
 * @since 4.0.8
 * @version 1.0.2
 */
defined( 'ABSPATH' ) || exit();

class LP_Cache {
	/**
	 * @var string Key group parent
	 */
	protected $key_group_parent = 'learn_press';
	/**
	 * @var string Key group child(external)
	 */
	protected $key_group_child = '';
	/**
	 * @var string Add key group parent with key group child
	 */
	protected $key_group = '';
	/**
	 * @var string Add key group parent with key group child
	 */
	protected $has_thim_cache = false;

	/**
	 * If set $has_thim_cache = true, will use thim cache
	 * Set/Update will check key from table thim_cache
	 * else only WP Cache
	 */
	protected function __construct( $has_thim_cache = false ) {
		$this->key_group      = $this->key_group_parent . '/' . $this->key_group_child;
		$this->has_thim_cache = $has_thim_cache;
	}

	/**
	 * Set cache
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @param int    $expire
	 */
	public function set_cache( string $key, $data, int $expire = 0 ) {
		// Cache WP
		wp_cache_set( $key, $data, $this->key_group, $expire );
		// Cache thim_cache
		if ( $this->has_thim_cache && LP_Settings::is_created_tb_thim_cache() ) {
			Thim_Cache_DB::instance()->set_value( $key, $data );
		}
	}

	/**
	 * Get cache
	 *
	 * @param string $key
	 * @return false|mixed
	 */
	public function get_cache( string $key ) {
		// Get WP Cache
		$cache = wp_cache_get( $key, $this->key_group );
		// Get thim_cache
		if ( false === $cache && $this->has_thim_cache && LP_Settings::is_created_tb_thim_cache() ) {
			$cache = wp_unslash( Thim_Cache_DB::instance()->get_value( $key ) );
		}

		return $cache;
	}

	/**
	 * Set value for first load page on one process
	 * Apply for query call same
	 *
	 * @param string $type
	 * @param string $key
	 * @param $val mixed
	 *
	 * @author tungnx
	 * @version 1.0.0
	 * @sicne 4.1.4.1
	 * @return false|mixed|string
	 */
	public static function cache_load_first( string $type = 'get', string $key = '', $val = '' ) {
		static $first_set_value = array();

		if ( 'get' === $type ) {
			if ( ! array_key_exists( $key, $first_set_value ) ) {
				return false;
			} else {
				return $first_set_value[ $key ];
			}
		} elseif ( 'set' === $type ) {
			$first_set_value[ $key ] = $val;

			return $first_set_value[ $key ];
		}

		return $first_set_value;
	}

	/**
	 * Clear cache by key
	 *
	 * @param $key
	 */
	public function clear( $key ) {
		try {
			wp_cache_delete( $key, $this->key_group );
			if ( $this->has_thim_cache && LP_Settings::is_created_tb_thim_cache() ) {
				Thim_Cache_DB::instance()->remove_cache( $key );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	public function clear_all() {
		wp_cache_flush();
	}
}
