<?php
/**
 * LP_Email_Enrolled_Course_User.
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

if ( ! class_exists( 'LP_Email_Enrolled_Course_User' ) ) {

	/**
	 * Class LP_Email_Enrolled_Course_User
	 */
	class LP_Email_Enrolled_Course_User extends LP_Email_Type_Enrolled_Course {
		/**
		 * LP_Email_Enrolled_Course_User constructor.
		 */
		public function __construct() {
			$this->id          = 'enrolled-course-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Send this email to user when they have enrolled course.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] You have enrolled in course', 'learnpress' );
			$this->default_heading = __( 'You have enrolled course', 'learnpress' );

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

			$user = learn_press_get_user( $user_id );

			$this->recipient = $user->get_email();

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	}
}

return new LP_Email_Enrolled_Course_User();
