<?php
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
				// 'upload-user-avatar',
				'checkout:nopriv',
				'complete-lesson',
				'finish-course', // finish_course.
				// 'retake-course', // retake_course.
				'external-link:nopriv',
				// 'save-uploaded-user-avatar',
				// 'load-more-courses',
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

			//add_action( 'wp_ajax_learnpress_upload-user-avatar', array( __CLASS__, 'upload_user_avatar' ) );
		}

		/**
		 * @depecated v4.1.6.4
		 */
		/*public static function load_more_courses() {
			_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '4.1.6.4' );
			$type     = LP_Request::get( 'type' );
			$user_id  = LP_Request::get_int( 'user', 0 );
			$paged    = LP_Request::get_int( 'current_page', 1 );
			$template = LP_Request::get( 'template' );

			$user          = learn_press_get_user( $user_id );
			$template_args = array();

			if ( in_array( $type, array( 'featured', 'latest' ) ) ) {
				$query_args = array(
					'paginate' => true,
					'return'   => 'ids',
					'author'   => $user->get_id(),
					'paged'    => $paged,
				);

				if ( 'featured' === $type ) {
					$query_args['featured'] = 1;
				}

				$query         = new LP_Course_Query( $query_args );
				$template_args = (array) $query->get_courses();
				$template      = "profile/dashboard/{$type}-courses";

			} else {
				$profile       = LP_Profile::instance( $user_id );
				$filter_status = LP_Request::get_string( 'filter-status' );
				$query         = $profile->query_courses( 'purchased', array( 'status' => $filter_status ) );
			}

			learn_press_get_template( $template, $template_args );

			wp_die();
		}*/

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
			LP()->checkout()->process_checkout_handler();
		}

		public static function request_become_a_teacher() {
			LP_Forms_Handler::process_become_teacher();
		}

		public static function recover_order() {
			if ( ! LP_Request::verify_nonce( 'recover-order' ) ) {
				return;
			}

			$factory   = new LP_Order_CURD();
			$user_id   = get_current_user_id();
			$order_key = LP_Request::get_string( 'order-key' );
			$order     = $factory->recover( $order_key, $user_id );
			$result    = array( 'result' => 'success' );

			if ( is_wp_error( $order ) ) {
				$result['message'] = $order->get_error_message();
				$result['result']  = 'error';
			} else {
				$result['message']  = sprintf(
					__( 'The order %s has been successfully recovered.', 'learnpress' ),
					$order_key
				);
				$result['redirect'] = $order->get_view_order_url();
			}

			$result = apply_filters( 'learn-press/order/recover-result', $result, $order_key, $user_id );

			learn_press_maybe_send_json( $result );

			if ( ! empty( $result['message'] ) ) {
				learn_press_add_message( $result['message'] );
			}

			if ( ! empty( $result['redirect'] ) ) {
				wp_redirect( $result['redirect'] );
				exit();
			}
		}

		public static function checkout_user_email_exists() {
			$email    = LP_Request::get_email( 'email' );
			$response = array(
				'exists' => 0,
			);

			if ( email_exists( $email ) ) {
				$response['exists'] = $email;
				$output             = '<div class="lp-guest-checkout-output">' . __(
					'Your email is already exists. Continue with this email?',
					'learnpress'
				) . '</div>';
			} else {
				$output = '<label class="lp-guest-checkout-output">
					<input type="checkbox" name="checkout-email-option" value="new-account">
				' . __(
					'Create new account with this email? Account information will be sent to this email.',
					'learnpress'
				) . '
				</label>';
			}

			$response['output'] = apply_filters( 'learnpress/guest_checkout_email_exist_output', $output, $email );

			learn_press_maybe_send_json( $response );
		}

		/*public static function upload_user_avatar() {
			$file       = $_FILES['lp-upload-avatar'];
			$upload_dir = learn_press_user_profile_picture_upload_dir();

			add_filter( 'upload_dir', array( __CLASS__, '_user_avatar_upload_dir' ), 10000 );

			$result = wp_handle_upload(
				$file,
				array(
					'test_form' => false,
				)
			);

			remove_filter( 'upload_dir', array( __CLASS__, '_user_avatar_upload_dir' ), 10000 );

			if ( is_array( $result ) ) {
				$result['name'] = $upload_dir['subdir'] . '/' . basename( $result['file'] );
				unset( $result['file'] );
			} else {
				$result = array(
					'error' => __( 'Profile picture upload failed', 'learnpress' ),
				);
			}

			learn_press_send_json( $result );
		}*/

		public static function _user_avatar_upload_dir( $dir ) {
			$dir = learn_press_user_profile_picture_upload_dir();

			return $dir;
		}

		/**
		 * Request finish course
		 *
		 * TODO: should move this function to api - tungnx
		 */
		public static function finish_course() {
			$nonce     = LP_Request::get_string( 'finish-course-nonce' );
			$course_id = LP_Request::get_int( 'course-id' );
			$course    = learn_press_get_course( $course_id );
			$user      = learn_press_get_current_user();

			$nonce_action = sprintf( 'finish-course-%d-%d', $course_id, $user->get_id() );

			if ( ! $user->get_id() || ! $course || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
				wp_die( __( 'Access denied!', 'learnpress' ) );
			}

			$finished    = $user->finish_course( $course_id );
			$lp_redirect = LP_Settings::get_option( 'course_finish_redirect' );
			$redirect    = ! empty( $lp_redirect ) ? $lp_redirect : get_the_permalink( $course_id );

			$response = array(
				'redirect' => apply_filters(
					'learn-press/finish-course-redirect',
					$redirect,
					$course_id
				),
			);

			if ( $finished ) {
				learn_press_update_user_item_meta( $finished, 'finishing_type', 'click' );
				learn_press_add_message( sprintf( __( 'You have finished this course "%s"', 'learnpress' ), $course->get_title() ) );
				$response['result'] = 'success';
			} else {
				learn_press_add_message( __( 'Error! You cannot finish this course. Please contact your administrator for more information.', 'learnpress' ) );
				$response['result'] = 'error';
			}

			learn_press_maybe_send_json( $response );

			if ( ! empty( $response['redirect'] ) ) {
				wp_redirect( $response['redirect'] );
				exit();
			}
		}

		/**
		 * Complete lesson
		 */
		public static function complete_lesson() {
			$nonce     = LP_Request::get_string( 'complete-lesson-nonce' );
			$item_id   = LP_Request::get_int( 'id' );
			$course_id = LP_Request::get_int( 'course_id' );

			$post     = get_post( $item_id );
			$user     = learn_press_get_current_user();
			$course   = learn_press_get_course( $course_id );
			$response = array(
				'result'   => 'success',
				'redirect' => $course->get_item_link( $item_id ),
			);

			$item         = $course->get_item( $item_id );
			$nonce_action = $item->get_nonce_action( 'complete', $course_id, $user->get_id() );
			try {
				// security check
				if ( ! $post || ( $post && ! wp_verify_nonce( $nonce, 'lesson-complete' ) ) ) {
					throw new Exception( __( 'Error! Invalid lesson or failed security check.', 'learnpress' ), 8000 );
				}

				$result = $user->complete_lesson( $item_id );

				if ( ! is_wp_error( $result ) ) {
					if ( $course->get_next_item() ) {
						$next                 = $course->get_next_item();
						$response['redirect'] = $course->get_item_link( $next );
					}

					learn_press_add_message( sprintf( __( 'Congrats! You have completed "%s".', 'learnpress' ), $item->get_title() ) );
				} else {
					learn_press_add_message( $result->get_error_message(), 'error' );
				}

				$response = apply_filters( 'learn-press/user-completed-lesson-result', $response, $item_id, $course_id, $user->get_id() );
			} catch ( Exception $ex ) {
				learn_press_add_message( $ex->getMessage(), 'error' );
			}

			if ( learn_press_message_count( 'error' ) ) {
				$response['result'] = 'error';
			}

			learn_press_maybe_send_json( $response );

			if ( ! empty( $response['redirect'] ) ) {
				// wp_cache_flush();
				wp_redirect( $response['redirect'] );
				exit();
			}
		}

		/**
		 * Retake course action
		 *
		 * @TODO move this function to API
		 */
		/*
		public static function retake_course() {
			$security  = LP_Request::get_string( 'retake-course-nonce' );
			$course_id = LP_Request::get_int( 'retake-course' );
			$user      = learn_press_get_current_user();
			$course    = learn_press_get_course( $course_id );
			$response  = array(
				'result' => 'error',
			);

			$security_action = sprintf( 'retake-course-%d-%d', $course->get_id(), $user->get_id() );
			// security check
			if ( ! wp_verify_nonce( $security, $security_action ) ) {
				learn_press_add_message( __( 'Error! Invalid course or failed security check.', 'learnpress' ),
					'error' );
			} else {
				if ( $user->can_retake_course( $course_id ) ) {
					$result = $user->retry_course( $course_id );

					if ( ! $result ) {
						learn_press_add_message( __( 'Error!', 'learnpress' ), 'error' );
					} else {
						learn_press_add_message( sprintf( __( 'You have retaken the course "%s"', 'learnpress' ),
							$course->get_title() ) );
						$response['result'] = 'success';
					}
				} else {
					learn_press_add_message( __( 'Error! You can not retake the course', 'learnpress' ), 'error' );
				}
			}

			if ( learn_press_message_count( 'error' ) == 0 ) {
				$item = $course->get_item_at( 0 );

				if ( $item ) {
					$redirect = $course->get_item_link( $item );
				} else {
					$redirect = $course->get_permalink();
				}
				$response['redirect'] = apply_filters( 'learn-press/user-retake-course-redirect', $redirect );
				$response             = apply_filters( 'learn-press/user-retaken-course-result', $response, $course_id,
					$user->get_id() );
			} else {
				$response['redirect'] = $course->get_permalink();
				$response             = apply_filters( 'learn-press/user-retake-course-failed-result', $response,
					$course_id, $user->get_id() );
			}

			learn_press_maybe_send_json( $response );

			if ( ! empty( $response['redirect'] ) ) {
				wp_redirect( $response['redirect'] );
				exit();
			}
		}*/
	}
}

LP_AJAX::init();
