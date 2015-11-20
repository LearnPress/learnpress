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
		self::add_style( 'learn-press-admin', learn_press_plugin_url( 'assets/css/learnpress-admin.css' ), array( 'learn-press-global' ) );
		self::add_style( 'learn-press-icon', learn_press_plugin_url( 'assets/css/style.css' ) );
		self::add_script( 'learn-press-admin', learn_press_plugin_url( 'assets/js/admin/learnpress.js' ) );
	}

	static function _print_assets(){
		self::enqueue_style( 'learn-press-icon' );
		self::enqueue_style( 'learn-press-admin' );
		self::enqueue_script( 'learn-press-admin' );
	}
}
LP_Admin_Assets::init();