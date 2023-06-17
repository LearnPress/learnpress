<?php
/**
 * Setup menus in WP admin.
 *
 * @author      ThimPress
 * @package     LearnPress
 * @version     1.0
 */

defined( 'ABSPATH' ) || exit;

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
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		if ( apply_filters( 'learn_press_show_admin_bar_courses_page', true ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menus' ), 50 );
		}

		/**
		 * @since 3.0.0
		 */
		$this->capability = 'edit_' . LP_COURSE_CPT . 's';
		include_once 'sub-menus/abstract-submenu.php';
	}

	/**
	 * Added url Pages of LP.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 *
	 * @return void
	 */
	public function admin_bar_menus( $wp_admin_bar ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		$url_pages = [
			'lp-courses'     => [
				'title'  => esc_html__( 'View Page Courses', 'learnpress' ),
				'href'   => learn_press_get_page_link( 'courses' ),
				'parent' => 'site-name',
			],
			'lp-profile'     => [
				'title'  => esc_html__( 'View Page Profile', 'learnpress' ),
				'href'   => learn_press_get_page_link( 'profile' ),
				'parent' => 'site-name',
			],
			'lp-instructors' => [
				'title'  => esc_html__( 'View Page Instructors', 'learnpress' ),
				'href'   => learn_press_get_page_link( 'instructors' ),
				'parent' => 'site-name',
			],
		];

		foreach ( $url_pages as $id => $url_page ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => $id,
					'parent' => $url_page['parent'] ?? 'appearance',
					'title'  => sprintf( '<span class="ab-label">%s</span>', $url_page['title'] ),
					'href'   => $url_page['href'],
				)
			);
		}
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
		// Not for translate menu_title, because it make compare $current_screen with "learnpress_page_.*" wrong
		add_menu_page(
			__( 'Learning Management System', 'learnpress' ),
			'LearnPress',
			$this->get_capability(),
			'learn_press',
			'',
			'dashicons-welcome-learn-more',
			'3.14'
		);

		// Default submenu items
		$menu_items               = array();
		$menu_items['statistic']  = include_once 'sub-menus/class-lp-submenu-statistics.php';
		$menu_items['addons']     = include_once 'sub-menus/class-lp-submenu-addons.php';
		$menu_items['themes']     = include_once 'sub-menus/class-lp-submenu-themes.php';
		$menu_items['settings']   = include_once 'sub-menus/class-lp-submenu-settings.php';
		$menu_items['tools']      = include_once 'sub-menus/class-lp-submenu-tools.php';
		$menu_items['categories'] = include_once 'sub-menus/class-lp-submenu-categories.php';
		$menu_items['tags']       = include_once 'sub-menus/class-lp-submenu-tags.php';

		$menu_items = apply_filters( 'learn-press/admin/menu-items', $menu_items );

		// Sort menu items by its priority
		uasort( $menu_items, 'learn_press_sort_list_by_priority_callback' );

		add_action(
			'parent_file',
			function( $parent_file ) {
				global $current_screen;

				$taxonomy = $current_screen->taxonomy;
				if ( $taxonomy == 'course_tag' ) {
					$parent_file = 'learn_press';
				} elseif ( $taxonomy == 'course_category' ) {
					$parent_file = 'learn_press';
				}

				return $parent_file;
			}
		);

		if ( $menu_items ) {
			foreach ( $menu_items as $k => $item ) {
				if ( is_string( $item ) && class_exists( $item ) ) {
					$item = new $item();
				}

				if ( ! $item instanceof LP_Abstract_Submenu ) {
					continue;
				}

				if ( in_array( $k, [ 'tags', 'categories' ] ) ) {
					$callback = false;
				} else {
					$callback = $item->get_callback();
				}

				add_submenu_page(
					'learn_press',
					$item->get_page_title(),
					$item->get_menu_title(),
					$item->get_capability(),
					$item->get_id(),
					$callback
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

	public static function instance() {
		static $instance;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}

return LP_Admin_Menu::instance();
