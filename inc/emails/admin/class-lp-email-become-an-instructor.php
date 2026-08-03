<?php
/**
 * Class LP_Email_Become_An_Instructor
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.2
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Become_An_Instructor' ) ) {
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

			parent::__construct();

			$variable_on_email_support = apply_filters(
				'lp/email/become-an-instructor/variables-support',
				[
					'{{request_email}}',
					'{{request_phone}}',
					'{{request_message}}',
					'{{admin_user_manager}}',
					'{{accept_url}}',
					'{{deny_url}}',
				]
			);

			$this->support_variables = array_merge( $this->support_variables, $variable_on_email_support );
		}

		/**
		 * Check email enable option
		 * Check param valid: 4 params: bat_name, bat_email, bat_phone, bat_message
		 * Set values
		 *
		 * @param array $params
		 * @throws Exception
		 */
		public function handle( array $params ) {
			if ( ! $this->enable ) {
				return;
			}

			if ( count( $params ) < 1 ) {
				return;
			}

			if ( ! $this->set_data_content( $params[0] ) ) {
				return;
			}
			$this->set_receive( $this->_get_admin_email() );
			$this->send_email();
		}

		/**
		 * Set data content
		 */
		protected function set_data_content( $params ): bool {
			$bat_email   = $params['bat_email'] ?? '';
			$bat_phone   = $params['bat_phone'] ?? '';
			$bat_message = $params['bat_message'] ?? '';

			$user = get_user_by( 'email', $bat_email );
			if ( ! $user ) {
				return false;
			}

			$content_format = $this->get_content_format();
			$is_html        = 'text/plain' !== $content_format;

			$admin_user_manager = admin_url( 'users.php?lp-action=pending-request' );
			$accept_url         = admin_url( 'users.php?lp-action=accept-request&user_id=' . $user->ID );
			$deny_url           = admin_url( 'users.php?lp-action=deny-request&user_id=' . $user->ID );

			if ( $is_html ) {
				$admin_user_manager = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $admin_user_manager ),
					esc_url( $admin_user_manager )
				);

				$accept_url = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $accept_url ),
					esc_url( $accept_url )
				);

				$deny_url = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $deny_url ),
					esc_url( $deny_url )
				);
			}

			$this->variables = apply_filters(
				'lp/email/type-become-an-instructor-admin/variables-mapper',
				[
					'{{request_email}}'      => $bat_email,
					'{{request_phone}}'      => $bat_phone,
					'{{request_message}}'    => $bat_message,
					'{{admin_user_manager}}' => $admin_user_manager,
					'{{accept_url}}'         => $accept_url,
					'{{deny_url}}'           => $deny_url,
				]
			);

			$variables_common = $this->get_common_variables( $this->email_format );
			$this->variables  = array_merge( $this->variables, $variables_common );

			return true;
		}
	}

	return new LP_Email_Become_An_Instructor();
}
