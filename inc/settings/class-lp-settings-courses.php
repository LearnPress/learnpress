<?php

use LearnPress\Helpers\Config;

/**
 * Class LP_Settings_Courses
 *
 * @author ThimPress <email@email.com>
 */
class LP_Settings_Courses extends LP_Abstract_Settings_Page {
	/**
	 * LP_Settings_Courses constructor.
	 */
	public function __construct() {
		$this->id   = 'courses';
		$this->text = esc_html__( 'Courses', 'learnpress' );

		parent::__construct();
	}

	public function save() {
		$course_permalink = LP_Helper::sanitize_params_submitted( $_POST['learn_press_course_base'] ?? '' );

		if ( ! $course_permalink ) {
			return;
		}

		if ( $course_permalink == 'custom' ) {
			$course_permalink = trim( $_POST['course_permalink_structure'], '/' );

			if ( '%course_category%' == $course_permalink ) {
				$course_permalink = _x( 'courses', 'slug', 'learnpress' ) . '/' . $course_permalink;
			}

			$course_permalink = '/' . $course_permalink;
			update_option( 'learn_press_course_base_type', 'custom' );

		} else {
			delete_option( 'learn_press_course_base_type' );
		}

		$course_base = untrailingslashit( $course_permalink );

		update_option( 'learn_press_course_base', $course_base );
		$courses_page_id   = learn_press_get_page_id( 'courses' );
		$courses_permalink = ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'learnpress' );

		if ( $courses_page_id && trim( $course_base, '/' ) === $courses_permalink ) {
			update_option( 'learn_press_use_verbose_page_rules', 'yes' );
		} else {
			delete_option( 'learn_press_use_verbose_page_rules' );
		}
	}

	/**
	 * Get config settings courses.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return array
	 */
	public function get_settings( $section = null, $tab = null ): array {
		return Config::instance()->get( 'course', 'settings' );
	}

	/**
	 * Get option enable load courses with ajax.
	 *
	 * @return bool
	 */
	public static function is_ajax_load_courses(): bool {
		return LP_Settings::get_option( 'courses_load_ajax', 'yes' ) === 'yes';
	}

	/**
	 * Get option enable courses archive ajax.
	 *
	 * @return bool
	 */
	public static function is_no_load_ajax_first_courses(): bool {
		return LP_Settings::get_option( 'courses_first_no_ajax', 'no' ) === 'yes';
	}

	/**
	 * Get option type Pagination.
	 *
	 * @return string
	 */
	public static function get_type_pagination(): string {
		return LP_Settings::get_option( 'course_pagination_type', 'number' );
	}
}
