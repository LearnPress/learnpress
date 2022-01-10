<?php
/**
 * Class LP_Object_Cache
 */

class LP_Object_Cache {

	/**
	 * Holds the cached objects.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private $cache = array();

	/**
	 * The amount of times the cache data was already stored in the cache.
	 *
	 * @since 2.5.0
	 * @var int
	 */
	public $cache_hits = 0;

	/**
	 * Amount of times the cache did not have the request in cache.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	public $cache_misses = 0;

	/**
	 * List of global cache groups.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $global_groups = array();

	/**
	 * The blog prefix to prepend to keys in non-global groups.
	 *
	 * @since 3.5.0
	 * @var int
	 */
	private $blog_prefix;

	/**
	 * Holds the value of is_multisite().
	 *
	 * @since 3.5.0
	 * @var bool
	 */
	private $multisite;

	/**
	 * @var bool
	 */
	protected static $_use_core = false;

	/**
	 * @var LP_Object_Cache
	 */
	protected static $instance = null;

	/**
	 * @return LP_Object_Cache
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function init() {
		// add_action( 'shutdown', array( __CLASS__, 'wp_cache_flush' ), 9999 );
	}

	public static function wp_cache_flush() {

	}

	public static function get_group( $group ) {
		if ( self::$_use_core ) {
			if ( false === strpos( $group, 'learn-press/' ) ) {
				return "learn-press/{$group}";
			}
		}

		return $group;
	}

	/**
	 * Makes private properties readable for backward compatibility.
	 *
	 * @param string $name  Property to get.
	 *
	 * @return mixed Property.
	 * @since 4.0.0
	 *
	 */
	public function __get( $name ) {
		return $this->$name;
	}

	/**
	 * Makes private properties settable for backward compatibility.
	 *
	 * @param string $name  Property to set.
	 * @param mixed $value  Property value.
	 *
	 * @return mixed Newly-set property.
	 * @since 4.0.0
	 *
	 */
	public function __set( $name, $value ) {
		return $this->$name = $value;
	}

