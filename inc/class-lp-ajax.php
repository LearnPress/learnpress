<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_AJAX' ) ) {
	class LP_AJAX {
		/**
		 * Init common ajax events
		 */
		public static function init() {
			$ajax_events = array(
				'checkout-user-email-exists:nopriv',
				'recover-order',
				'request-become-a-teacher:nonce',
				'checkout:nopriv',
				//'complete-lesson',
				'finish-course', // finish_course.
				'external-link:nopriv',
			);

			$ajax_events = apply_filters( 'learn-press/ajax/events', $ajax_events );

			foreach ( $ajax_events as $action => $callback ) {

				if ( is_numeric( $action ) ) {
					$action = $callback;
				}

				$actions = LP_Request::parse_action( $action );
				$method  = $actions['action'];

				if ( ! is_callable( $callback ) ) {
					$method   = preg_replace( '/-/', '_', $method );
					$callback = array( __CLASS__, $method );
				}

				LP_Request::register_ajax( $action, $callback );
			}
		}

		public static function external_link() {
			$nonce  = LP_Request::get( 'nonce' );
			$id     = LP_Request::get( 'id' );
			$course = learn_press_get_course( $id );

			if ( ! $course ) {
				return;
			}

			$link = $course->get_external_link();

			if ( ! wp_verify_nonce( $nonce, 'external-link-' . $link ) ) {
				return;
			}

			if ( apply_filters( 'learn-press/course-redirect-external-link', $id ) ) {
				wp_redirect( $link );
				exit();
			}
		}

		public static function checkout() {
			LearnPress::instance()->checkout()->process_checkout_handler();
		}

		public static function request_become_a_teacher() {
			LP_Forms_Handler::process_become_teacher();
		}

		/**
		 * Recover order
		 *
		 * @since 4.0.0
		 * @version 4.0.1
		 * @return void
		 */
		public static function recover_order() {
			$response = new LP_REST_Response();

			if ( ! LP_Request::verify_nonce( 'recover-order' ) ) {
				return;
			}

			$factory   = new LP_Order_CURD();
			$user_id   = get_current_user_id();
			$order_key = LP_Request::get_string( 'order-key' );
			$order     = $factory->recover( $order_key, $user_id );

			if ( is_wp_error( $order ) ) {
				$response->message = $order->get_error_message();
			} else {
				$response->status         = 'success';
				$response->message        = sprintf(
					__( 'The order %s has been successfully recovered.', 'learnpress' ),
					$order_key
				);
				$response->data->redirect = $order->get_view_order_url();
			}

			wp_send_json( $response );
		}

		public static function checkout_user_email_exists() {
			$response = new LP_REST_Response();

			try {
				$email             = LP_Request::get_email( 'email' );
				$user_can_register = get_option( 'users_can_register' );
				$html_wrapper      = [
					'<label class="lp-guest-checkout-output">' => '</label>',
				];

				if ( email_exists( $email ) ) {
					$output = __(
						'Your email already exists. Do you want to continue with this email?',
						'learnpress'
					);
				} elseif ( $user_can_register ) {
					$output = sprintf(
						'<input type="checkbox" name="checkout-email-option" value="new-account"> <span>%s</span>',
						__(
							'Create a new account with this email. The account information will be sent with this email.',
							'learnpress'
						)
					);
				} else {
					$output = __( 'The system does not allow the creation of a new account, you must enter an existing account.', 'learnpress' );
				}

				$response->status        = 'success';
				$response->data->content = Template::instance()->nest_elements( $html_wrapper, $output );
			} catch ( Throwable $e ) {
				$response->message = $e->getMessage();
			}

			wp_send_json( $response );
		}

		/**
		 * Request finish course
		 *
		 * TODO: should move this function to api - tungnx
		 */
		public static function finish_course() {
			$link_redirect = '';
			$message_data  = [
				'status'  => 'error',
				'content' => '',
			];

			try {
				$nonce       = LP_Request::get_param( 'finish-course-nonce', '', 'text', 'post' );
				$course_id   = LP_Request::get_param( 'course-id', 0, 'int', 'post' );
				$courseModel = CourseModel::find( $course_id, true );
				$user_id     = get_current_user_id();
				$userModel   = UserModel::find( $user_id, true );

				$nonce_action = sprintf( 'finish-course-%d-%d', $course_id, $user_id );
				if ( ! $courseModel || ! $userModel || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
					throw new Exception( __( 'Request is invalid!', 'learnpress' ) );
				}

				$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
				if ( ! $userCourseModel instanceof UserCourseModel ) {
					throw new Exception( __( 'You have not enrolled in this course.', 'learnpress' ) );
				}

				$can_finish = $userCourseModel->can_finish();
				if ( is_wp_error( $can_finish ) ) {
					throw new Exception( $can_finish->get_error_message() );
				}

				$userCourseModel->handle_finish();
				$lp_redirect             = LP_Settings::get_option( 'course_finish_redirect' );
				$link_redirect           = ! empty( $lp_redirect ) ? $lp_redirect : $courseModel->get_permalink();
				$link_redirect           = esc_url_raw( $link_redirect );
				$message_data['status']  = 'success';
				$message_data['content'] = __( 'Course has been finished successfully.', 'learnpress' );
			} catch ( Throwable $e ) {
				$message_data['content'] = $e->getMessage();
			}

			learn_press_set_message( $message_data );
			if ( ! empty( $link_redirect ) ) {
				wp_redirect( $link_redirect );
				die();
			}
		}

		/**
		 * Complete lesson
		 * @deprecated 4.2.7.6
		 */
		/*public static function complete_lesson() {
			$response = array(
				'result'   => 'error',
				'redirect' => '',
			);

			try {
				$nonce        = LP_Request::get_param( 'complete-lesson-nonce' );
				$lesson_id    = LP_Request::get_param( 'id', 0, 'int' );
				$course_id    = LP_Request::get_param( 'course_id', 0, 'int' );
				$item_id_next = LP_Request::get_param( 'item_id_next', 0, 'int' );

				if ( ! wp_verify_nonce( $nonce, 'lesson-complete' ) ) {
					throw new Exception( __( 'Error! Invalid lesson or failed security check.', 'learnpress' ) );
				}

				$lesson = get_post( $lesson_id );
				if ( ! $lesson || $lesson->post_type !== LP_LESSON_CPT ) {
					throw new Exception( __( 'Error! Invalid lesson.', 'learnpress' ) );
				}

				$user = learn_press_get_current_user();
				if ( $user instanceof LP_User_Guest ) {
					throw new Exception( __( 'Please login.', 'learnpress' ) );
				}

				$course = learn_press_get_course( $course_id );
				if ( ! $course ) {
					throw new Exception( __( 'Course is invalid!.', 'learnpress' ) );
				}

				$item = $course->get_item( $lesson_id );
				if ( ! $item instanceof LP_Course_Item ) {
					throw new Exception( 'Item is invalid!', 'learnpress' );
				}

				$result = $user->complete_lesson( $lesson_id, $course_id );
				if ( $item_id_next ) {
					$response['redirect'] = $course->get_item_link( $item_id_next );
				} else {
					$response['redirect'] = $item->get_permalink();
				}

				if ( ! is_wp_error( $result ) ) {
					if ( $course->get_next_item() ) {
						$next                 = $course->get_next_item();
						$response['redirect'] = $course->get_item_link( $next );
					}

					$message_data = [
						'status'  => 'success',
						'content' => sprintf( __( 'Congrats! You have completed "%s".', 'learnpress' ), $item->get_title() ),
					];
					learn_press_set_message( $message_data );
					$response['result'] = 'success';
				} else {
					throw new Exception( $result->get_error_message() );
				}

				$response = apply_filters( 'learn-press/user-completed-lesson-result', $response, $lesson_id, $course_id, $user->get_id() );
			} catch ( Throwable $e ) {
				$message_data = [
					'status'  => 'error',
					'content' => $e->getMessage(),
				];
				learn_press_set_message( $message_data );
			}

			//learn_press_maybe_send_json( $response );

			if ( ! empty( $response['redirect'] ) ) {
				wp_redirect( $response['redirect'] );
				exit();
			}
		}*/
	}
}

LP_AJAX::init();
