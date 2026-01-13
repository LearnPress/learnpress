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
		// Check role publish course of user teacher
		if ( ! empty( $_POST ) && isset( $_GET['tab'] ) && $_GET['tab'] === 'courses' ) {
			$teacher                  = get_role( LP_TEACHER_ROLE );
			$course_cap               = LP_COURSE_CPT . 's';
			$review_course_instructor = $_POST['learn_press_required_review'] ?? false;
			if ( $review_course_instructor ) {
				$teacher->remove_cap( 'publish_' . $course_cap );
			} else {
				$teacher->add_cap( 'publish_' . $course_cap );
			}
		}
		// End check role publish course of user teacher
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
