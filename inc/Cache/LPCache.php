<?php

namespace LearnPress\Cache;

use LP_Debug;
use LP_Helper;
use LP_Settings;
use Thim_Cache_DB;
use Throwable;

/**
 * Manage LearnPress cache data in the WordPress object cache and optional persistent cache.
 *
 * @since 4.4.11
 * @version 1.0.0
 */
class LPCache {
	/**
	 * Parent cache group.
	 *
	 * @var string
	 */
	protected $key_group_parent = 'learn_press';

	/**
	 * Child cache group appended to the parent group.
	 *
	 * @var string
	 */
	protected $key_group_child = '';

	/**
	 * Resolved cache group used by cache operations.
	 *
	 * @var string
	 */
	protected $key_group = '';

	/**
	 * Request-local cache group that object-cache drop-ins must not persist.
	 *
	 * @var string
	 */
	protected $key_group_not_persistent = 'lp_cache_not_persistent';

	/**
	 * Whether the persistent Thim cache is enabled for this instance.
	 *
	 * @var bool
	 */
	protected $has_thim_cache = false;

	/**
	 * Initialize the cache group and persistent-cache setting.
	 *
	 * @since 4.4.11
	 *
	 * @param bool $has_thim_cache Whether to use the persistent Thim cache.
	 */
	public function __construct( $has_thim_cache = false ) {
		$this->key_group = $this->key_group_parent;

		if ( ! empty( $this->key_group_child ) ) {
			$this->key_group .= '/' . $this->key_group_child;
		}

		$this->has_thim_cache = $has_thim_cache;
	}

	/**
	 * Store a value in the WordPress object cache and optional persistent cache.
	 *
	 * @since 4.4.11
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $data   Value to cache.
	 * @param int    $expire Expiration in seconds. Default 0.
	 * @return bool Whether the value was stored in the WordPress object cache.
	 */
	public function set_cache( string $key, $data, int $expire = 0 ): bool {
		try {
			$result = wp_cache_set( $key, $data, $this->key_group, $expire );

			if ( $this->can_handle_with_thim_cache() ) {
				$key_cache  = "{$this->key_group}/{$key}";
				$value      = is_string( $data )
					? $data
					: wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				$expiration = $expire > 0 ? time() + $expire : 0;

				Thim_Cache_DB::instance()->set_value( $key_cache, $value, $expiration );
			}

			return $result;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
			return false;
		}
	}

	/**
	 * Retrieve a cached value.
	 *
	 * The WordPress object cache is checked first. A persistent-cache hit is
	 * restored to the WordPress object cache before being returned.
	 *
	 * @since 4.4.11
	 *
	 * @param string $key Cache key.
	 * @return mixed|false Cached value on success, false on a cache miss.
	 */
	public function get_cache( string $key, $type_data_get = '' ) {
		$cache = wp_cache_get( $key, $this->key_group );
		if ( false !== $cache || ! $this->can_handle_with_thim_cache() ) {
			return $cache;
		}

		$key_cache = "{$this->key_group}/{$key}";
		$cache     = Thim_Cache_DB::instance()->get_value( $key_cache );
		if ( false === $cache ) {
			return false;
		}

		if ( ! empty( $type_data_get ) ) {
			try {
				switch ( $type_data_get ) {
					case 'object':
						$cache = LP_Helper::json_decode( $cache );
						break;
					case 'array':
						$cache = LP_Helper::json_decode( $cache, true );
						break;
				}
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}
		}

		wp_cache_set( $key, $cache, $this->key_group );

		return $cache;
	}

	/**
	 * Register the request-local cache group as non-persistent.
	 *
	 * Object-cache drop-ins (Redis/Memcached) will keep values in this group
	 * only for the current request, matching the default WordPress behavior.
	 *
	 * @since 4.4.11
	 *
	 * @return void
	 */
	protected function register_not_persistent_group() {
		static $registered = false;

		if ( $registered ) {
			return;
		}

		if ( function_exists( 'wp_cache_add_non_persistent_groups' ) ) {
			wp_cache_add_non_persistent_groups( array( $this->key_group_not_persistent ) );
		}

		$registered = true;
	}

