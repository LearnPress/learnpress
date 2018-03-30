<?php
/**
 * LP_Email_Enrolled_Course_Admin.
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

if ( ! class_exists( 'LP_Email_Enrolled_Course_Admin' ) ) {

	/**
	 * Class LP_Email_Enrolled_Course_Admin
	 */
	class LP_Email_Enrolled_Course_Admin extends LP_Email_Type_Enrolled_Course {

		/**
		 * LP_Email_Enrolled_Course_Admin constructor.
		 */
		public function __construct() {
			$this->id              = 'enrolled-course-admin';
			$this->title           = __( 'Admin', 'learnpress' );
			$this->description     = __( 'Send this email to admin when user has enrolled course.', 'learnpress' );
			$this->default_subject = __( '{{user_display_name}} has enrolled course', 'learnpress' );
			$this->default_heading = __( 'User has enrolled course', 'learnpress' );

			$this->recipient = LP()->settings->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

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

			if ( ! $this->enable ) {
				return;
			}

			$this->get_object();

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	}
}
return new LP_Email_Enrolled_Course_Admin();