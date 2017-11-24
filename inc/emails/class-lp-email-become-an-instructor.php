<?php
/**
 * Class LP_Email_Become_An_Instructor
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Become_An_Instructor' ) ) {

	/**
	 * Class LP_Email_Become_An_Instructor
	 */
	class LP_Email_Become_An_Instructor extends LP_Email {

		/**
		 * LP_Email_Become_An_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'become-an-instructor';
			$this->title       = __( 'Request', 'learnpress' );
			$this->description = __( 'Become an instructor email.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] Request to become an instructor', 'learnpress' );
			$this->default_heading = __( 'Become an instructor', 'learnpress' );

			add_action( 'learn-press/become-a-teacher-sent', array( $this, 'trigger' ) );

			parent::__construct();

			$this->support_variables[] = '{{request_email}}';
		}

		/**
		 * Trigger email.
		 *
		 * @param string $email
		 *
		 * @return bool
		 */
		public function trigger( $email ) {
			if ( ! $this->enable ) {
				return false;
			}

			if ( ! $user = get_user_by( 'email', $email ) ) {
				return false;
			}

			LP_Emails::instance()->set_current( $this->id );

			$this->recipient = get_option( 'admin_email' );

			$this->get_object( null, array(
				'request_email' => $email
			) );
			$this->get_variable();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_Become_An_Instructor();