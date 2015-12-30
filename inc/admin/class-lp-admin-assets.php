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

learn_press_include( 'abstracts/abstract-lp-assets.php' );
class LP_Admin_Assets extends LP_Abstract_Assets {

	static function init(){
		add_action( 'learn_press_print_assets', array( __CLASS__, '_print_assets' ) );
		parent::init();
	}
	/**
	 * Load assets for admin
	 */
	static function load_scripts(){
		self::add_style( 'learn-press-global', learn_press_plugin_url( 'assets/css/global-style.css' ) );
		self::add_style( 'learn-press-admin', learn_press_plugin_url( 'assets/css/admin/admin.css' ), array( 'learn-press-global', 'learn-press-ui' ) );
		self::add_style( 'learn-press-icons', learn_press_plugin_url( 'assets/css/icons.css' ) );

		self::add_script( 'learn-press-ui', learn_press_plugin_url( 'assets/js/ui.js' ) );
		self::add_script( 'learn-press-admin', learn_press_plugin_url( 'assets/js/admin/learnpress.js' ), array( 'learn-press-ui' ) );
		self::add_script( 'modal-search-items',    LearnPress()->plugin_url( 'assets/js/admin/modal-search-items.js' ), array( 'jquery' ) );
	}

	static function _print_assets(){
		self::enqueue_style( 'learn-press-icons' );
		self::enqueue_style( 'learn-press-admin' );
		self::enqueue_script( 'learn-press-admin' );
	}
}
LP_Admin_Assets::init();