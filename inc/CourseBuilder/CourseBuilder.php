<?php

namespace LearnPress\CourseBuilder;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LP_Course_Post_Type;
use LP_Forms_Handler;
use LP_REST_Profile_Controller;
use LP_Settings;
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
	 *  Constructor
	 *
	 */
	protected function __construct() {
	}

	/**
	 * Get tabs default in course builder.
	 *
	 * @return array
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_tabs_arr(): array {
		$tab_arr = [
			'courses'   => array(
				'title'    => esc_html__( 'Courses', 'learnpress' ),
				'slug'     => 'courses',
				'sections' => array(
					'overview'   => array(
						'title' => esc_html__( 'Course Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'curriculum' => array(
						'title' => esc_html__( 'Curriculum', 'learnpress' ),
						'slug'  => 'curriculum',
					),
					'settings'   => array(
						'title' => esc_html__( 'Course Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'lessons'   => array(
				'title'    => esc_html__( 'Lessons', 'learnpress' ),
				'slug'     => 'lessons',
				'sections' => array(
					'overview' => array(
						'title' => esc_html__( 'Lesson Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'settings' => array(
						'title' => esc_html__( 'Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'quizzes'   => array(
				'title'    => esc_html__( 'Quizzes', 'learnpress' ),
				'slug'     => 'quizzes',
				'sections' => array(
					'overview' => array(
						'title' => esc_html__( 'Quizz Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'question' => array(
						'title' => esc_html__( 'Question', 'learnpress' ),
						'slug'  => 'question',
					),
					'settings' => array(
						'title' => esc_html__( 'Quizz Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'questions' => array(
				'title'    => esc_html__( 'Questions', 'learnpress' ),
				'slug'     => 'questions',
				'sections' => array(
					'overview' => array(
						'title' => esc_html__( 'Question Overview', 'learnpress' ),
						'slug'  => 'overview',
					),
					'settings' => array(
						'title' => esc_html__( 'Question Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
		];

		return apply_filters( 'learn-press/course-builder/tabs', $tab_arr );
	}

	/**
	 * Get the current course builder tab.
	 * @param string $current
	 * @return string
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function get_current_tab() {
		global $wp;
		$current = '';
		if ( ! empty( $_REQUEST['tab'] ) ) {
			$current = sanitize_text_field( $_REQUEST['tab'] );
		} elseif ( ! empty( $wp->query_vars['tab'] ) ) {
			$current = $wp->query_vars['tab'];
		} else {
			$tab_data    = self::get_tabs_arr();
			$current_tab = reset( $tab_data );
			if ( ! empty( $current_tab['slug'] ) ) {
				$current = $current_tab['slug'];
			} else {
				$current = array_keys( $tab_data );
			}
		}

		return $current;
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
				$current_tab = self::get_current_tab();
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
		$tabs = self::get_tabs_arr();
		return false !== $key ? ( array_key_exists( $key, $tabs ) ? $tabs[ $key ] : [] ) : $tabs;
	}

	public static function get_link_course_builder( $sub = '' ) {
		$page = LP_Settings::get_option( 'course_builder', 'course-builder' );
		$link = sprintf( '%s/%s/', home_url(), $page );
		$tab  = self::get_current_tab();

		if ( $sub ) {
			$link .= $tab . '/' . $sub;
		}
		return $link;
	}

	public static function get_tab_link( $tab = false, $post_id = null, $section = false ) {
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
	 * @return int| post-new
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
}
