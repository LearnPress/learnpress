<?php
/**
 * LP_Email_Finished_Course_Admin.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Finished_Course_Admin' ) ) {
	class LP_Email_Finished_Course_Admin extends LP_Email_Type_Finished_Course {

		/**
		 * LP_Email_Finished_Course_Admin constructor.
		 */
		public function __construct() {
			$this->id              = 'finished-course-admin';
			$this->title           = __( 'Admin', 'learnpress' );
			$this->description     = __( 'Send an email to admin when the user has finished the course.', 'learnpress' );
			$this->default_subject = __( '{{user_display_name}} has completed the course.', 'learnpress' );
			$this->default_heading = __( 'The user has completed the course.', 'learnpress' );

			$this->recipient = LP_Settings::instance()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

			parent::__construct();
		}
	}

	return new LP_Email_Finished_Course_Admin();
}

