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
			$this->description     = __( 'Send an email to admin when the user has enrolled in the course.', 'learnpress' );
			$this->default_subject = __( '{{user_display_name}} has enrolled in the course', 'learnpress' );
			$this->default_heading = __( 'The user has enrolled in the course', 'learnpress' );

			$this->recipient = LP_Settings::instance()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );

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
			$this->send_email();
		}
	}
}
return new LP_Email_Enrolled_Course_Admin();
