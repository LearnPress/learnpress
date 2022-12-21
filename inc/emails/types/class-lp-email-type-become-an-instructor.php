<?php
/**
 * Class LP_Email_Type_Become_An_Instructor
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.1.3
 * @author tungnx
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Type_Become_An_Instructor' ) ) {

	/**
	 * Class LP_Email_Become_An_Instructor
	 */
	class LP_Email_Type_Become_An_Instructor extends LP_Email {

		public function __construct() {
			parent::__construct();

			$variable_on_email_support = apply_filters(
				'lp/email/become-an-instructor-accept-deny/variables-support',
				[]
			);

			$this->support_variables = array_merge( $this->support_variables, $variable_on_email_support );
		}

		public function handle( array $params ) {
			if ( ! $this->enable ) {
				return;
			}

			if ( ! count( $params ) ) {
				return;
			}

			$user_email = $params[0];
			$user       = get_user_by( 'email', $user_email );

			if ( ! $user ) {
				return;
			}

			$this->variables = apply_filters(
				'lp/email/become-an-instructor-accept-deny/variables-mapper',
				[]
			);

			$variables_common = $this->get_common_variables( $this->email_format );
			$this->variables  = array_merge( $this->variables, $variables_common );
			$this->set_receive( $user->user_email );
			$this->send_email();
		}
	}
}


