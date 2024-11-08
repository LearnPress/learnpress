<?php
/**
 * LP_Email_Enrolled_Course_User.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email
 * @version 3.0.1
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
			$this->description = __( 'Send this email to the user when they have enrolled in the course.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] You have enrolled in the course', 'learnpress' );
			$this->default_heading = __( 'You have enrolled in the course', 'learnpress' );

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

			$user_email = '';
			if ( $this->_user ) {
				$user_email = $this->_user->get_email();
			} else {
				$user_email = $this->_order->get_user_email();
			}

			$this->set_data_content();
			$this->set_receive( $user_email );
			$this->send_email();
		}
	}
}

return new LP_Email_Enrolled_Course_User();
