<?php
/**
 * LP_Email_Enrolled_Course_Instructor.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Enrolled_Course_Instructor' ) ) {

	/**
	 * Class LP_Email_Enrolled_Course_Instructor
	 */
	class LP_Email_Enrolled_Course_Instructor extends LP_Email_Type_Enrolled_Course {
		/**
		 * LP_Email_Enrolled_Course_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'enrolled-course-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send this email to instructor when they have enrolled course.', 'learnpress' );

			$this->default_subject = __( '{{user_display_name}} has enrolled course', 'learnpress' );
			$this->default_heading = __( 'User has enrolled course', 'learnpress' );

			parent::__construct();
		}
	}
}

return new LP_Email_Enrolled_Course_Instructor();
