<?php
/**
 * Class LP_Email_Reset_Password
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.1.5
 * @author Nhamdv
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Reset_Password' ) ) {
	class LP_Email_Reset_Password extends LP_Email {

		public function __construct() {
			$this->id          = 'reset-password';
			$this->title       = esc_html__( 'Reset Password', 'learnpress' );
			$this->description = esc_html__( 'Password Reset Email.', 'learnpress' );

			$this->default_subject = esc_html__( '[{{site_title}}] Reset Password', 'learnpress' );
			$this->default_heading = esc_html__( 'Reset Password', 'learnpress' );

			parent::__construct();

			$variable_on_email_support = apply_filters(
				'lp/email/reset-password/variables-support',
				array(
					'{{user_login}}',
					'{{user_email}}',
					'{{user_id}}',
					'{{reset_key}}',
					'{{reset_link}}',
				)
			);

			$this->support_variables = array_merge( $this->support_variables, $variable_on_email_support );
		}

		/**
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

			if ( ! $this->set_data_content( $params ) ) {
				return;
			}

			$this->send_email();
		}

		/**
		 * Set data content
		 */
		public function set_data_content( $params ): bool {
			$user_login = $params['user_login'] ?? '';
			$reset_key  = $params['reset_key'] ?? '';

			$user = get_user_by( 'login', $user_login );

			if ( ! $user ) {
				return false;
			}

			$locale = get_user_locale( $user );

			$this->variables = apply_filters(
				'lp/email/type-reset-password/variables-mapper',
				array(
					'{{user_login}}' => $user_login,
					'{{user_email}}' => stripslashes( $user->user_email ),
					'{{user_id}}'    => $user->ID,
					'{{reset_key}}'  => $reset_key,
					'{{reset_link}}' => network_site_url( 'wp-login.php?action=rp&key=' . $reset_key . '&login=' . rawurlencode( $user_login ), 'login' ) . '&wp_lang=' . $locale,
				)
			);

			$this->set_receive( $user->user_email );

			$variables_common = $this->get_common_variables( $this->email_format );
			$this->variables  = array_merge( $this->variables, $variables_common );

			return true;
		}
	}

	return new LP_Email_Reset_Password();
}

