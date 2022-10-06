<?php
/**
 * LP_Email_Enrolled_Course_Instructor.
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

if ( ! class_exists( 'LP_Email_Enrolled_Course_Instructor' ) ) {
	class LP_Email_Enrolled_Course_Instructor extends LP_Email_Type_Enrolled_Course {
		/**
		 * LP_Email_Enrolled_Course_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'enrolled-course-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send this email to the instructor when they have enrolled in the course.', 'learnpress' );

			$this->default_subject = __( '{{user_display_name}} has enrolled in the course', 'learnpress' );
			$this->default_heading = __( 'The user has enrolled in the course', 'learnpress' );

			parent::__construct();
		}

		/**
		 * Trigger email.
		 * Receive 2 params: order_id, old_status
		 *
		 * @param array $params
		 *
		 * @throws Exception
		 * @since 4.1.1
		 * @author tungnx
		 */
		public function handle( array $params ) {
			if ( ! $this->check_and_set( $params ) ) {
				return;
			}

			$this->set_data_content();
			$this->set_receive( $this->_course->get_author()->get_email() );
			$this->send_email();
		}
	}

	return new LP_Email_Enrolled_Course_Instructor();
}

