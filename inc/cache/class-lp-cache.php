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
	protected $key_group_parent = 'learn_press/';
	/**
	 * @var string Key group child(external)
	 */
	protected $key_group_child = '';
	/**
	 * @var string Add key group parent with key group child
	 */
	protected $key_group = '';
	/**
	 * @var float|int default expire
	 */
	protected $expire = DAY_IN_SECONDS;

	protected function __construct() {
		$this->key_group = $this->key_group_parent . $this->key_group_child;
	}

	/**
	 * Set cache
	 * $expire = -1 is  get default expire time on one day(DAY_IN_SECONDS)
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @param int    $expire
	 */
	public function set_cache( string $key, $data, int $expire = -1 ) {
		if ( -1 === $expire ) {
			$expire = $this->expire;
		}
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
