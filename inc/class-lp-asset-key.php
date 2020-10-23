<?php

/**
 * Class LP_Asset_Key
 *
 * @author  tungnx
 * @package LearnPress/Classes
 * @version 1.0
 * @since 3.2.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Asset_Key {
	public $_url = ''; // url of file css/js
	public $_deps = array(); // attach js/css need load
	public $_in_footer = 0; // load in footer
	public $_only_register = 1; // value 1 for run wp_register_script(), 0 for run wp_enqueue_script()
	public $_screens = array(); // default value empty will load all page

	/**
	 * LP_ASSET_KEY constructor.
	 *
	 * @param string $url
	 * @param array $deps
	 * @param int $in_footer
	 * @param int $only_register
	 * @param string[] $screens
	 */
	public function __construct( $url = '', $deps = array(), $screens = array(), $only_register = 1, $in_footer = 0 ) {
		$this->_url           = $url;
		$this->_deps          = $deps;
		$this->_in_footer     = $in_footer;
		$this->_only_register = $only_register;
		$this->_screens       = $screens;
	}
}