<?php

namespace LearnPress\CourseBuilder;

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
					'edit'       => array(
						'title' => esc_html__( 'Edit', 'learnpress' ),
						'slug'  => 'edit',
					),
					'curriculum' => array(
						'title' => esc_html__( 'Curriculum', 'learnpress' ),
						'slug'  => 'curriculum',
					),
					'settings'   => array(
						'title' => esc_html__( 'Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'lessons'   => array(
				'title'    => esc_html__( 'Lessons', 'learnpress' ),
				'slug'     => 'lessons',
				'sections' => array(
					'edit'     => array(
						'title' => esc_html__( 'Edit', 'learnpress' ),
						'slug'  => 'edit',
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
					'edit'     => array(
						'title' => esc_html__( 'Edit', 'learnpress' ),
						'slug'  => 'edit',
					),
					'question' => array(
						'title' => esc_html__( 'Question', 'learnpress' ),
						'slug'  => 'question',
					),
					'settings' => array(
						'title' => esc_html__( 'Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
			'questions' => array(
				'title'    => esc_html__( 'Questions', 'learnpress' ),
				'slug'     => 'questions',
				'sections' => array(
					'edit'     => array(
						'title' => esc_html__( 'Edit', 'learnpress' ),
						'slug'  => 'edit',
					),
					'settings' => array(
						'title' => esc_html__( 'Settings', 'learnpress' ),
						'slug'  => 'settings',
					),
				),
			),
		];

		return $tab_arr;
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

	public static function get_tab_link( $tab = false, $post_id = null, $section = false ) {
		$link = '';
		if ( ! $tab ) {
			return $link;
		}

		$link = trailingslashit( learn_press_get_page_link( 'course_builder' ) );

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
}
