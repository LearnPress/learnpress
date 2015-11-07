<?php

/**
 * Class LP_Settings_Courses
 */
class LP_Settings_Courses extends LP_Settings_Base {
	function __construct() {
		$this->id   = 'courses';
		$this->text = __( 'Courses', 'learn_press' );

		parent::__construct();
	}

	function _get_sections() {
		$sections = array(
			'general' => __( 'General', 'learn_press' )
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	function save(){
		parent::save();
		$course_permalink = $_POST['learn_press_course_base'];

		if ( $course_permalink == 'custom' ) {
			// Get permalink without slashes
			$course_permalink = trim( $_POST['course_permalink_structure'], '/' );

			// This is an invalid base structure and breaks pages
			if ( '%course_category%' == $course_permalink ) {
				$course_permalink = _x( 'courses', 'slug', 'learn_press' ) . '/' . $course_permalink;
			}

			// Prepending slash
			$course_permalink = '/' . $course_permalink;
		} elseif ( empty( $course_permalink ) ) {
			$course_permalink = false;
		}

		$course_base = untrailingslashit( $course_permalink );

		update_option( 'learn_press_course_base', $course_base );

		// Shop base may require verbose page rules if nesting pages
		$courses_page_id   = learn_press_get_page_id( 'courses' );
		$courses_permalink = ( $courses_page_id > 0 && get_post( $courses_page_id ) ) ? get_page_uri( $courses_page_id ) : _x( 'courses', 'default-slug', 'learn_press' );

		if ( $courses_page_id && trim( $course_base, '/' ) === $courses_permalink ) {
			update_option( 'learn_press_use_verbose_page_rules', 'yes' );
		}else{
			delete_option( 'learn_press_use_verbose_page_rules' );
		}
	}

	function output() {
		$view = learn_press_get_admin_view( 'settings/courses.php' );
		require_once $view;
	}
}

new LP_Settings_Courses();