	/**
	 * Retrieve a value from the request-local, non-persistent cache.
	 *
	 * @since 4.4.11
	 *
	 * @param string $key Cache key.
	 * @return mixed|false Cached value on success, false on a cache miss.
	 */
	public function get_not_persistent_cache( string $key ) {
		$this->register_not_persistent_group();

		return wp_cache_get( $key, $this->key_group_not_persistent );
	}

	/**
	 * Store a value in the request-local, non-persistent cache.
	 *
	 * Values in this group only live for the current request, so expiration
	 * is not supported.
	 *
	 * @since 4.4.11
	 *
	 * @param string $key  Cache key.
	 * @param mixed  $data Value to cache.
	 * @return bool Whether the value was stored.
	 */
	public function set_not_persistent_cache( string $key, $data ) {
		$this->register_not_persistent_group();

		return wp_cache_set( $key, $data, $this->key_group_not_persistent );
	}

	/**
	 * Get a request-local value or compute and cache it if missing.
	 *
	 * @since 4.4.11
	 *
	 * @param string   $key      Cache key.
	 * @param callable $callback Callback to compute the value on a cache miss.
	 * @return mixed The cached or computed value.
	 */
	public function remember_not_persistent( string $key, callable $callback ) {
		$value = $this->get_not_persistent_cache( $key );
		if ( false !== $value ) {
			return $value;
		}

		$value = $callback();
		$this->set_not_persistent_cache( $key, $value );

		return $value;
	}

	/**
	 * Delete a value from the WordPress object cache and optional persistent cache.
	 *
	 * @since 4.4.11
	 *
	 * @param string $key Cache key.
	 * @return bool Whether the value was deleted from the WordPress object cache.
	 */
	public function clear( string $key ): bool {
		try {
			if ( empty( $key ) ) {
				return false;
			}

			$result = wp_cache_delete( $key, $this->key_group );
			if ( $this->can_handle_with_thim_cache() ) {
				Thim_Cache_DB::instance()->remove_cache( "{$this->key_group}/{$key}" );
			}

			return $result;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
			return false;
		}
	}

	/**
	 * Add a cache key to a stored key registry.
	 *
	 * @since 4.4.11
	 *
	 * @param string $key_group     Registry cache key.
	 * @param string $key_cache_new Cache key to add to the registry.
	 * @return void
	 */
	public function save_cache_keys( string $key_group, string $key_cache_new ) {
		try {
			$keys       = $this->get_cache( $key_group );
			$keys_cache = false === $keys ? array() : $keys;

			if ( in_array( $key_cache_new, $keys_cache ) ) {
				return;
			}

			$keys_cache[] = $key_cache_new;
			$this->set_cache( $key_group, $keys_cache );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * Delete every cache entry registered under a key registry.
	 *
	 * @since 4.4.11
	 *
	 * @param string $key_group Registry cache key.
	 * @return void
	 */
	public function clear_cache_on_group( string $key_group ) {
		try {
			$keys = $this->get_cache( $key_group, 'array' );
			if ( false === $keys ) {
				return;
			}

			foreach ( $keys as $key ) {
				$this->clear( $key );
			}

			$this->clear( $key_group );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}
	}

	/**
	 * Determine whether the persistent Thim cache can be used.
	 *
	 * @since 4.4.11
	 *
	 * @return bool True when enabled and the persistent cache table exists.
	 */
	public function can_handle_with_thim_cache(): bool {
		return $this->has_thim_cache && LP_Settings::is_created_tb_thim_cache();
	}

	/**
	 * Clear the WordPress object cache and all persistent LearnPress cache data.
	 *
	 * @since 4.4.11
	 *
	 * @return bool Whether the WordPress object cache was flushed successfully.
	 */
	public function clear_all(): bool {
		try {
			$result = wp_cache_flush();
			Thim_Cache_DB::instance()->remove_all_cache();

			return $result;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
			return false;
		}
	}
}
