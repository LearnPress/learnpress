<?php
/**
 * Setup menus in WP admin.
 *
 * @author      ThimPress
 * @package     LearnPress
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Admin_Menu
 */
class LP_Admin_Menu {

	/**
	 * Array of submenu items.
	 *
	 * @var array
	 */
	protected $menu_items = array();

	/**
	 * Main menu capability.
	 *
	 * @var string
	 */
	protected $capability = '';

	/**
	 * LP_Admin_Menu Construct
	 */
	public function __construct() {
		// admin menu
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'notify_new_course' ) );
		//add_action( 'init', 'learn_press_admin_update_settings', 1000 );
		if ( apply_filters( 'learn_press_show_admin_bar_courses_page', true ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 50 );
		}

		/**
		 * @since 3.0.0
		 */
		$this->capability = 'edit_' . LP_COURSE_CPT . 's';
		include_once 'sub-menus/abstract-submenu.php';
	}

	public function admin_bar_menus( $wp_admin_bar ) {

		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			return;
		}

		if ( get_option( 'page_on_front' ) == learn_press_get_page_id( 'courses' ) ) {
			return;
		}

		$wp_admin_bar->add_node( array(
			'parent' => 'site-name',
			'id'     => 'courses-page',
			'title'  => __( 'View Courses', 'learnpress' ),
			'href'   => learn_press_get_page_link( 'courses' )
		) );
	}

	/**
	 * Get main menu capability.
	 *
	 * @return string
	 */
	public function get_capability() {
		return $this->capability;
	}

	/**
	 * Register for menu for admin
	 */
	public function admin_menu() {

		add_menu_page(
			__( 'Learning Management System', 'learnpress' ),
			__( 'LearnPress', 'learnpress' ),
			$this->get_capability(),
			'learn_press',
			'',
			'dashicons-welcome-learn-more',
			'3.14'
		);

		// Default submenu items
		$menu_items              = array();
		$menu_items['statistic'] = include_once "sub-menus/class-lp-submenu-statistics.php";
		$menu_items['addons']    = include_once "sub-menus/class-lp-submenu-addons.php";
		$menu_items['settings']  = include_once "sub-menus/class-lp-submenu-settings.php";
		$menu_items['tools']     = include_once "sub-menus/class-lp-submenu-tools.php";

		// Deprecated hooks
		$menu_items = apply_filters( 'learn_press_menu_items', $menu_items );

		$menu_items = apply_filters( 'learn-press/admin/menu-items', $menu_items );

		// Sort menu items by it's priority
		//uasort( $menu_items, array( $this, 'sort_menu_items' ) );
		uasort( $menu_items, 'learn_press_sort_list_by_priority_callback' );

		if ( $menu_items ) {
			foreach ( $menu_items as $item ) {

				// Construct submenu if it is a name of a class
				if ( is_string( $item ) && class_exists( $item ) ) {
					$item = new $item();
				}

				if ( ! $item instanceof LP_Abstract_Submenu ) {
					continue;
				}

				add_submenu_page(
					'learn_press',
					$item->get_page_title(),
					$item->get_menu_title(),
					$item->get_capability(),
					$item->get_id(),
					array( $item, 'display' )
				);

			}
			$this->menu_items = $menu_items;
		}

		$addons = LP_Admin::instance()->get_addons();
	}

	/**
	 * Callback function using for "usort".
	 *
	 * @param LP_Abstract_Submenu $a
	 * @param LP_Abstract_Submenu $b
	 *
	 * @return mixed
	 */
	public function sort_menu_items( $a, $b ) {
		return $a->get_priority() > $b->get_priority();
	}

	public function get_menu_items() {
		return $this->menu_items;
	}

	/*
	 * Notify an administrator with pending courses
	 */
	public function notify_new_course() {
		global $menu;
		$current_user = wp_get_current_user();
		if ( ! in_array( 'administrator', $current_user->roles ) ) {
			return;
		}
		$count_courses   = wp_count_posts( LP_COURSE_CPT );
		$awaiting_mod    = $count_courses->pending;
		$menu['3.14'][0] .= " <span class='awaiting-mod count-$awaiting_mod'><span class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span></span>";
	}

	public static function instance() {
		static $instance = false;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}

return LP_Admin_Menu::instance();