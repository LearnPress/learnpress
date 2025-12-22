<?php

use LearnPress\Helpers\Config;

/**
 * Class LP_Settings_Permalink
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings
 * @since 4.1.7.3.2
 * @version 1.0.0
 */
class LP_Settings_Permalink extends LP_Abstract_Settings_Page {
	/**
	 * Construct
	 */
	public function __construct() {
		$this->id   = 'permalink';
		$this->text = esc_html__( 'Permalinks', 'learnpress' );

		parent::__construct();
	}

	/**
	 * Return fields for settings page.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		return Config::instance()->get( 'permalink', 'settings' );
	}

	/**
	 * @since 4.3.2.3
	 *
	 * move from LP_Settings_Courses class to here
	 */
	public function save() {
		$course_permalink = trim( LP_Request::get_param( 'learn_press_course_base' ) );
		if ( ! $course_permalink ) {
			return;
		}

		if ( $course_permalink == 'custom' ) {
			$course_permalink = trim( LP_Request::get_param( 'course_permalink_structure' ), '/' );

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
		//$courses_page_id   = learn_press_get_page_id( 'courses' );
		//$courses_permalink = ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'learnpress' );

		/*if ( $courses_page_id && trim( $course_base, '/' ) === $courses_permalink ) {
			update_option( 'learn_press_use_verbose_page_rules', 'yes' );
		} else {
			delete_option( 'learn_press_use_verbose_page_rules' );
		}*/
	}
}

return new LP_Settings_Permalink();
