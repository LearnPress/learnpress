<?php

/**
 * Class LP_Cache
 *
 * @author tungnx
 * @since 4.0.8
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Cache {
	protected static $instance;
	protected $key_group_parent = 'learn_press/';
	protected $key_group_child  = '';
	protected $key_group        = '';

	/**
	 * Get instance
	 *
	 * @return LP_Cache
	 */
	public static function instance(): LP_Cache {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {
		$this->key_group = $this->key_group_parent . $this->key_group_child;
	}

	/**
	 * Set cache
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param int $expire
	 */
	public function set_cache( string $key, $data, int $expire ) {
		wp_cache_set( $key, $data, $this->key_group, $expire );
	}

	/**
	 * Get cache
	 *
	 * @param string $key
	 * @return false|mixed
	 */
	public function get_cache( string $key ) {
		return wp_cache_get( $key, $this->key_group );
	}

	/**
	 * Clear cache by key
	 *
	 * @param $key
	 */
	public function clear( $key ) {
		wp_cache_delete( $key, $this->key_group );
	}

	public function clear_all() {
		wp_cache_flush();
	}
}
