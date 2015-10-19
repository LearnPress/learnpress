<?php
/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 * @extends LP_Abstract_Assets
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

LP()->_include( 'abstracts/abstract-lp-assets.php' );

class LP_Admin_Assets extends LP_Abstract_Assets {
	/**
	 * Load assets for admin
	 */
	static function load_scripts(){
		self::enqueue_script( 'learnpress', LP()->plugin_url( 'assets/js/admin/learnpress.js' ) );
	}
}
LP_Admin_Assets::init();