	/**
	 * Makes private properties checkable for backward compatibility.
	 *
	 * @param string $name  Property to check if set.
	 *
	 * @return bool Whether the property is set.
	 * @since 4.0.0
	 *
	 */
	public function __isset( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Makes private properties un-settable for backward compatibility.
	 *
	 * @param string $name  Property to unset.
	 *
	 * @since 4.0.0
	 *
	 */
	public function __unset( $name ) {
		unset( $this->$name );
	}

	/**
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @param int|string $key  What to call the contents in the cache.
	 * @param mixed $data  The contents to store in the cache.
	 * @param string $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int $expire  Optional. When to expire the cache contents. Default 0 (no expiration).
	 *
	 * @return bool False if cache key and group already exist, true on success
	 * @uses  WP_Object_Cache::set()     Sets the data after the checking the cache
	 *                                    contents existence.
	 *
	 * @since 2.0.0
	 *
	 * @uses  WP_Object_Cache::_exists() Checks to see if the cache already has data.
	 */
	public static function add( $key, $data, $group = 'default', $expire = 0 ) {

		$group = self::get_group( $group );

		if ( self::$_use_core ) {
			return wp_cache_add( $key, $data, $group, $expire );
		}

		if ( wp_suspend_cache_addition() ) {
			return false;
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$self = self::instance();

		$id = $key;
		if ( $self->multisite && ! isset( $self->global_groups[ $group ] ) ) {
			$id = $self->blog_prefix . $key;
		}

		if ( self::_exists( $id, $group ) ) {
			return false;
		}

		return self::set( $key, $data, $group, (int) $expire );
	}

	/**
	 * Sets the list of global cache groups.
	 *
	 * @param array $groups  List of groups that are global.
	 *
	 * @since 3.0.0
	 *
	 */
	public static function add_global_groups( $groups ) {
		$groups = (array) $groups;
		$self   = self::instance();

		$groups              = array_fill_keys( $groups, true );
		$self->global_groups = array_merge( $self->global_groups, $groups );
	}

	/**
	 * Decrements numeric cache item's value.
	 *
	 * @param int|string $key  The cache key to decrement.
	 * @param int $offset  Optional. The amount by which to decrement the item's value. Default 1.
	 * @param string $group  Optional. The group the key is in. Default 'default'.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 * @since 3.3.0
	 *
	 */
	public static function decr( $key, $offset = 1, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$self = self::instance();

		if ( $self->multisite && ! isset( $self->global_groups[ $group ] ) ) {
			$key = $self->blog_prefix . $key;
		}

		if ( ! self::_exists( $key, $group ) ) {
			return false;
		}

		if ( ! is_numeric( $self->cache[ $group ][ $key ] ) ) {
			$self->cache[ $group ][ $key ] = 0;
		}

		$offset = (int) $offset;

		$self->cache[ $group ][ $key ] -= $offset;

		if ( $self->cache[ $group ][ $key ] < 0 ) {
			$self->cache[ $group ][ $key ] = 0;
		}

		return $self->cache[ $group ][ $key ];
	}

	/**
	 * Removes the contents of the cache key in the group.
	 *
	 * If the cache key does not exist in the group, then nothing will happen.
	 *
	 * @param int|string $key  What the contents in the cache are called.
	 * @param string $group  Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool $deprecated  Optional. Unused. Default false.
	 *
	 * @return bool False if the contents weren't deleted and true on success.
	 * @since 2.0.0
	 *
	 */
	public static function delete( $key, $group = 'default', $deprecated = false ) {

		$group = self::get_group( $group );

		if ( self::$_use_core ) {
			return wp_cache_delete( $key, $group );
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$self = self::instance();

		if ( $self->multisite && ! isset( $self->global_groups[ $group ] ) ) {
			$key = $self->blog_prefix . $key;
		}

		if ( ! self::_exists( $key, $group ) ) {
			return false;
		}

		unset( $self->cache[ $group ][ $key ] );

		return true;
	}

	/**
	 * Clears the object cache of all data.
	 *
	 * @return true Always returns true.
	 * @since 2.0.0
	 *
	 */
	public static function flush() {

		if ( self::$_use_core ) {
			// return wp_cache_flush();
		}

		$self = self::instance();

		$self->cache = array();

		return true;
	}

	/**
	 * Retrieves the cache contents, if it exists.
	 *
	 * The contents will be first attempted to be retrieved by searching by the
	 * key in the cache group. If the cache is hit (success) then the contents
	 * are returned.
	 *
	 * On failure, the number of cache misses will be incremented.
	 *
	 * @param int|string $key  What the contents in the cache are called.
	 * @param string $group  Optional. Where the cache contents are grouped. Default 'default'.
	 * @param string $force  Optional. Unused. Whether to force a refetch rather than relying on the local
	 *                            cache. Default false.
	 * @param bool $found  Optional. Whether the key was found in the cache (passed by reference).
	 *                            Disambiguates a return of false, a storable value. Default null.
	 *
	 * @return false|mixed False on failure to retrieve contents or the cache contents on success.
	 * @since 2.0.0
	 *
	 */
	public static function get( $key, $group = 'default', $force = false, &$found = null ) {
		$group = self::get_group( $group );

		if ( self::$_use_core ) {
			return wp_cache_get( $key, $group, $force, $found );
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$self = self::instance();

		if ( $self->multisite && ! isset( $self->global_groups[ $group ] ) ) {
			$key = $self->blog_prefix . $key;
		}

		if ( self::_exists( $key, $group ) ) {
			$found            = true;
			$self->cache_hits += 1;
			if ( is_object( $self->cache[ $group ][ $key ] ) ) {
				return clone $self->cache[ $group ][ $key ];
			} else {
				return $self->cache[ $group ][ $key ];
			}
		}

		$found              = false;
		$self->cache_misses += 1;

		return false;
	}

	/**
	 * Increments numeric cache item's value.
	 *
	 * @param int|string $key  The cache key to increment
	 * @param int $offset  Optional. The amount by which to increment the item's value. Default 1.
	 * @param string $group  Optional. The group the key is in. Default 'default'.
	 *
	 * @return false|int False on failure, the item's new value on success.
	 * @since 3.3.0
	 *
	 */
	public static function incr( $key, $offset = 1, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		$self = self::instance();

		if ( $self->multisite && ! isset( $self->global_groups[ $group ] ) ) {
			$key = $self->blog_prefix . $key;
		}

		if ( ! self::_exists( $key, $group ) ) {
			return false;
		}

		if ( ! is_numeric( $self->cache[ $group ][ $key ] ) ) {
			$self->cache[ $group ][ $key ] = 0;
		}

		$offset = (int) $offset;

		$self->cache[ $group ][ $key ] += $offset;

		if ( $self->cache[ $group ][ $key ] < 0 ) {
			$self->cache[ $group ][ $key ] = 0;
		}

		return $self->cache[ $group ][ $key ];
	}

	/**
	 * Replaces the contents in the cache, if contents already exist.
	 *
	 * @param int|string $key  What to call the contents in the cache.
	 * @param mixed $data  The contents to store in the cache.
	 * @param string $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int $expire  Optional. When to expire the cache contents. Default 0 (no expiration).
	 *
	 * @return bool False if not exists, true if contents were replaced.
	 * @see   WP_Object_Cache::set()
	 *
	 * @since 2.0.0
	 *
	 */
	public static function replace( $key, $data, $group = 'default', $expire = 0 ) {

		$group = self::get_group( $group );

		if ( self::$_use_core ) {
			return wp_cache_replace( $key, $data, $group, $expire );
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$self = self::instance();

		$id = $key;
		if ( $self->multisite && ! isset( $self->global_groups[ $group ] ) ) {
			$id = $self->blog_prefix . $key;
		}

		if ( ! self::_exists( $id, $group ) ) {
			return false;
		}

		return self::set( $key, $data, $group, (int) $expire );
	}

	/**
	 * Resets cache keys.
	 *
	 * @since      3.0.0
	 *
	 * @deprecated 3.5.0 Use switch_to_blog()
	 * @see        switch_to_blog()
	 */
	public static function reset() {

		$self = self::instance();

		_deprecated_function( __FUNCTION__, '3.5.0', 'switch_to_blog()' );

		// Clear out non-global caches since the blog ID has changed.
		foreach ( array_keys( $self->cache ) as $group ) {
			if ( ! isset( $self->global_groups[ $group ] ) ) {
				unset( $self->cache[ $group ] );
			}
		}
	}

	/**
	 * Sets the data contents into the cache.
	 *
	 * The cache contents is grouped by the $group parameter followed by the
	 * $key. This allows for duplicate ids in unique groups. Therefore, naming of
	 * the group should be used with care and should follow normal function
	 * naming guidelines outside of core WordPress usage.
	 *
	 * The $expire parameter is not used, because the cache will automatically
	 * expire for each time a page is accessed and PHP finishes. The method is
	 * more for cache plugins which use files.
	 *
	 * @param int|string $key  What to call the contents in the cache.
	 * @param mixed $data  The contents to store in the cache.
	 * @param string $group  Optional. Where to group the cache contents. Default 'default'.
	 * @param int $expire  Not Used.
	 *
	 * @return true Always returns true.
	 * @since 2.0.0
	 *
	 */
	public static function set( $key, $data, $group = 'default', $expire = 0 ) {
		$group = self::get_group( $group );

		if ( self::$_use_core ) {
			return wp_cache_set( $key, $data, $group, $expire );
		}

		if ( empty( $group ) ) {
			$group = 'default';
		}

		$self = self::instance();

		if ( $self->multisite && ! isset( $self->global_groups[ $group ] ) ) {
			$key = $self->blog_prefix . $key;
		}

		if ( is_object( $data ) ) {
			$data = clone $data;
		}

		$self->cache[ $group ][ $key ] = $data;

		return true;
	}

	/**
	 * Echoes the stats of the caching.
	 *
	 * Gives the cache hits, and cache misses. Also prints every cached group,
	 * key and the data.
	 *
	 * @since 2.0.0
	 */
	public static function stats() {
		$self = self::instance();
		echo '<p>';
		echo "<strong>Cache Hits:</strong> {$self->cache_hits}<br />";
		echo "<strong>Cache Misses:</strong> {$self->cache_misses}<br />";
		echo '</p>';
		echo '<ul>';
		foreach ( $self->cache as $group => $cache ) {
			echo "<li><strong>Group:</strong> $group - ( " . number_format( strlen( serialize( $cache ) ) / KB_IN_BYTES,
					2 ) . 'k )</li>';
		}
		echo '</ul>';
	}

	/**
	 * Switches the internal blog ID.
	 *
	 * This changes the blog ID used to create keys in blog specific groups.
	 *
	 * @param int $blog_id  Blog ID.
	 *
	 * @since 3.5.0
	 *
	 */
	public static function switch_to_blog( $blog_id ) {
		$self = self::instance();

		$blog_id           = (int) $blog_id;
		$self->blog_prefix = $self->multisite ? $blog_id . ':' : '';
	}

	/**
	 * Serves as a utility function to determine whether a key exists in the cache.
	 *
	 * @param int|string $key  Cache key to check for existence.
	 * @param string $group  Cache group for the key existence check.
	 *
	 * @return bool Whether the key exists in the cache for the given group.
	 * @since 3.4.0
	 *
	 */
	protected static function _exists( $key, $group ) {
		$self = self::instance();

		return isset( $self->cache[ $group ] ) && ( isset( $self->cache[ $group ][ $key ] ) || array_key_exists( $key,
					$self->cache[ $group ] ) );
	}

	/**
	 * Sets up object properties; PHP 5 style constructor.
	 *
	 * @since 2.0.8
	 */
	public function __construct() {
		$this->multisite   = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';

		/**
		 * @todo This should be moved to the PHP4 style constructor, PHP5
		 * already calls __destruct()
		 */
		register_shutdown_function( array( $this, '__destruct' ) );
	}

	/**
	 * Saves the object cache before object is completely destroyed.
	 *
	 * Called upon object destruction, which should be when PHP ends.
	 *
	 * @return true Always returns true.
	 * @since 2.0.8
	 *
	 */
	public function __destruct() {
		return true;
	}
}

LP_Object_Cache::init();
