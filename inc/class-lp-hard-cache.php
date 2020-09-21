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
	 * Init
	 */
	public static function init() {
		$upload_dir       = wp_upload_dir();
		self::$_root_path = trailingslashit( $upload_dir['basedir'] ) . 'lp-cache';

		@wp_mkdir_p( self::$_root_path );

		self::$_lock = self::get_option_enable();
	}

	/**
	 * Set cache content, replace if existing.
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @param string $group
	 *
	 * @return bool
	 */
	public static function set( $key, $data, $group = '' ) {
		return self::write( $key, $data, $group, true );
	}

	/**
	 * Get cache from file.
	 *
	 * @param string $key
	 * @param string $group
	 * @param bool   $force | set true if want always cache
	 *
	 * @return bool
	 */
	public static function get( $key, $group = '', $force = false ) {

		if ( self::is_locked() && ! $force ) {
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
	protected static function read( $key, $group = '' ) {
		$file = self::get_file( $key, $group );

		if ( file_exists( $file ) ) {
			$f       = @fopen( $file, 'r' );
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
	 * @param mixed  $data
	 * @param string $group
	 * @param bool   $overwrite
	 *
	 * @return bool
	 */
	protected static function write( $key, $data, $group = '', $overwrite = false ) {
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
		$path = array( self::$_root_path, $group );
		$path = array_filter( $path );
		$path = join( '/', $path );
		@wp_mkdir_p( $path );
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
	 * Check if cache is locked
	 *
	 * @return bool
	 */
	public static function is_locked() {
		return self::$_lock;
	}

	public static function get_option_enable() {
		return 'yes' === get_option( 'learn_press_enable_hard_cache' );
	}

	/**
	 * Clear cache
	 *
	 * @param false $group
	 *
	 * @return mixed
	 */
	public static function flush( $group = false ) {
		$wp_filesystem = LP_Helper::get_wp_filesystem();

		if ( $group ) {
			$return = $wp_filesystem->rmdir( self::get_path( $group ), true );
		} else {
			$return = $wp_filesystem->rmdir( self::$_root_path, true );
		}

		return $return;
	}
}

add_action( 'init', array( 'LP_Hard_Cache', 'init' ) );