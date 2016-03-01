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
		parent::$caller = __CLASS__;
		add_action( 'learn_press_print_assets', array( __CLASS__, '_print_assets' ) );
		parent::init();
	}
	/**
	 * Load assets for admin
	 */
	static function load_scripts(){
		$screen = get_current_screen();
		$screen_id = $screen->id;
		$page_id = !empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
		if( in_array( $screen_id, learn_press_get_screens() ) || in_array( $page_id, learn_press_get_admin_pages() ) ) {
			self::add_style( 'learn-press-global', learn_press_plugin_url( 'assets/css/global-style.css' ) );
			self::add_style( 'learn-press-admin', learn_press_plugin_url( 'assets/css/admin/admin.css' ), array( 'learn-press-global' ) );
			self::add_style( 'learn-press-icons', learn_press_plugin_url( 'assets/css/icons.css' ) );

			//self::add_script( 'learn-press-ui', learn_press_plugin_url( 'assets/js/ui.js' ) );
			self::add_script( 'learn-press-admin', learn_press_plugin_url( 'assets/js/admin/admin.js' ) );
			self::add_script( 'modal-search-items', learn_press_plugin_url( 'assets/js/admin/modal-search-items.js' ), array( 'jquery' ) );
		}
		if( in_array( $screen_id, array( 'lp_order', 'order', 'edit-lp_order' ) ) ){
			self::add_style( 'learn-press-order', learn_press_plugin_url( 'assets/css/admin/meta-box-order.css' ) );
			self::add_script( 'learn-press-order', learn_press_plugin_url( 'assets/js/admin/meta-box-order.js' ), array( 'backbone', 'wp-util' ) );
		}
	}

	static function _print_assets(){
		self::enqueue_style( 'learn-press-icons' );
		self::enqueue_style( 'learn-press-admin' );

		self::enqueue_style( 'learn-press-order' );
		self::enqueue_script( 'learn-press-order' );

		self::enqueue_script( 'learn-press-admin' );
	}
}
LP_Admin_Assets::init();