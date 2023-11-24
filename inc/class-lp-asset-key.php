<?php

/**
 * Class LP_Asset_Key
 *
 * @author  tungnx
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 3.2.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Asset_Key {
	/**
	 * Url of file css/js
	 *
	 * @var string
	 */
	public $_url = '';
	/**
	 * Attach js/css need load
	 *
	 * @var array
	 */
	public $_deps = array();
	/**
	 * Load on footer
	 *
	 * @var int
	 */
	public $_in_footer = 0;
	/**
	 * Strategy load defer/async support from WP 6.3
	 *
	 * @var array
	 * @since 4.2.5.5
	 */
	public $_strategy = [];
	/**
	 * Value 1 for run wp_register_script(), 0 for run wp_enqueue_script()
	 *
	 * @var int
	 */
	public $_only_register = 1;
	/**
	 * Default value empty will load all page
	 *
	 * @var array|string[]
	 */
	public $_screens = array();
	/**
	 * Set screens(pages) not load js
	 *
	 * @var array|string[]
	 */
	public $_exclude_screens = array();
	/**
	 * Version of addon
	 *
	 * @var string
	 */
	public $_version = '';

	/**
	 * LP_ASSET_KEY constructor.
	 *
	 * @param string   $url .
	 * @param array    $deps .
	 * @param string[] $screens .
	 * @param int      $only_register .
	 * @param int      $in_footer .
	 */
	public function __construct(
		string $url = '',
		array $deps = array(),
		array $screens = array(),
		int $only_register = 1,
		int $in_footer = 0,
		string $version = '',
		array $strategy = []
	) {
		$this->_url           = $url;
		$this->_deps          = $deps;
		$this->_in_footer     = $in_footer;
		$this->_only_register = $only_register;
		$this->_screens       = $screens;
		$this->_version       = $version;
		$this->_strategy      = $strategy;
	}

	/**
	 * Set pages not call js.
	 *
	 * @param string[] $screens .
	 */
	public function exclude_screen( array $screens = array() ) {
		$this->_exclude_screens = $screens;
	}

	/**
	 * Set dependency
	 *
	 * @param array $deps
	 */
	public function set_dependency_js( array $deps ) {
		$this->_deps = $deps;
	}
}
