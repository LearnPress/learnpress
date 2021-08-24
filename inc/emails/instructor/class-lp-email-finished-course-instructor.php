<?php
/**
 * LP_Email_Finished_Course_Instructor.
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

if ( ! class_exists( 'LP_Email_Finished_Course_Instructor' ) ) {
	class LP_Email_Finished_Course_Instructor extends LP_Email_Type_Finished_Course {
		/**
		 * LP_Email_Finished_Course_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'finished-course-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send this email to instructor when they have finished course.', 'learnpress' );

			$this->default_subject = __( '{{user_display_name}} has finished course', 'learnpress' );
			$this->default_heading = __( 'User has finished course', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Finished_Course_Instructor();
}

