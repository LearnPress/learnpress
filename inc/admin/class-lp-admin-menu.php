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
		add_action( 'admin_menu', array( $this, 'lp_group_menus' ), 9999 );

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

		$url_pages = array(
			'lp-courses'     => array(
				'title'  => esc_html__( 'View Page Courses', 'learnpress' ),
				'href'   => learn_press_get_page_link( 'courses' ),
				'parent' => 'site-name',
			),
			'lp-profile'     => array(
				'title'  => esc_html__( 'View Page Profile', 'learnpress' ),
				'href'   => learn_press_get_page_link( 'profile' ),
				'parent' => 'site-name',
			),
			'lp-instructors' => array(
				'title'  => esc_html__( 'View Page Instructors', 'learnpress' ),
				'href'   => learn_press_get_page_link( 'instructors' ),
				'parent' => 'site-name',
			),
		);

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
			LP_PLUGIN_URL . '/assets/images/logo-menu.png',
			'3.14'
		);

		// Default submenu items
		$menu_items                     = array();
		$menu_items['statistic']        = include_once 'sub-menus/class-lp-submenu-statistics.php';
		$menu_items['student-enrolled'] = include_once 'sub-menus/class-lp-submenu-students-enrolled.php';
		$menu_items['addons']           = include_once 'sub-menus/class-lp-submenu-addons.php';
		$menu_items['themes']           = include_once 'sub-menus/class-lp-submenu-themes.php';
		$menu_items['settings']         = include_once 'sub-menus/class-lp-submenu-settings.php';
		$menu_items['help-center']      = include_once 'sub-menus/class-lp-submenu-help-center.php';
		$menu_items['tools']            = include_once 'sub-menus/class-lp-submenu-tools.php';
		$menu_items['categories']       = include_once 'sub-menus/class-lp-submenu-categories.php';
		$menu_items['tags']             = include_once 'sub-menus/class-lp-submenu-tags.php';

		$menu_items = apply_filters( 'learn-press/admin/menu-items', $menu_items );

		// Sort menu items by its priority
		//uasort( $menu_items, 'learn_press_sort_list_by_priority_callback' );

		add_action(
			'parent_file',
			function ( $parent_file ) {
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

				if ( in_array( $k, array( 'tags', 'categories' ) ) ) {
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
					$callback,
					//$item->get_priority()
				);
			}
			$this->menu_items = $menu_items;
		}

		// $addons = LP_Admin::instance()->get_addons();
	}

	/**
	 * Group and reorder the LearnPress admin submenu items.
	 *
	 * Items are grouped by the 'learn-press/wp-admin/menu-group' filter
	 * and ordered by the default group and slug order. Unmatched items
	 * are added to the 'operations' group. A divider is inserted between
	 * each group.
	 *
	 * @return void
	 */
	public function lp_group_menus() {
		global $submenu;

		$lp_menus = $submenu['learn_press'] ?? [];
		if ( empty( $lp_menus ) ) {
			return;
		}

		$group_menus_default = apply_filters(
			'learn-press/wp-admin/menu-group',
			[
				'content' => [
					'courses' => 'edit.php?post_type=' . LP_COURSE_CPT,
					'lessons' => 'edit.php?post_type=' . LP_LESSON_CPT,
					'quizzes' => 'edit.php?post_type=' . LP_QUIZ_CPT,
					'questions' => 'edit.php?post_type=' . LP_QUESTION_CPT,
					'course_category' => 'edit-tags.php?taxonomy=course_category',
					'course_tag' => 'edit-tags.php?taxonomy=course_tag',
				],
				'operations' => [
					'orders' => 'edit.php?post_type=' . LP_ORDER_CPT,
					'students' => 'learn-press-students-enrolled',
					'statistics' => 'learn-press-statistics',
				],
				'system' => [
					'addons' => 'learn-press-addons',
					'themes' => 'learn-press-themes',
					'settings' => 'learn-press-settings',
					'tools' => 'learn-press-tools',
					'help_center' => 'learn-press-help-center',
				],
			]
		);

		$group_menus = [];

		// Group menus by default
		foreach ( $lp_menus as $item ) {
			$slug     = $item[2] ?? '';
			$in_group = false;

			foreach ( $group_menus_default as $group => $menus ) {
				if ( in_array( $slug, $menus ) ) {
					$group_menus[ $group ][] = $item;
					$in_group                = true;
				}
			}

			// If not in any group, add to operations
			if ( ! $in_group ) {
				$group_menus['operations'][] = $item;
			}
		}

		// Reorder menus
		$menus_new = [];
		$i         = 0;
		$count     = count( $group_menus_default );
		foreach ( $group_menus_default as $group => $menus ) {
			if ( ! isset( $group_menus[ $group ] ) ) {
				continue;
			}

			// Order items by the default slug order, then append leftovers.
			$items_by_slug = [];
			foreach ( $group_menus[ $group ] as $item ) {
				$items_by_slug[ $item[2] ?? '' ] = $item;
			}

			foreach ( $menus as $menu_slug ) {
				if ( ! is_string( $menu_slug ) ) {
					continue;
				}

				if ( isset( $items_by_slug[ $menu_slug ] ) ) {
					$menus_new[] = $items_by_slug[ $menu_slug ];
					unset( $items_by_slug[ $menu_slug ] );
				}
			}

			foreach ( $items_by_slug as $item ) {
				$menus_new[] = $item;
			}

			++$i;
			if ( $i < $count ) {
				// Add divider between groups
				$menus_new[] = array(
					'',
					$this->get_capability(),
					'lp-menu-divider-' . $group,
					'',
					'lp-menu-divider',
				);
			}
		}

		$submenu['learn_press'] = $menus_new;
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
