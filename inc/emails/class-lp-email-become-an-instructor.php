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

			$this->support_variables = array_merge( $this->general_variables, array(
				'{{request_email}}',
				'{{request_phone}}',
				'{{request_message}}',
				'{{admin_user_manager}}',
				'{{accept_url}}',
				'{{deny_url}}'
			) );
		}

		/**
		 * Trigger email.
		 *
		 * @param string $email
		 *
		 * @return bool
		 */
		public function trigger( $args ) {
			if ( ! $this->enable ) {
				return false;
			}

			$email   = $args['bat_email'];
			$phone   = $args['bat_phone'];
			$message = $args['bat_message'];

			if ( ! $user = get_user_by( 'email', $email ) ) {
				return false;
			}

			LP_Emails::instance()->set_current( $this->id );

			$this->recipient = $this->_get_admin_email();// get_option( 'admin_email' );

			$this->get_object( null, array(
				'request_email'      => $email,
				'request_phone'      => $phone,
				'request_message'    => $message ? $message : '',
				'admin_user_manager' => admin_url( 'users.php?lp-action=pending-request' ),
				'accept_url'         => admin_url( 'users.php?lp-action=accept-request&user_id=' . $user->ID ),
				'deny_url'           => admin_url( 'users.php?lp-action=deny-request&user_id=' . $user->ID )
			) );
			$this->get_variable();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_Become_An_Instructor();