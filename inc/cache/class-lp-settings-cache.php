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
	/**
	 * @var string key cache
	 */
	public $key_rewrite_rules = 'lp_rewrite_rules';

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
		LP_Cache::cache_load_first( 'set', $key, $value );
	}

	/**
	 * Get cache lp settings.
	 *
	 * @return array|false|mixed|string
	 */
	public function get_lp_settings() {
		$key   = "{$this->key_settings}";
		$total = LP_Cache::cache_load_first( 'get', $key );
		if ( false !== $total ) {
			return $total;
		}

		$total = $this->get_cache( $key );
		LP_Cache::cache_load_first( 'set', $key, $total );

		return $total;
	}

	/**
	 * Clear cache lp settings.
	 *
	 * @return void
	 */
	public function clean_lp_settings() {
		$key = $this->key_settings;
		LP_Cache::cache_load_first( 'clear', $key );
		$this->clear( $key );
	}

	/**
	 * Set cache rewrite rules.
	 *
	 * @param string $value
	 *
	 * @return void
	 */
	public function set_rewrite_rules( string $value = '' ) {
		$key = "$this->key_rewrite_rules";
		$this->set_cache( $key, $value );
		LP_Cache::cache_load_first( 'set', $key, $value );
	}

	/**
	 * Get cache rewrite rules.
	 *
	 * @return array|false|mixed|string
	 */
	public function get_rewrite_rules() {
		$key   = "{$this->key_rewrite_rules}";
		$total = LP_Cache::cache_load_first( 'get', $key );
		if ( false !== $total ) {
			return $total;
		}

		$total = $this->get_cache( $key );
		LP_Cache::cache_load_first( 'set', $key, $total );

		return $total;
	}

	/**
	 * Clear cache rewrite rules.
	 *
	 * @return void
	 */
	public function clean_lp_rewrite_rules() {
		$key = $this->key_rewrite_rules;
		$this->clear( $key );
	}
}
