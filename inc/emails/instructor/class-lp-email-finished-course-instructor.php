<?php
/**
 * LP_Email_Finished_Course_Instructor.
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

if ( ! class_exists( 'LP_Email_Finished_Course_Instructor' ) ) {

	/**
	 * Class LP_Email_Finished_Course_Instructor
	 */
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

		/**
		 * Trigger email.
		 *
		 * @param int $course_id
		 * @param int $user_id
		 * @param int $user_item_id
		 *
		 * @return bool|mixed
		 */
		public function trigger( $course_id, $user_id, $user_item_id ) {

			parent::trigger( $course_id, $user_id, $user_item_id );

			if ( ! $this->enable ) {
				return false;
			}

			if ( ! $instructor = $this->get_instructor() ) {
				return false;
			}

			/**
			 * If the instructor also is admin and email for admin is enabled
			 */
			$instructor_email = $instructor->get_email();
			$admin_email      = apply_filters( 'learn-press/email/admin-email', get_option( 'admin_email' ) );
			$admin_email      = LP()->settings->get( 'emails_finished-course-admin.recipients', $admin_email );

			if ( $instructor->is_admin() && $admin_email == $instructor_email && LP_Emails::get_email( 'finished-course-admin' )->enable() ) {
				return false;
			}

			$this->recipient = $instructor->get_data( 'email' );

			$this->get_object();

			if ( $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() ) ) {
				$return = $this->get_recipient();

				return $return;
			}

			return false;
		}
	}
}

return new LP_Email_Finished_Course_Instructor();
