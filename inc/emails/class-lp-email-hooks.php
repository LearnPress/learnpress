<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Hooks' ) ) {

	/**
	 * Class LP_Email_Hooks
	 */
	class LP_Email_Hooks {
		protected static $instance;
		protected $actions;

		protected function __construct() {
			// Define class handle send email with hook corresponding
			$this->actions = apply_filters(
				'learn-press/email-actions',
				[
					// preview course
					'learn_press_course_submit_rejected',
					'learn_press_course_submit_approved',
					'learn_press_course_submit_for_reviewer',

					// New order
					'learn-press/order/status-pending-to-processing' => [
						LP_Email_New_Order_Admin::class => LP_PLUGIN_PATH . 'inc/emails/admin/class-lp-email-new-order-admin.php',
						LP_Email_New_Order_User::class  => LP_PLUGIN_PATH . 'inc/emails/student/class-lp-email-new-order-user.php',
						LP_Email_New_Order_Instructor::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-new-order-instructor.php',
						LP_Email_New_Order_Guest::class => LP_PLUGIN_PATH . 'inc/emails/guest/class-lp-email-new-order-guest.php',
						LP_Email_Processing_Order_User::class => LP_PLUGIN_PATH . 'inc/emails/student/class-lp-email-processing-order-user.php',
						LP_Email_Processing_Order_Guest::class => LP_PLUGIN_PATH . 'inc/emails/guest/class-lp-email-processing-order-guest.php',
					],
					'learn-press/order/status-pending-to-completed' => [
						LP_Email_New_Order_Admin::class => LP_PLUGIN_PATH . 'inc/emails/admin/class-lp-email-new-order-admin.php',
						LP_Email_New_Order_User::class  => LP_PLUGIN_PATH . 'inc/emails/student/class-lp-email-new-order-user.php',
						LP_Email_New_Order_Instructor::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-new-order-instructor.php',
						LP_Email_New_Order_Guest::class => LP_PLUGIN_PATH . 'inc/emails/guest/class-lp-email-new-order-guest.php',
					],

					// Completed order
					'learn-press/order/status-completed' => [
						LP_Email_Completed_Order_User::class => LP_PLUGIN_PATH . 'inc/emails/student/class-lp-email-completed-order-user.php',
						LP_Email_Completed_Order_Admin::class => LP_PLUGIN_PATH . 'inc/emails/admin/class-lp-email-completed-order-admin.php',
						LP_Email_Completed_Order_Guest::class => LP_PLUGIN_PATH . 'inc/emails/guest/class-lp-email-completed-order-guest.php',
					],

					// User enrolled course when order completed before
					'learnpress/user/course-enrolled'    => [
						LP_Email_Enrolled_Course_User::class => LP_PLUGIN_PATH . 'inc/emails/student/class-lp-email-enrolled-course-user.php',
						LP_Email_Enrolled_Course_Admin::class => LP_PLUGIN_PATH . 'inc/emails/admin/class-lp-email-enrolled-course-admin.php',
						LP_Email_Enrolled_Course_Instructor::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-enrolled-course-instructor.php',
					],

					// Cancelled order
					'learn-press/order/status-cancelled' => [
						LP_Email_Cancelled_Order_User::class => LP_PLUGIN_PATH . 'inc/emails/student/class-lp-email-cancelled-order-user.php',
						LP_Email_Cancelled_Order_Admin::class => LP_PLUGIN_PATH . 'inc/emails/admin/class-lp-email-cancelled-order-admin.php',
						LP_Email_Cancelled_Order_Guest::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-cancelled-order-guest.php',
						LP_Email_Cancelled_Order_Instructor::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-cancelled-order-instructor.php',
					],

					// Finished course
					'learn-press/user-course-finished'   => [
						LP_Email_Finished_Course_Admin::class => LP_PLUGIN_PATH . 'inc/emails/admin/class-lp-email-finished-course-admin.php',
						LP_Email_Finished_Course_User::class => LP_PLUGIN_PATH . 'inc/emails/student/class-lp-email-finished-course-user.php',
						LP_Email_Finished_Course_Instructor::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-finished-course-instructor.php',
					],

					// User become a teacher
					'learn-press/become-a-teacher-sent'  => [
						LP_Email_Become_An_Instructor::class => LP_PLUGIN_PATH . 'inc/emails/admin/class-lp-email-become-an-instructor.php',
					],
					'learn-press/user-become-a-teacher-accept' => [
						LP_Email_Instructor_Accepted::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-instructor-accepted.php',
					],
					'learn-press/user-become-a-teacher-deny' => [
						LP_Email_Instructor_Denied::class => LP_PLUGIN_PATH . 'inc/emails/instructor/class-lp-email-instructor-denied.php',
					],
				]
			);

			foreach ( $this->actions as $tag_hook => $action ) {
				add_action( $tag_hook, array( $this, 'handle_send_email_on_background' ), 10, 10 );
			}
		}

		protected function include() {
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
			$email_bg       = LP_Background_Single_Email::instance();
			$current_filter = current_filter();

			try {
				if ( isset( $this->actions[ $current_filter ] ) && is_array( $this->actions[ $current_filter ] ) ) {
					foreach ( $this->actions[ $current_filter ] as $class_email => $path_file ) {
						$data_send = [
							'params'     => $args,
							'class_name' => $class_email,
						];
						$email_bg->data( $data_send )->dispatch();
					}
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
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

