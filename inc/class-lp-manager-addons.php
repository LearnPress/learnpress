<?php
/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

class LP_Manager_Addons {
	protected static $_instance;
	private $url_list_addons = 'https://learnpress.github.io/learnpress/version-addons.json';

	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->url_list_addons = LP_PLUGIN_URL . '/version-addons.json';
	}

	protected function download() {

	}

	public static function instance(): LP_Manager_Addons {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

