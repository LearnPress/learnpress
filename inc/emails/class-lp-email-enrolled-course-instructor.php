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

		/**
		 * Trigger email.
		 *
		 * @param int $course_id
		 * @param int $user_id
		 * @param int $user_item_id
		 */
		public function trigger( $course_id, $user_id, $user_item_id ) {

			parent::trigger( $course_id, $user_id, $user_item_id );

			if ( ! $instructor = $this->get_instructor() ) {
				return;
			}

			$user  = learn_press_get_user( $instructor->get_id() );
			$roles = $user->get_data( 'roles' );

			if ( ! $roles ) {
				return;
			}

			// if instructor is admin
			if ( in_array( 'administrator', $roles ) ) {
				// disable when turn on send admin mail option
				if ( ! learn_press_is_negative_value( LP()->settings()->get( 'emails_enrolled-course-admin' )['enable'] ) ) {
					return;
				}
			}

			$this->recipient     = $user->get_data( 'email' );
			$this->instructor_id = $user_id;

			$this->get_object();
			$this->get_variable();

			if ( $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() ) ) {
				$return[] = $this->get_recipient();
			}

			$this->recipient = $instructor->get_email();

			$this->get_object();

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	}
}

return new LP_Email_Enrolled_Course_Instructor();