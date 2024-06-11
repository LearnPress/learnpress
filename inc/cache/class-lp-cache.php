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
	public function __construct( $has_thim_cache = false ) {
		$this->key_group      = $this->key_group_parent . '/' . $this->key_group_child;
		$this->has_thim_cache = $has_thim_cache;
	}

	/**
	 * Set cache
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @param int    $expire
	 *
	 * @since 4.0.8
	 * @version 1.0.2
	 */
	public function set_cache( string $key, $data, int $expire = 0 ) {
		try {
			// Cache WP
			wp_cache_set( $key, $data, $this->key_group, $expire );
			// Cache thim_cache
			if ( $this->can_handle_with_thim_cache() ) {
				$key = "{$this->key_group}/{$key}";
				Thim_Cache_DB::instance()->set_value( $key, $data, $expire );
				/*$lp_bg_thim_cache = new LP_Background_Thim_Cache();
				$lp_bg_thim_cache->data( compact( 'key', 'data' ) )->dispatch();*/
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
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
		if ( false === $cache && $this->can_handle_with_thim_cache() ) {
			$key   = "{$this->key_group}/{$key}";
			$cache = Thim_Cache_DB::instance()->get_value( $key );
			/*if ( is_string( $cache ) ) {
				$cache = wp_unslash( $cache );
			}*/
		}

		return $cache;
	}

	/**
	 * Set value for first load page on one process
	 * Apply for query call same many times.
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
		} elseif ( 'clear' === $type ) {
			unset( $first_set_value[ $key ] );
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
			if ( empty( $key ) ) {
				return;
			}

			wp_cache_delete( $key, $this->key_group );
			if ( $this->can_handle_with_thim_cache() ) {
				$key = "{$this->key_group}/{$key}";
				Thim_Cache_DB::instance()->remove_cache( $key );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Store list keys cache to a group.
	 *
	 * @param string $key_group
	 * @param string $key_cache_new
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function save_cache_keys( string $key_group, string $key_cache_new ) {
		try {
			$keys = $this->get_cache( $key_group );
			if ( false === $keys ) {
				$keys_cache = array();
			} else {
				$keys_cache = LP_Helper::json_decode( $keys, true );
			}

			$keys_cache[] = $key_cache_new;
			$this->set_cache( $key_group, json_encode( $keys_cache ) );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Clear cache on group store list keys.
	 *
	 * @param string $key_group
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function clear_cache_on_group( string $key_group ) {
		try {
			$keys_cache_of_group_str = $this->get_cache( $key_group );
			if ( false === $keys_cache_of_group_str ) {
				return;
			}
			$keys_cache_of_group = LP_Helper::json_decode( $keys_cache_of_group_str, true );
			if ( ! empty( $keys_cache_of_group ) ) {
				foreach ( $keys_cache_of_group as $key ) {
					// Clear cache by key
					$this->clear( $key );
				}
				// Clear cache store list keys
				$this->clear( $key_group );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Check can handle with thim cache
	 *
	 * @since 4.2.5.4
	 * @version 1.0.0
	 * @return bool
	 */
	public function can_handle_with_thim_cache(): bool {
		return $this->has_thim_cache && LP_Settings::is_created_tb_thim_cache();
	}

	/**
	 * Clear all cache
	 *
	 * @since 4.0.8
	 * @version 1.0.1
	 * @return void
	 */
	public function clear_all() {
		try {
			wp_cache_flush();
			Thim_Cache_DB::instance()->remove_all_cache();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}
}
