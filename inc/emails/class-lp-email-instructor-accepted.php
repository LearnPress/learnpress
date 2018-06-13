<?php
/**
 * Class LP_Email_Instructor_Accepted
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Instructor_Accepted' ) ) {
	/**
	 * Class LP_Email_Instructor_Accepted
	 */
	class LP_Email_Instructor_Accepted extends LP_Email {

		/**
		 * LP_Email_Instructor_Accepted constructor.
		 */
		public function __construct() {
			$this->id          = 'instructor-accepted';
			$this->title       = __( 'Accepted', 'learnpress' );
			$this->description = __( 'Become an instructor email accepted.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] Your request to become an instructor accepted', 'learnpress' );
			$this->default_heading = __( 'Become an instructor accepted', 'learnpress' );

			add_action( 'learn-press/user-become-a-teacher-accept', array( $this, 'trigger' ) );
			add_action( 'set_user_role', array( $this, 'set_user_role' ), 10, 3 );

			parent::__construct();
		}

		/**
		 * Set user role.
		 *
		 * @param $user_id
		 * @param $role
		 * @param $old_roles
		 */
		public function set_user_role( $user_id, $role, $old_roles ) {
			if ( LP_TEACHER_ROLE === $role ) {
				$user = get_user_by( 'id', $user_id );
				$this->trigger( $user->user_email );
			}
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

return new LP_Email_Instructor_Accepted();