<?php

/**
 * Class LP_Hard_Cache
 *
 * Cache content in files
 *
 * @since 3.0.0
 */
class LP_Hard_Cache {

	/**
	 * @var string
	 */
	protected static $_root_path = '';

	/**
	 * @var string
	 */
	protected static $_default_group = '';

	/**
	 * @var bool
	 */
	protected static $_lock = false;

	/**
	 * @var bool
	 */
	protected static $_hard_lock = false;

	/**
	 * Init
	 */
	/*public static function init() {
		$upload_dir       = wp_upload_dir();
		self::$_root_path = trailingslashit( $upload_dir['basedir'] ) . 'lp-cache';

		if ( defined( 'LP_HARD_CACHE' ) ) {
			self::$_lock = self::$_hard_lock = ! LP_HARD_CACHE;
		} else {
			self::$_lock = self::$_hard_lock = ! ( 'yes' === LP()->settings()->get( 'enable_hard_cache' ) );
		}

		if ( self::is_locked() ) {
			return;
		}

		@wp_mkdir_p( self::$_root_path ); // phpcs:ignore
	}*/

	/**
	 * Replace existing cache with new data.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function replace( $key, $data, $group = '' ) {
		if ( self::is_locked() ) {
			return false;
		}

		$file = self::get_file( $key, $group );

		if ( ! file_exists( $file ) ) {
			return false;
		}

		return self::write( $key, $data, $group );
	}

	/**
	 * Set cache content, replace if existing.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function set( $key, $data, $group = '' ) {
		if ( self::is_locked() ) {
			return false;
		}

		return self::write( $key, $data, $group, true );
	}

	/**
	 * Get cache from file.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function get( $key, $group = '' ) {
		if ( self::is_locked() ) {
			return false;
		}

		return self::read( $key, $group );
	}

	/**
	 * Read content file
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return bool|mixed
	 */
	public static function read( $key, $group = '' ) {
		if ( self::is_locked() ) {
			return false;
		}

		$file = self::get_file( $key, $group );
		if ( file_exists( $file ) ) {
			$f       = @fopen( $file, 'r' ); // phpcs:ignore
			$content = fread( $f, filesize( $file ) );
			fclose( $f );

			if ( ! $content ) {
				return false;
			}

			return LP_Helper::maybe_unserialize( $content );
		}

		return false;
	}

	/**
	 * Write data into file.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param string $group
	 * @param bool $overwrite
	 *
	 * @return bool
	 */
	protected static function write( $key, $data, $group = '', $overwrite = false ) {

		if ( self::is_locked() ) {
			return false;
		}

		$file = self::get_file( $key, $group );

		/**
		 * If file exists and overwrite is false
		 */
		if ( $overwrite && file_exists( $file ) ) {
			return false;
		}

		$f = @fopen( $file, 'w' );

		if ( ! $f ) {
			return false;
		}

		fwrite( $f, maybe_serialize( $data ) );
		fclose( $f );

		return true;
	}

	/**
	 * Get file of cache by the key and group.
	 *
	 * @param string $key
	 * @param string $group
	 *
	 * @return string
	 */
	protected static function get_file( $key, $group = '' ) {
		if ( self::is_locked() ) {
			return false;
		}

		$path = array( self::$_root_path, $group );
		$path = array_filter( $path );
		$path = join( '/', $path );
		@wp_mkdir_p( $path ); // phpcs:ignore
		$file = "{$path}/{$key}.cache";

		return $file;
	}

	protected static function get_path( $group = '' ) {
		$path = array( self::$_root_path, $group );
		$path = array_filter( $path );
		$path = join( '/', $path );

		return $path;
	}

	/**
	 * Enable for updating cache temporary even it is locked.
	 */
	public static function unlock() {
		self::$_lock = false;
	}

	/**
	 * Disable for updating cache temporary event it is not locked.
	 */
	public static function lock() {
		self::$_lock = true;
	}

	/**
	 * Reset lock to default.
	 */
	public static function reset_lock() {
		self::$_lock = self::$_hard_lock;
	}

	/**
	 * Check if cache is locked
	 *
	 * @return bool
	 */
	public static function is_locked() {
		return self::$_lock;
	}

	public static function flush( $group = false ) {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		WP_Filesystem();
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return false;
		}

		if ( $group ) {
			$return = $wp_filesystem->rmdir( self::get_path( $group ), true );
		} else {
			$return = $wp_filesystem->rmdir( self::$_root_path, true );
		}

		return $return;
	}
}

//add_action( 'init', array( 'LP_Hard_Cache', 'init' ) );
