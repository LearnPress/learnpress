<?php
/**
 * Class Thim_Cache_DB
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.2.2
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'Thim_Cache_DB' ) ) {
	return;
}

class Thim_Cache_DB {
	private static $_instance = null;

	/**
	 * Singleton
	 */
	public static function instance(): Thim_Cache_DB {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new static();
		}

		return self::$_instance;
	}

	/**
	 * @var wpdb $wpdb
	 */
	public $wpdb;
	public $table_name;

	protected function __construct() {
		$this->wpdb       = $GLOBALS['wpdb'];
		$this->table_name = $this->wpdb->prefix . 'thim_cache';
	}

	/**
	 * Get value by key_cache
	 *
	 * @param string $key_cache
	 *
	 * @return bool|string
	 */
	public function get_value( string $key_cache ) {
		$sql = $this->wpdb->prepare(
			"SELECT `value` FROM {$this->table_name} WHERE `key_cache` = %s",
			$key_cache
		);

		$result = $this->wpdb->get_var( $sql );
		if ( is_null( $result ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Get value by key_cache
	 *
	 * @param string $key_cache
	 * @param string $value
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function set_value( string $key_cache, string $value ) {
		$value_old = $this->get_value( $key_cache );
		if ( false !== $value_old ) {
			// Update
			$sql = $this->wpdb->prepare(
				"UPDATE {$this->table_name} SET value = %s WHERE key_cache = %s",
				$value,
				$key_cache
			);
		} else {
			// Insert
			$sql = $this->wpdb->prepare(
				"INSERT INTO {$this->table_name} (key_cache, value) VALUES (%s, %s)",
				$key_cache,
				$value
			);
		}

		return $this->wpdb->query( $sql );
	}

	/**
	 * Delete value by key_cache
	 *
	 * @param string $key_cache
	 *
	 * @return bool|int|mysqli_result|resource|null
	 * @throws Exception
	 */
	public function remove_cache( string $key_cache ) {
		$sql = $this->wpdb->prepare(
			"DELETE FROM {$this->table_name} WHERE key_cache = %s",
			$key_cache
		);

		if ( $this->wpdb->last_error ) {
			throw new Exception( $this->wpdb->last_error );
		}

		return $this->wpdb->query( $sql );
	}
}
