<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_AJAX' ) ) {
	/**
	 * Class LP_AJAX
	 */
	class LP_AJAX {

		public static function init() {

			// learnpress_ajax_event => nopriv
			$ajaxEvents = array(
				//'list_quiz'            => false,
				'load_quiz_question'  => true,
				'load_prev_question'  => false,
				'load_next_question'  => false,
				//'save_question_answer' => false,
				'finish_quiz'         => true,
				'retake_quiz'         => true, // anonymous user can retake quiz
				'take_free_course'    => false,
				'load_lesson_content' => false,
				'load_next_lesson'    => false,
				'load_prev_lesson'    => false,
				'complete_lesson'     => false,
				'finish_course'       => false,
				'not_going'           => false,
				//
				'take_course'         => true,
				'start_quiz'          => true,
				'fetch_question'      => true
			);

			foreach ( $ajaxEvents as $ajax_event => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				}
			}

			LP_Request_Handler::register( 'lp-ajax', array( __CLASS__, 'do_ajax' ) );
		}

		public static function do_ajax( $var ) {
			if ( !defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}
			LP_Gateways::instance()->get_available_payment_gateways();
			$result   = false;
			$method   = preg_replace( '/[-]+/', '_', $var );
			$callback = array( __CLASS__, '_request_' . $method );
			if ( is_callable( $callback ) ) {
				$result = call_user_func( $callback );
			} elseif ( has_action( 'learn_press_ajax_handler_' . $var ) ) {
				do_action( 'learn_press_ajax_handler_' . $var );
				return;
			}
			learn_press_send_json( $result );
		}

		public static function _request_become_a_teacher() {
			$response = learn_press_process_become_a_teacher_form(
				array(
					'name'  => learn_press_get_request( 'bat_name' ),
					'email' => learn_press_get_request( 'bat_email' ),
					'phone' => learn_press_get_request( 'bat_phone' )
				)
			);
			learn_press_send_json( $response );
		}

		public static function _request_checkout() {
			return LP()->checkout->process_checkout();
		}

		public static function _request_enroll_course() {
			$course_id = learn_press_get_request( 'enroll-course' );
			if ( !$course_id ) {
				throw new Exception( __( 'Invalid course', 'learnpress' ) );
			}
			$insert_id = LP()->user->enroll( $course_id );

			$response = array(
				'result'   => 'fail',
				'redirect' => apply_filters( 'learn_press_enroll_course_failure_redirect_url', get_the_permalink( $course_id ) )
			);
			if ( $insert_id ) {
				$response['result']   = 'success';
				$response['redirect'] = apply_filters( 'learn_press_enrolled_course_redirect_url', get_the_permalink( $course_id ) );
				$message              = apply_filters( 'learn_press_enrolled_course_message', sprintf( __( 'Congrats! You have enrolled <strong>%s</strong>', 'learnpress' ), get_the_title( $course_id ) ), $course_id, LP()->user->id );
				learn_press_add_notice( $message );
			} else {
				$message = apply_filters( 'learn_press_enroll_course_failed_message', sprintf( __( 'Sorry! The course <strong>%s</strong> you want to enroll has failed! Please contact site\'s administrator for more information.', 'learnpress' ), get_the_title( $course_id ) ), $course_id, LP()->user->id );
				learn_press_add_notice( $message, 'error' );
			}

			if ( is_ajax() ) {
				learn_press_send_json( $response );
			}

			if ( $response['redirect'] ) {
				wp_redirect( $response['redirect'] );
				exit();
			}
			return false;
		}

		public static function _request_checkout_login() {
			$result = array(
				'result' => 'success'
			);
			ob_start();
			if ( empty( $_REQUEST['user_login'] ) ) {
				$result['result'] = 'fail';
				learn_press_add_notice( __( 'Please enter username', 'learnpress' ), 'error' );
			}
			if ( empty( $_REQUEST['user_password'] ) ) {
				$result['result'] = 'fail';
				learn_press_add_notice( __( 'Please enter password', 'learnpress' ), 'error' );
			}
			if ( $result['result'] == 'success' ) {
				$creds                  = array();
				$creds['user_login']    = $_REQUEST['user_login'];
				$creds['user_password'] = $_REQUEST['user_password'];
				$creds['remember']      = true;
				$user                   = wp_signon( $creds, false );
				if ( is_wp_error( $user ) ) {
					$result['result'] = 'fail';
					learn_press_add_notice( $user->get_error_message(), 'error' );
				} else {
					$result['redirect'] = learn_press_get_page_link( 'checkout' );
				}
			}
			learn_press_print_notices();
			$messages = ob_get_clean();
			if ( $result['result'] == 'fail' ) {
				$result['messages'] = $messages;
			}
			return $result;
		}

		public static function _request_login() {
			$data_str = learn_press_get_request( 'data' );
			$data     = null;
			if ( $data_str ) {
				parse_str( $data_str, $data );
			}

			$user   = wp_signon(
				array(
					'user_login'    => $data['log'],
					'user_password' => $data['pwd'],
					'remember'      => isset( $data['rememberme'] ) ? $data['rememberme'] : false
				),
				is_ssl()
			);
			$error  = is_wp_error( $user );
			$return = array(
				'result'   => $error ? 'error' : 'success',
				'redirect' => !$error && !empty( $_REQUEST['return'] ) ? $_REQUEST['return'] : ''
			);
			if ( $error ) {
				$return['message'] = array(
					'title'   => __( 'Login failed', 'learnpress' ),
					'message' => $user->get_error_message() ? $user->get_error_message() : __( 'Please enter your username and/or password', 'learnpress' )
				);
			} else {
				wp_set_current_user( $user->ID );
				$next = learn_press_get_request( 'next' );
				if ( $next == 'enroll-course' ) {
					$user     = new LP_User( $user->ID );
					$checkout = false;
					if ( $cart_items = LP()->cart->get_items() ) {
						foreach ( $cart_items as $item ) {
							if ( $user->has_enrolled_course( $item['item_id'] ) ) {
								$checkout = $item['item_id'];
							} elseif ( $user->has_purchased_course( $item['item_id'] ) ) {
								$checkout = $item['item_id'];
								$user->enroll( $item['item_id'] );
							}
							if ( $checkout ) {
								LP()->cart->remove_item( $checkout );
								$return['redirect'] = get_the_permalink( $checkout );
								learn_press_add_notice( sprintf( __( 'Welcome back, %s. You\'ve already enrolled this course', 'learnpress' ), $user->user->display_name ) );
								break;
							}
						}
					}
					if ( $checkout === false ) {
						add_filter( 'learn_press_checkout_success_result', '_learn_press_checkout_auto_enroll_free_course', 10, 2 );
						$checkout = LP()->checkout()->process_checkout();
					} else {
					}
				}
			}
			learn_press_send_json( $return );
		}

		public static function _request_add_to_cart() {
			LP()->cart->add_to_cart( learn_press_get_request( 'add-course-to-cart' ) );

		}

		public static function _request_finish_course() {
			$nonce     = learn_press_get_request( 'nonce' );
			$course_id = absint( learn_press_get_request( 'id' ) );
			$user      = learn_press_get_current_user();

			$course = LP_Course::get_course( $course_id );

			$nonce_action = sprintf( 'learn-press-finish-course-%d-%d', $course_id, $user->id );
			if ( !$user->id || !$course || !wp_verify_nonce( $nonce, $nonce_action ) ) {
				wp_die( __( 'Access denied!', 'learnpress' ) );
			}

			$response = $user->finish_course( $course_id );

			if ( $response['result'] == 'success' ) {
				$message              = __( 'Congrats! You have finished this course', 'learnpress' );
				$response['redirect'] = get_the_permalink( $course_id );
			} else {
				$message = __( 'Error! You cannot finish this course. Please contact your administrator for more information.', 'learnpress' );
			}

			$response['message'] = array( 'title' => __( 'Finish course', 'learnpress' ), 'message' => $message );
			learn_press_send_json( $response );
		}


		/**
		 * die();
		 * Student take course
		 * @return void
		 */
		public static function take_course() {
			$payment_method = !empty( $_POST['payment_method'] ) ? $_POST['payment_method'] : '';
			$course_id      = !empty( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : false;
			do_action( 'learn_press_take_course', $course_id, $payment_method );
		}

		private static function update_time_remaining() {
			$time_remaining = learn_press_get_request( 'time_remaining' );
			$quiz_id        = learn_press_get_request( 'quiz_id' );
			$user_id        = learn_press_get_request( 'user_id' );
			if ( $time_remaining ) {
				$quiz_time_remaining = learn_press_get_quiz_time_remaining( $user_id, $quiz_id );
				if ( $time_remaining != $quiz_time_remaining ) {
					$quiz_time     = (array) get_user_meta( $user_id, '_lpr_quiz_start_time', true );
					$quiz_duration = get_post_meta( $quiz_id, '_lpr_duration', true );


					if ( !empty( $quiz_time[$quiz_id] ) ) {
						echo $quiz_time[$quiz_id], ',';
						$quiz_time[$quiz_id] = current_time( 'timestamp' ) - $time_remaining;
						echo $quiz_time[$quiz_id], ',';
						update_user_meta( $user_id, '_lpr_quiz_start_time', $quiz_time );
					}
				}
			}
		}

		/**
		 * Load quiz question
		 */
		public static function load_quiz_question() {
			$quiz_id     = !empty( $_REQUEST['quiz_id'] ) ? absint( $_REQUEST['quiz_id'] ) : 0;
			$question_id = !empty( $_REQUEST['question_id'] ) ? absint( $_REQUEST['question_id'] ) : 0;
			$user_id     = !empty( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;
			global $quiz;
			$quiz      = LP_Quiz::get_quiz( $quiz_id );
			LP()->quiz = $quiz;



			do_action( 'learn_press_load_quiz_question', $question_id, $quiz_id, $user_id );

			$user = learn_press_get_current_user();
			if ( $user->id != $user_id ) {
				learn_press_send_json(
					array(
						'result'  => 'error',
						'message' => __( 'Load question error. Try again!', 'learnpress' )
					)
				);
			}
			if ( !$quiz_id || !$question_id ) {
				learn_press_send_json(
					array(
						'result'  => 'error',
						'message' => __( 'Something is wrong. Try again!', 'learnpress' )
					)
				);
			}
			if ( $question = LP_Question_Factory::get_question( $question_id ) ) {
				$quiz->current_question = $question;

				ob_start();
				if ( $progress = $user->get_quiz_progress( $quiz->id ) ) {
					learn_press_update_user_quiz_meta( $progress->history_id, 'current_question', $question_id );
				}
				$question_answers = $user->get_question_answers( $quiz->id, $question_id );
				$question->render( array( 'answered' => $question_answers ) );
				/*
								if ( $hint = get_post_meta( $question->id, '_lp_explanation', true ) ) {
									///echo '<div id="learn-press-question-hint-' . $question->id . '" class="question-hint hide-if-js">' . $hint . '</div>';
								}
				*/
				$content = ob_get_clean();
				learn_press_send_json(
					apply_filters( 'learn_press_load_quiz_question_result_data', array(
							'result'    => 'success',
							'permalink' => learn_press_get_user_question_url( $quiz_id, $question_id ),
							'question'  => array(
								'content' => $content
							)
						)
					)
				);

			}
		}


		/**
		 * Load previous question
		 */
		public static function load_prev_question() {

			$prev_question_id = $_POST['prev_question_id'];
			$question_id      = $_POST['question_id'];
			$question_answer  = $_POST['question_answer'];
			$quiz_id          = $_POST['quiz_id'];

			lpr_save_question_answer( $quiz_id, $question_id, $question_answer );

			do_action( 'lpr_load_question', $prev_question_id );

			die;
		}

		/**
		 *   Load next question
		 */
		public static function load_next_question() {

			$next_question_id = $_POST['next_question_id'];
			$quiz_id          = $_POST['quiz_id'];
			$question_id      = $_POST['question_id'];
			$question_answer  = $_POST['question_answer'];

			lpr_save_question_answer( $quiz_id, $question_id, $question_answer );

			do_action( 'lpr_load_question', $next_question_id );

			die;
		}

		/**
		 * Finish quiz
		 */
		public static function finish_quiz() {
			$user    = learn_press_get_current_user();
			$quiz_id = learn_press_get_request( 'quiz_id' );
			// save current answer as if user may change
			//self::save_question_if_needed();
			$user->finish_quiz( $quiz_id );

			$response = array(
				'redirect' => get_the_permalink( $quiz_id )
			);
			learn_press_send_json( $response );
		}

		/**
		 *  Retake a quiz
		 */
		public static function retake_quiz() {
			die( __FUNCTION__ );
			// verify nonce
			if ( !wp_verify_nonce( learn_press_get_request( 'nonce' ), 'retake-quiz' ) ) {
				learn_press_send_json(
					array(
						'result'  => 'fail',
						'message' => __( 'Something went wrong. Please try again!', 'learnpress' )
					)
				);
			}

			$quiz_id = learn_press_get_request( 'quiz_id' );
			$user    = learn_press_get_current_user();

			$response = $user->retake_quiz( $quiz_id );
			learn_press_send_json( $response );
		}

		/**
		 * Load lesson content
		 */
		public static function load_lesson_content() {

			learn_press_debug( $_REQUEST );
			global $post;

			$lesson_id = $_POST['lesson_id'];
			$title     = get_the_title( $lesson_id );
			$post      = get_post( $lesson_id );
			$content   = $post->post_content;
			$content   = apply_filters( 'the_content', $content );
			printf(
				'<h3>%s</h3>
				%s
				<button class="complete-lesson-button">Complete Lesson</button>',
				$title,
				$content
			);
			die;
		}

		/**
		 * Load next lesson
		 */
		public function load_next_lesson() {

			$lesson_id = $_POST['lesson_id'];
			$html      = '';
			$html .= '<h2>' . get_the_title( $lesson_id ) . '</h2>';
			$html .= '<p>' . get_post_meta( $lesson_id, '_lpr_lesson_desc', true ) . '</p>';
			$lesson         = get_post( $lesson_id );
			$lesson_content = $lesson->post_content;
			$lesson_content = apply_filters( 'the_content', $lesson_content );
			$html .= '<p>' . $lesson_content . '</p>';
			echo $html;
		}

		/**
		 * Load previous lesson
		 */
		public function load_prev_lesson() {

			$lesson_id = $_POST['lesson_id'];
			$html      = '';
			$html .= '<h2>' . get_the_title( $lesson_id ) . '</h2>';
			$html .= '<p>' . get_post_meta( $lesson_id, '_lpr_lesson_desc', true ) . '</p>';
			$lesson         = get_post( $lesson_id );
			$lesson_content = $lesson->post_content;
			$lesson_content = apply_filters( 'the_content', $lesson_content );
			$html .= '<p>' . $lesson_content . '</p>';
			echo $html;
		}

		/**
		 * Complete lesson
		 */
		public static function complete_lesson() {
			$nonce        = learn_press_get_request( 'nonce' );
			$item_id      = learn_press_get_request( 'id' );
			$course_id    = learn_press_get_request( 'course_id' );
			$post         = get_post( $item_id );
			$user         = learn_press_get_current_user();
			$course       = LP_Course::get_course( $course_id );
			$response     = array(
				'result' => 'success'
			);
			$nonce_action = sprintf( 'learn-press-complete-%s-%d-%d-%d', $post->post_type, $post->ID, $course->id, $user->id );
			// security check
			if ( !$post || ( $post && !wp_verify_nonce( $nonce, $nonce_action ) ) ) {
				$response['result']  = 'error';
				$response['message'] = __( 'Error! Invalid lesson or security checked failure', 'learnpress' );
			}

			if ( $response['result'] == 'success' ) {
				$result = $user->complete_lesson( $item_id );
				if ( !is_wp_error( $result ) ) {
					$can_finish                = $user->can_finish_course( $course_id );
					$response['button_text']   = '<span class="dashicons dashicons-yes"></span>' . __( 'Completed', 'learnpress' );
					$response['course_result'] = round( $result * 100, 0 );
					$response['can_finish']    = $can_finish;
					$response['next_item']     = $course->get_next_item( $item_id );

					ob_start();
					if ( $can_finish ) {
						learn_press_display_message( __( 'Congratulations! You have completed this lesson and you can finish course.', 'learnpress' ) );
					} else {
						learn_press_display_message( __( 'Congratulations! You have completed this lesson.', 'learnpress' ) );
					}
					$response['message'] = ob_get_clean();
				} else {
					ob_start();
					learn_press_display_message( $result->get_error_message() );
					$response['message'] = ob_get_clean();
					$response['result']  = 'fail';
				}
			}
			learn_press_send_json( $response );
		}

		/**
		 * Retake course action
		 */
		public static function retake_course() {
			$course_id = !empty( $_REQUEST['course_id'] ) ? absint( $_REQUEST['course_id'] ) : 0;
			$user_id   = !empty( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : get_current_user_id();

			if ( !$user_id || !$course_id ) {
				wp_die( __( 'Error', 'learnpress' ) );
			}

			////
			// Of course, user only retake course if he has finished
			if ( !learn_press_user_has_finished_course( $course_id, $user_id ) ) {
				wp_die( __( 'Error', 'learnpress' ) );
			}

			// reset course start time
			$course_time = get_user_meta( $user_id, '_lpr_course_time', true );
			if ( $course_time && !empty( $course_time[$course_id] ) ) {
				$course_time[$course_id] = array(
					'start' => current_time( 'timestamp' ),
					'end'   => null
				);
				update_user_meta( $user_id, '_lpr_course_time', $course_time );
			}

			// reset course user finished
			$course_finished = get_user_meta( $user_id, '_lpr_course_finished', true );
			if ( $course_finished && in_array( $course_id, $course_finished ) ) {
				if ( false !== ( $position = array_search( $course_id, $course_finished ) ) ) {
					unset( $course_finished[$position] );
					update_user_meta( $user_id, '_lpr_course_finished', $course_finished );
				}
			}
			$user_finished = get_post_meta( $course_id, '_lpr_user_finished', true );
			if ( $user_finished ) {
				if ( false !== ( $position = array_search( $user_id, $user_finished ) ) ) {
					unset( $user_finished[$position] );
					update_post_meta( $course_id, '_lpr_user_finished', $user_finished );
				}
			}

			// reset the lessons user has completed
			$lessons = get_user_meta( $user_id, '_lpr_lesson_completed', true );
			if ( $lessons && isset( $lessons[$course_id] ) ) {
				unset( $lessons[$course_id] );
				update_user_meta( $user_id, '_lpr_lesson_completed', $lessons );
			}

			$quizzes = learn_press_get_quizzes( $course_id );

			// remove all quizzes in the course which user has taken
			if ( $quizzes ) {
				$quiz_start_time       = get_user_meta( $user_id, '_lpr_quiz_start_time', true );
				$quiz_question         = get_user_meta( $user_id, '_lpr_quiz_questions', true );
				$quiz_current_question = get_user_meta( $user_id, '_lpr_quiz_current_question', true );
				$quiz_question_answer  = get_user_meta( $user_id, '_lpr_quiz_question_answer', true );
				$quiz_completed        = get_user_meta( $user_id, '_lpr_quiz_completed', true );

				foreach ( $quizzes as $quiz_id ) {
					delete_user_meta( $user_id, '_lpr_quiz_taken', $quiz_id );
					if ( isset( $quiz_start_time[$quiz_id] ) ) unset( $quiz_start_time[$quiz_id] );
					if ( isset( $quiz_question[$quiz_id] ) ) unset( $quiz_question[$quiz_id] );
					if ( isset( $quiz_current_question[$quiz_id] ) ) unset( $quiz_current_question[$quiz_id] );
					if ( isset( $quiz_question_answer[$quiz_id] ) ) unset( $quiz_question_answer[$quiz_id] );
					if ( isset( $quiz_completed[$quiz_id] ) ) unset( $quiz_completed[$quiz_id] );
				}

				update_user_meta( $user_id, '_lpr_quiz_start_time', $quiz_start_time );
				update_user_meta( $user_id, '_lpr_quiz_questions', $quiz_question );
				update_user_meta( $user_id, '_lpr_quiz_current_question', $quiz_current_question );
				update_user_meta( $user_id, '_lpr_quiz_question_answer', $quiz_question_answer );
				update_user_meta( $user_id, '_lpr_quiz_completed', $quiz_completed );
			}

			update_user_meta( $user_id, '_lpr_course_taken', $course_id );
			wp_send_json(
				array(
					'redirect' => get_permalink( $course_id )
				)
			);
			die();
		}

		public static function start_quiz() {
			$quiz_id = !empty( $_REQUEST['quiz_id'] ) ? absint( $_REQUEST['quiz_id'] ) : 0;
			if ( !$quiz_id ) {
				learn_press_send_json(
					array(
						'result'  => 'error',
						'message' => __( 'The quiz ID is empty', 'learnpress' )
					)
				);
			}
			global $quiz;

			$quiz = LP_Quiz::get_quiz( $quiz_id );

			if ( !$quiz->id || $quiz->id != $quiz_id ) {
				learn_press_send_json(
					array(
						'result'  => 'error',
						'message' => __( 'Something is wrong! Please try again', 'learnpress' )
					)
				);
			}
			$user = learn_press_get_current_user();
			if ( $quiz->is_require_enrollment() && $user->is( 'guest' ) ) {
				learn_press_send_json(
					array(
						'result'  => 'error',
						'message' => __( 'Please login to do this quiz', 'learnpress' )
					)
				);
			}
			$user->set_quiz( $quiz );

			switch ( strtolower( $user->get_quiz_status() ) ) {
				case 'completed':
					learn_press_send_json(
						array(
							'result'  => 'error',
							'message' => __( 'You have completed this quiz', 'learnpress' ),
							'data'    => $user->get_quiz_result()
						)
					);
					break;
				case 'started':
					learn_press_send_json(
						array(
							'result'  => 'error',
							'message' => __( 'You have started this quiz', 'learnpress' ),
							'data'    => array(
								'status' => $user->get_quiz_status()
							)
						)
					);
					break;
				default:
					$result           = $user->start_quiz();
					$current_question = !empty( $result['current_question'] ) ? $result['current_question'] : $user->get_current_question_id( $quiz_id );
					learn_press_send_json(
						array(
							'result'           => 'success',
							'data'             => $result,
							'question_url'     => learn_press_get_user_question_url( $quiz_id, $current_question ),
							'question_content' => LP_Question_Factory::fetch_question_content( $current_question )
						)
					);
			}
			die();
		}
	}
}
LP_AJAX::init();