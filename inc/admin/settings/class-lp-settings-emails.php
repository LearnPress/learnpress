<?php

/**
 * Class LP_Settings_Emails
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Emails extends LP_Settings_Base {
	/**
	 * Constructor
	 */
	function __construct() {
		$this->id   = 'emails';
		$this->text = __( 'Emails', 'learn_press' );
		parent::__construct();
	}

	/**
	 * Sections
	 *
	 * @return mixed
	 */
	function get_sections() {
		$sections = array(
			'general'          => __( 'General options', 'learn_press' ),
			'new_course'       => __( 'New course', 'learn_press' ),
			'published_course' => __( 'Published course', 'learn_press' ),
			'new_order'        => __( 'New order', 'learn_press' ),
			'enrolled_course'  => __( 'Enrolled course', 'learn_press' ),
			'passed_course'    => __( 'Passed course', 'learn_press' ),
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	/**
	 * @param $default_message
	 */
	function message_editor( $default_message ) {
		$settings  = LP()->settings;
		$content   = stripslashes( $settings->get( $this->section['id'] . '.message', $default_message ) );
		$editor_id = 'email_message';
		wp_editor(
			stripslashes( $content ),
			$editor_id,
			array(
				'textarea_rows' => 10,
				'wpautop'       => false,
				'textarea_name' => "lpr_settings[$this->id][message]",
			)
		);

	}

	/**
	 *
	 */
	function output_section_general() {
		$view = learn_press_get_admin_view( 'settings/emails/general.php' );
		include_once $view;
	}

	function output_section_new_course() {
		$view = learn_press_get_admin_view( 'settings/emails/new-course.php' );
		include_once $view;
	}

	function output_section_new_order() {
		$view = learn_press_get_admin_view( 'settings/emails/new-order.php' );
		include_once $view;
	}

	function output_section_published_course() {
		$view = learn_press_get_admin_view( 'settings/emails/published-course.php' );
		include_once $view;
	}

	function output_section_enrolled_course() {
		$view = learn_press_get_admin_view( 'settings/emails/enrolled-course.php' );
		include_once $view;
	}

	function output_section_passed_course() {
		$view = learn_press_get_admin_view( 'settings/emails/passed-course.php' );
		include_once $view;
	}

	function output_section_become_a_teacher() {
		$view = learn_press_get_admin_view( 'settings/emails/general.php' );
		include_once $view;
		$this->_become_a_teacher_request();
	}

	private function _become_a_teacher_request() {
		$view = learn_press_get_admin_view( 'settings/emails/become-a-teacher-request.php' );
		include_once $view;
	}

}

//
return new LP_Settings_Emails();