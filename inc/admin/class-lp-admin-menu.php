<?php
/**
 * Setup menus in WP admin.
 *
 * @author      ThimPress
 * @package     LearnPress
 * @version     1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Admin_Menu
 */
class LP_Admin_Menu {

	/**
	 * LP_Admin_Menu Construct
	 */
	function __construct() {
		// admin menu
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'notify_new_course' ) );
		add_action( 'init', array( $this, 'menu_content' ) );
	}

	/**
	 * Register for menu for admin
	 */
	public function admin_menu() {
		$capacity = 'edit_' . LP()->course_post_type . 's';
		add_menu_page(
			__( 'Learning Management System', 'learn_press' ),
			__( 'LearnPress', 'learn_press' ),
			$capacity,
			'learn_press',
			'',
			'dashicons-welcome-learn-more',
			'3.14'
		);

		$menu_items = array(
			'statistics' => array(
				'learn_press',
				__( 'Statistics', 'learn_press' ),
				__( 'Statistics', 'learn_press' ),
				$capacity,
				'learn_press_statistics',
				'learn_press_statistic_page'
			),
			'settings'   => array(
				'options-general.php',
				__( 'LearnPress Settings', 'learn_press' ),
				__( 'LearnPress', 'learn_press' ),
				'manage_options',
				'learn_press_settings',
				'learn_press_settings_page'
			),
			'addons'     => array(
				'learn_press',
				__( 'Add-ons', 'learn_press' ),
				__( 'Add-ons', 'learn_press' ),
				'manage_options',
				'learn_press_add_ons',
				'learn_press_add_ons_page'
			)
		);

		// Third-party can be add more items
		$menu_items = apply_filters( 'learn_press_menu_items', $menu_items );

		if ( $menu_items ) foreach ( $menu_items as $item ) {
			call_user_func_array( 'add_submenu_page', $item );
		}
	}

	/*
	 * Notify an administrator with pending courses
	 */
	function notify_new_course() {
		global $menu;
		$current_user = wp_get_current_user();
		if ( !in_array( 'administrator', $current_user->roles ) ) {
			return;
		}
		$count_courses = wp_count_posts( LP()->course_post_type );
		$awaiting_mod  = $count_courses->pending;
		$menu['3.14'][0] .= " <span class='awaiting-mod count-$awaiting_mod'><span class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span></span>";
	}

	function menu_content(){
		// auto include file for admin page
		// example: slug = learn_press_settings -> file = inc/admin/sub-menus/settings.php
		$page = !empty ( $_REQUEST['page'] ) ? $_REQUEST['page'] : null;
		if ( !$page ) return;

		if ( strpos( $page, 'learn_press_' ) === false ) return;
		$file = preg_replace( '!^learn_press_!', '', $page );
		$file = str_replace( '_', '-', $file );
		if ( file_exists( $file = LP_PLUGIN_PATH . "/inc/admin/sub-menus/{$file}.php" ) ) {
			require_once $file;
		}
	}
}

return new LP_Admin_Menu();