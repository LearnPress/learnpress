<?php

use LearnPress\Background\LPBackgroundAjax;
use LearnPress\Models\UserItems\UserCourseModel;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Hooks' ) ) {

	/**
	 * Class LP_Email_Hooks
	 *
	 * @uses SendEmailAjax::send_mail_order_status_pending_to_processing
	 * @uses SendEmailAjax::send_mail_order_status_pending_to_completed
	 * @uses SendEmailAjax::send_mail_order_status_update_to_completed
	 * @uses SendEmailAjax::send_mail_order_status_update_to_cancelled
	 * @uses SendEmailAjax::send_mail_user_course_finished
	 * @uses SendEmailAjax::send_mail_become_a_teacher_request
	 * @uses SendEmailAjax::send_mail_become_a_teacher_accept
	 * @uses SendEmailAjax::send_mail_become_a_teacher_deny
	 */
	class LP_Email_Hooks {
		protected static $instance;
		protected $actions;

		protected function __construct() {
			// Define class handle send email with hook corresponding
			$this->actions = apply_filters(
				'learn-press/email-actions-hooks',
				[
					// preview course
					'learn_press_course_submit_rejected',
					'learn_press_course_submit_approved',
					'learn_press_course_submit_for_reviewer',

					// New order
					'learn-press/order/status-pending-to-processing' => 'send_mail_order_status_pending_to_processing',
					'learn-press/order/status-pending-to-completed' => 'send_mail_order_status_pending_to_completed',
					// Completed order
					'learn-press/order/status-completed' => 'send_mail_order_status_update_to_completed',
					// Cancelled order
					'learn-press/order/status-cancelled' => 'send_mail_order_status_update_to_cancelled',
					// Finished course
					'learn-press/user-course-finished'   => 'send_mail_user_course_finished',
					// User become a teacher
					'learn-press/become-a-teacher-sent'  => 'send_mail_become_a_teacher_request',
					'learn-press/user-become-a-teacher-accept' => 'send_mail_become_a_teacher_accept',
					'learn-press/user-become-a-teacher-deny' => 'send_mail_become_a_teacher_deny',
				]
			);

			foreach ( $this->actions as $tag_hook => $action ) {
				add_action( $tag_hook, array( $this, 'handle_send_email_on_background' ), 11, 10 );
			}

			// Override message change password
			add_filter( 'retrieve_password_notification_email', array( $this, 'retrieve_password_message' ), 11, 4 );
		}

		/**
		 * Call background email
		 * Check hook and call class email corresponding
		 *
		 * @author tungnx
		 * @since 4.1.1
		 * @version 1.0.0
		 */
		public function handle_send_email_on_background() {
			$args           = func_get_args();
			$args           = array_merge( $args, [ 'params_request' => $_REQUEST ] );
			$email_bg       = LP_Background_Single_Email::instance();
			$current_filter = current_filter();

			try {
				if ( isset( $this->actions[ $current_filter ] ) ) {
					if ( is_string( $this->actions[ $current_filter ] ) ) { // For new declaration, only string callback
						$data_send = [
							'params'       => $args,
							'lp-load-ajax' => $this->actions[ $current_filter ],
						];
						LPBackgroundAjax::handle( $data_send );
					} elseif ( is_array( $this->actions[ $current_filter ] ) ) { // For old declaration, has array
						foreach ( $this->actions[ $current_filter ] as $class_email => $path_file ) {
							$data_send = [
								'params'     => $args,
								'class_name' => $class_email,
							];
							$email_bg->data( $data_send )->dispatch();
						}
					}
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Override message reset password
		 *
		 * @param array $data_mail
		 * @param string $key
		 * @param string $user_login
		 * @param WP_User $user_data
		 *
		 * @return array
		 * @version 1.0.1
		 * @since 4.2.6.9
		 */
		public function retrieve_password_message( $data_mail, $key, $user_login, $user_data ) {
			try {
				include_once LP_PLUGIN_PATH . 'inc/emails/types/class-lp-email-reset-password.php';
				$email_reset_pass = new LP_Email_Reset_Password();
				if ( ! $email_reset_pass->enable ) {
					return $data_mail;
				}

				$email_reset_pass = new LP_Email_Reset_Password();
				$params           = [
					'user_login' => $user_login,
					'reset_key'  => $key,
				];
				$email_reset_pass->set_data_content( $params );
				$message              = $email_reset_pass->get_content();
				$message              = apply_filters( 'learn-press/email-content', $email_reset_pass->apply_style_inline( $message ), $this );
				$data_mail['message'] = $message;
				$data_mail['subject'] = $email_reset_pass->get_subject();
				$data_mail['headers'] = $email_reset_pass->get_headers();
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $data_mail;
		}

		/**
		 * @return LP_Email_Hooks
		 */
		public static function instance(): self {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	LP_Email_Hooks::instance();
}
