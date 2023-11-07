<?php

/**
 * Class LP_Settings_Cache
 *
 * @author tungnx
 * @since 4.2.2
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Settings_Cache extends LP_Cache {
	/**
	 * @var string key group
	 */
	protected $key_group_child = 'settings';
	/**
	 * @var string key cache
	 */
	public $key_settings = 'lp_settings';

	public function __construct( $has_thim_cache = false ) {
		parent::__construct( $has_thim_cache );
	}

	/**
	 * Set cache lp settings.
	 *
	 * @param string $value
	 *
	 * @return void
	 */
	public function set_lp_settings( string $value = '' ) {
		$key = "$this->key_settings";
		$this->set_cache( $key, $value );
	}

	/**
	 * Get cache lp settings.
	 *
	 * @return array|false|mixed|string
	 */
	public function get_lp_settings() {
		$key = $this->key_settings;
		return $this->get_cache( $key );
	}

	/**
	 * Clear cache lp settings.
	 *
	 * @return void
	 */
	public function clean_lp_settings() {
		$key = $this->key_settings;
		$this->clear( $key );
	}
}
