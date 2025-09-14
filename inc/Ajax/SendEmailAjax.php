<?php
/**
 * class SendEmailAjax
 *
 * @since 4.2.9.1
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Models\UserItems\UserCourseModel;
use LP_Email;
use LP_Email_Become_An_Instructor;
use LP_Email_Cancelled_Order_Admin;
use LP_Email_Cancelled_Order_Guest;
use LP_Email_Cancelled_Order_Instructor;
use LP_Email_Cancelled_Order_User;
use LP_Email_Completed_Order_Admin;
use LP_Email_Completed_Order_Guest;
use LP_Email_Completed_Order_User;
use LP_Email_Enrolled_Course_Admin;
use LP_Email_Enrolled_Course_Instructor;
use LP_Email_Enrolled_Course_User;
use LP_Email_Finished_Course_Admin;
use LP_Email_Finished_Course_Instructor;
use LP_Email_Finished_Course_User;
use LP_Email_Instructor_Accepted;
use LP_Email_Instructor_Denied;
use LP_Email_New_Order_Admin;
use LP_Email_New_Order_Guest;
use LP_Email_New_Order_Instructor;
use LP_Email_New_Order_User;
use LP_Email_Processing_Order_Guest;
use LP_Email_Processing_Order_User;
use LP_Helper;
use LP_REST_Response;
use Throwable;

class SendEmailAjax extends AbstractAjax {
	/**
	 * Send mail when order status update to complete
	 *
	 * @since 4.2.9.1
	 * @version 1.0.1
	 */
	public function send_mail_order_status_pending_to_processing() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email_classes = apply_filters(
				'learn-press/order-status-pending-to-processing/send-mail',
				[
					LP_Email_New_Order_Admin::class,
					LP_Email_New_Order_User::class,
					LP_Email_New_Order_Instructor::class,
					LP_Email_New_Order_Guest::class,
					LP_Email_Processing_Order_User::class,
					LP_Email_Processing_Order_Guest::class,
				]
			);

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Send mail when order status update to complete
	 *
	 * @since 4.2.9.1
	 * @version 1.0.1
	 */
	public function send_mail_order_status_pending_to_completed() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email_classes = apply_filters(
				'learn-press/order-status-pending-to-completed/send-mail',
				[
					LP_Email_New_Order_Admin::class,
					LP_Email_New_Order_User::class,
					LP_Email_New_Order_Instructor::class,
					LP_Email_New_Order_Guest::class,
				]
			);

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Send mail when order status update to complete
	 *
	 * @since 4.2.9.1
	 * @version 1.0.1
	 */
	public function send_mail_order_status_update_to_completed() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email_classes = apply_filters(
				'learn-press/order-status-update-to-completed/send-mail',
				[
					LP_Email_Completed_Order_Admin::class,
					LP_Email_Completed_Order_User::class,
					LP_Email_Completed_Order_Guest::class,
				]
			);

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Send mail when order status update to cancel
	 *
	 * @since 4.2.9.1
	 * @version 1.0.1
	 */
	public function send_mail_order_status_update_to_cancelled() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email_classes = apply_filters(
				'learn-press/order-status-update-to-cancelled/send-mail',
				[
					LP_Email_Cancelled_Order_User::class,
					LP_Email_Cancelled_Order_Admin::class,
					LP_Email_Cancelled_Order_Guest::class,
					LP_Email_Cancelled_Order_Instructor::class,
				]
			);

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Send mails for case multiple users finish courses
	 * Order has many users, courses change status to completed
	 *
	 * @throws Exception
	 * @version 1.0.1
	 * @since 4.2.9.1
	 */
	public function send_mail_users_enrolled_courses() {
		$user_course_ids = LP_Helper::sanitize_params_submitted( $_POST['user_course_ids'] ?? [] );

		$email_classes = apply_filters(
			'learn-press/users-enrolled-courses/send-mail',
			[
				LP_Email_Enrolled_Course_User::class,
				LP_Email_Enrolled_Course_Admin::class,
				LP_Email_Enrolled_Course_Instructor::class,
			]
		);

		foreach ( $user_course_ids as $user_course_id ) {
			$user_id         = $user_course_id['user_id'] ?? 0;
			$course_id       = $user_course_id['course_id'] ?? 0;
			$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $userCourseModel ) {
				continue;
			}

			$data_send = [
				$userCourseModel->ref_id,
				$userCourseModel->item_id,
				$userCourseModel->user_id,
			];

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		}
	}

	/**
	 * Send mail for case a user enrolled a course.
	 *
	 * @throws Exception
	 * @version 1.0.1
	 * @since 4.2.9.1
	 */
	public function send_mail_user_enrolled_course() {
		$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

		$email_classes = apply_filters(
			'learn-press/user-enrolled-course/send-mail',
			[
				LP_Email_Enrolled_Course_User::class,
				LP_Email_Enrolled_Course_Admin::class,
				LP_Email_Enrolled_Course_Instructor::class,
			]
		);

		foreach ( $email_classes as $email_class ) {
			if ( class_exists( $email_class ) ) {
				$email = new $email_class();
				/** @var LP_Email $email */
				$email->handle( $data_send );
			}
		}
	}

	/**
	 * Send mail when user finish course
	 *
	 * @since 4.2.9.1
	 * @version 1.0.1
	 */
	public function send_mail_user_course_finished() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email_classes = apply_filters(
				'learn-press/user-finished-course/send-mail',
				[
					LP_Email_Finished_Course_Admin::class,
					LP_Email_Finished_Course_User::class,
					LP_Email_Finished_Course_Instructor::class,
				]
			);

			foreach ( $email_classes as $email_class ) {
				if ( class_exists( $email_class ) ) {
					$email = new $email_class();
					/** @var LP_Email $email */
					$email->handle( $data_send );
				}
			}
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Send mail when user request to become a teacher
	 *
	 * @since 4.2.9.1
	 * @version 1.0.0
	 */
	public function send_mail_become_a_teacher_request() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email = new LP_Email_Become_An_Instructor();
			$email->handle( $data_send );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Send mail when admin accept user become a teacher
	 *
	 * @since 4.2.9.1
	 * @version 1.0.0
	 */
	public function send_mail_become_a_teacher_accept() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email = new LP_Email_Instructor_Accepted();
			$email->handle( $data_send );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Send mail when admin deny user become a teacher
	 *
	 * @since 4.2.9.1
	 * @version 1.0.0
	 */
	public function send_mail_become_a_teacher_deny() {
		$response = new LP_REST_Response();

		try {
			$data_send = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? [] );

			$email = new LP_Email_Instructor_Denied();
			$email->handle( $data_send );
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
