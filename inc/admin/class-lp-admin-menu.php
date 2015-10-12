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
	}

	/**
	 * Register for menu for admin
	 */
	public function admin_menu() {
		add_menu_page(
			__( 'Learning Management System', 'learn_press' ),
			__( 'LearnPress', 'learn_press' ),
			'edit_lpr_courses',
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
				'edit_lpr_courses',
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
}

return new LP_Admin_Menu();