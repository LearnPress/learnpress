<?php
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'Thim_Cache_DB' ) ) {
	return;
}

/**
 * Class Thim_Cache_DB
 *
 * @author tungnx
 * @version 1.0.2
 * @since 4.2.2
 */
class Thim_Cache_DB {
	private static $_instance = null;
	private $action           = null; // one of insert/update
	const ACTION_INSERT       = 'insert';
	const ACTION_UPDATE       = 'update';

	/**
	 * Set action for thim cache (one of insert/update)
	 * Default is null
	 *
	 * @param string|null $action
	 * @description Null for not set manual action insert/update
	 * @description Else it will be auto check exist key_cache to insert/update
	 * @return Thim_Cache_DB
	 */
	public function set_action( $action ): Thim_Cache_DB {
		$this->action = $action;
		return $this;
	}

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
			"SELECT `value`, `expiration` FROM {$this->table_name} WHERE `key_cache` = %s",
			$key_cache
		);

		$result = $this->wpdb->get_row( $sql );
		if ( is_null( $result ) ) {
			return false;
		}

		$expiration = (int) ( $result->expiration ?? 0 );
		if ( $expiration > 0 && $expiration < time() ) {
			try {
				$this->remove_cache( $key_cache );
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}
			return false;
		}

		return $result->value;
	}

	/**
	 * Get value by key_cache
	 *
	 * @param string $key_cache
	 * @param string $value
	 * @param int $expire timestamp
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function set_value( string $key_cache, string $value, int $expire = 0 ) {
		// Use ON DUPLICATE KEY UPDATE to avoid duplicate key error
		// when two processes insert the same key concurrently.
		$sql = $this->wpdb->prepare(
			"INSERT INTO {$this->table_name} (key_cache, value, expiration)
			VALUES (%s, %s, %d)
			ON DUPLICATE KEY UPDATE value = VALUES(value), expiration = VALUES(expiration)",
			$key_cache,
			$value,
			$expire
		);

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

		$result = $this->wpdb->query( $sql );

		if ( $this->wpdb->last_error ) {
			throw new Exception( $this->wpdb->last_error );
		}

		return $result;
	}

	/**
	 * Delete all cache.
	 *
	 * @return bool|int|mysqli_result|resource|null
	 * @throws Exception
	 */
	public function remove_all_cache() {
		$sql    = "TRUNCATE TABLE {$this->table_name}";
		$result = $this->wpdb->query( $sql );

		if ( $this->wpdb->last_error ) {
			throw new Exception( $this->wpdb->last_error );
		}

		return $result;
	}
}
