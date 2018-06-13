<?php
/**
 * Class LP_Email_Instructor_Denied
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Instructor_Denied' ) ) {
	/**
	 * Class LP_Email_Instructor_Denied
	 */
	class LP_Email_Instructor_Denied extends LP_Email {

		/**
		 * LP_Email_Instructor_Denied constructor.
		 */
		public function __construct() {
			$this->id          = 'instructor-denied';
			$this->title       = __( 'Denied', 'learnpress' );
			$this->description = __( 'Become an instructor email denied.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] Your request to become an instructor denied', 'learnpress' );
			$this->default_heading = __( 'Become an instructor denied', 'learnpress' );

			add_action( 'learn-press/user-become-a-teacher-deny', array( $this, 'trigger' ) );

			parent::__construct();
		}

		/**
		 * Trigger email.
		 *
		 * @param string $email
		 *
		 * @return bool
		 */
		public function trigger( $email ) {

			if ( ! $this->enable() ) {
				return false;
			}

			if ( ! $user = get_user_by( 'email', $email ) ) {
				return false;
			}

			LP_Emails::instance()->set_current( $this->id );

			$this->recipient = $email;

			$this->get_object();
			$this->get_variable();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_Instructor_Denied();