<?php

namespace LearnPress\CourseBuilder;

use Exception;
use LearnPress\Helpers\Config;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LP_Course_Post_Type;
use LP_Forms_Handler;
use LP_REST_Profile_Controller;
use LP_Settings;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * Course Builder class.
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class CourseBuilder {
	/**
	 * Constant for new post identifier
	 */
	const POST_NEW = 'post-new';

	/**
	 *  Constructor
	 *
	 */
	protected function __construct() {
	}

	/**
	 * Get menus structure default in course builder.
	 *
	 * @return array
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public static function get_menus_arr(): array {
		return Config::instance()->get( 'menus', 'course-builder' );
	}

	/**
	 * Get the current menu slug for the Course Builder page.
	 *
	 * Retrieves the active menu slug from the WP_Query object based on the 'lp_cb_menu_slug' query variable.
	 *
	 * @return string The current menu slug.
	 *
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public static function get_menu_current(): string {
		/** @var WP_Query $wp_query */
		global $wp_query;
		$menu_current = (string) $wp_query->get( 'lp_cb_menu_slug' );
		if ( '' !== $menu_current ) {
			return $menu_current;
		}

		$menus = self::get_menus_arr();
		if ( isset( $menus['dashboard']['slug'] ) ) {
			return (string) $menus['dashboard']['slug'];
		}

		if ( ! empty( $menus ) ) {
			reset( $menus );
			$first_menu = current( $menus );
			if ( is_array( $first_menu ) && ! empty( $first_menu['slug'] ) ) {
				return (string) $first_menu['slug'];
			}

			$first_key = key( $menus );
			if ( is_string( $first_key ) ) {
				return $first_key;
			}
		}

		return '';
	}

	/**
	 * Get the current section being viewed in a course builder tab.
	 * @param string $current
	 * @param string $tab
	 * @return string
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_current_section( $current = '', $tab = '' ) {
		global $wp;

		if ( empty( $_REQUEST['post_id'] ) && empty( $wp->query_vars['post_id'] ) ) {
			return $current;
		}

		if ( ! empty( $_REQUEST['section'] ) ) {
			$current = sanitize_text_field( $_REQUEST['section'] );
		} elseif ( ! empty( $wp->query_vars['section'] ) ) {
			$current = $wp->query_vars['section'];
		} else {
			if ( ! $tab ) {
				$current_tab = self::get_menu_current();
			} else {
				$current_tab = $tab;
			}
			$tab_data = self::get_data( $current_tab );

			if ( ! empty( $tab_data['sections'] ) ) {
				$sections = $tab_data['sections'];
				$section  = reset( $sections );
				if ( ! empty( $section['slug'] ) ) {
					$current = $section['slug'];
				} else {
					$current = array_keys( $tab_data['sections'] );
				}
			}
		}

		return $current;
	}

	/**
	 * Retrieves tabs data or a specific tab by key.
	 *
	 * @param string|bool
	 * @return array
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_data( $key = false ) {
		$tabs = self::get_menus_arr();
		return false !== $key ? ( array_key_exists( $key, $tabs ) ? $tabs[ $key ] : [] ) : $tabs;
	}

	/**
	 * Get link for course builder
	 *
	 * @param string $sub
	 *
	 * @return string
	 */
	public static function get_link_course_builder( string $sub = '' ): string {
		$page = LP_Settings::get_option( 'course_builder', 'course-builder' );
		return home_url( "{$page}/{$sub}" );
	}

	/**
	 * Get link for add new an item.
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public static function get_link_add_new( $type ): string {
		return self::get_link_course_builder( "{$type}/create" );
	}

	/**
	 * Get tab link
	 *
	 * @param string|false $tab
	 * @param int|string|null $post_id
	 * @param string|false $section
	 *
	 * @return string
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_tab_link( $tab = false, $post_id = null, $section = false ): string {
		$link = '';
		if ( ! $tab ) {
			return $link;
		}

		$link = self::get_link_course_builder();

		if ( ! empty( $tab ) ) {
			$link .= $tab . '/';
		}

		if ( ! empty( $post_id ) ) {
			$link .= $post_id . '/';
		}

		if ( ! empty( $section ) ) {
			$link .= $section . '/';
		}

		return $link;
	}

	/**
	 * Get post id
	 *
	 * @return int
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_post_id() {
		global $wp;
		$post_id = 0;
		if ( ! empty( $_REQUEST['post_id'] ) ) {
			$post_id = $_REQUEST['post_id'];
		}

		if ( ! empty( $wp->query_vars['post_id'] ) ) {
			$post_id = $wp->query_vars['post_id'];
		}

		return $post_id;
	}

	public static function can_view_course_builder() {
		return is_user_logged_in() && current_user_can( 'edit_lp_courses' );
	}

	/**
	 * Get title page
	 *
	 * @return string
	 */
	public static function get_title_page(): string {
		$title = '';

		$menu_current = self::get_menu_current();
		$menus        = self::get_menus_arr();
		if ( isset( $menus[ $menu_current ] ) ) {
			$title = $menus[ $menu_current ]['title'];
		}

		return $title;
	}
}
