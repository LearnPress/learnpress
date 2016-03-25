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
				'load_quiz_question'   => true,
				'load_prev_question'   => false,
				'load_next_question'   => false,
				'save_question_answer' => false,
				'finish_quiz'          => true,
				'retake_quiz'          => true, // anonymous user can retake quiz
				'take_free_course'     => false,
				'load_lesson_content'  => false,
				'load_next_lesson'     => false,
				'load_prev_lesson'     => false,
				'complete_lesson'      => false,
				'finish_course'        => false,
				'join_event'           => false,
				'not_going'            => false,
				//
				'take_course'          => true,
				'start_quiz'           => true,
				'fetch_question'       => true
			);

			foreach ( $ajaxEvents as $ajax_event => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				}
			}

			LP_Request_Handler::register( 'lp-ajax', array( __CLASS__, 'do_ajax' ) );
		}

		static function do_ajax( $var ) {
			if ( !defined( 'DOING_AJAX' ) ) {
				define( 'DOING_AJAX', true );
			}
			LP_Gateways::instance()->get_available_payment_gateways();
			$result = false;
			/*switch ( $var ) {
				case 'checkout':

					break;
				case 'enroll-course':


					break;
				case 'checkout-login':

					break;
				case 'add-to-cart':

					break;
				case 'finish-course'
			}*/
			$method   = preg_replace( '/[-]+/', '_', $var );
			$callback = array( __CLASS__, '_request_' . $method );
			if ( is_callable( $callback ) ) {
				$result = call_user_func( $callback );
			} elseif ( has_action( 'learn_press_ajax_handler_' . $method ) ) {
				do_action( 'learn_press_ajax_handler_' . $method );
				return;
			}
			learn_press_send_json( $result );
		}

		static function _request_checkout() {
			return LP()->checkout->process_checkout();
		}

		static function _request_enroll_course() {
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

		static function _request_checkout_login() {
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

		static function _request_add_to_cart() {
			return LP()->cart->add_to_cart( learn_press_get_request( 'add-course-to-cart' ) );
		}

		static function _request_finish_course() {
			$user_id   = get_current_user_id();
			$course_id = absint( learn_press_get_request( 'id' ) );

			$user   = learn_press_get_user( $user_id );
			$course = LP_Course::get_course( $course_id );
			if ( !$user->id || !$course || !wp_verify_nonce( learn_press_get_request( 'nonce' ), 'learn-press-finish-course-' . $course->id ) ) {
				wp_die( __( 'Access denied!', 'learnpress' ) );
			}

			$response = $user->finish_course( $course_id );

			learn_press_send_json( $response );

			$assessment = get_post_meta( $course_id, '_lpr_course_final', true );
			$pass       = floatval( get_post_meta( $course_id, '_lpr_course_condition', true ) );
			if ( $assessment == 'yes' ) {

				$final_quiz   = lpr_get_final_quiz( $course_id );
				$final_result = learn_press_get_quiz_result( $user_id, $final_quiz );// lpr_get_quiz_result( $final_quiz );
				if ( !empty( $final_result ) && !empty( $final_result['mark_percent'] ) && ( $final_result['mark_percent'] * 100 >= $pass ) ) {
					$finish = true;
				}
			} else {
				$progress = lpr_course_evaluation( $course_id );
				if ( $progress >= $pass ) {
					$finish = true;
				}
			}


			if ( $finish ) {
				learn_press_finish_course( $course_id, $user_id );

				$json['message'] = __( 'Congratulation ! You have finished this course', 'learnpress' );
			} else {
				$json['finish']  = false;
				$json['message'] = __( 'Sorry! You can not finish this course now', 'learnpress' );
			}
			wp_send_json( $json );
			die;
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
			$quiz = LP_Quiz::get_quiz( $quiz_id );

			do_action( 'learn_press_load_quiz_question', $question_id, $quiz_id, $user_id );
			// save question if find it
			//self::save_question_if_needed();
			//self::update_time_remaining();
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

				ob_start();

				if ( $progress = $user->get_quiz_progress( $quiz->id ) ) {
					learn_press_update_user_quiz_meta( $progress->history_id, 'current_question', $question_id );
				}
				$question_answers = $user->get_question_answers( $quiz->id, $question_id );
				$question->render( array( 'answered' => $question_answers ) );
				$content = ob_get_clean();
				learn_press_send_json(
					apply_filters( 'learn_press_load_quiz_question_result_data', array(
							'result'    => 'success',
							'content'   => $content,
							'permalink' => learn_press_get_user_question_url( $quiz_id, $question_id )
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
		function load_next_lesson() {

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
		function load_prev_lesson() {

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
		 * Take this free course
		 */
		public static function take_free_course() {
			_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.0' );
			$course_id     = $_POST['course_id'];
			$check_payment = get_post_meta( $course_id, '_lpr_course_payment', true );

			//check user is logged in
			if ( !is_user_logged_in() ) {
				echo 'not logged in';
				die;
			}

			$user_id = get_current_user_id();

			// check prerequisite
			$prerequisite = get_post_meta( $course_id, '_lpr_course_prerequisite', true );
			if ( $prerequisite ) {
				$course_completed = get_user_meta( $user_id, '_lpr_course_completed', true );
				foreach ( $prerequisite as $prerequi ) {
					if ( $prerequi && $course_completed ) {
						if ( !array_key_exists( $prerequi, $course_completed ) ) {
							echo 'not prerequisite';
							die;
						}
					}
				}
			}

			//check payment
			if ( $check_payment == 'free' ) {

				$course_taken = get_user_meta( $user_id, '_lpr_user_course', true );

				$course_user = get_post_meta( $course_id, '_lpr_course_user', true );

				if ( !$course_taken ) {
					$course_taken = array();
				}
				if ( !$course_user ) {
					$course_user = array();
				}

				if ( !in_array( $course_id, $course_taken ) ) {
					array_push( $course_taken, $course_id );
					do_action( 'learn_press_after_take_course', $user_id, $course_id );
				}
				if ( !in_array( $user_id, $course_user ) ) {
					array_push( $course_user, $user_id );
				}
				update_user_meta( $user_id, '_lpr_user_course', $course_taken );
				update_post_meta( $course_id, '_lpr_course_user', $course_user );

				$start_date                         = time();
				$user_course_start_date[$course_id] = $start_date;
				update_user_meta( $user_id, '_lpr_user_course_start_time', $user_course_start_date );

				// email notification
				$student = get_userdata( $user_id );
				$mail_to = $student->user_email;

				learn_press_send_mail(
					$mail_to,
					'enrolled_course',
					apply_filters( 'learn_press_var_enrolled_course', array(
						'user_name'   => $student->display_name,
						'course_name' => get_the_title( $course_id )
					) )
				);

			}
			if ( file_exists( $template = lpr_locate_template_part( 'course', 'main' ) ) ) {
				require_once( $template );
			}
			die;
		}

		/**
		 * Complete lesson
		 */
		public static function complete_lesson() {
			$nonce   = learn_press_get_request( 'nonce' );
			$item_id = learn_press_get_request( 'id' );
			$post    = get_post( $item_id );

			// security check
			if ( !$post || ( $post && !wp_verify_nonce( $nonce, sprintf( 'learn-press-complete-%s-%d', $post->post_type, $post->ID ) ) ) ) {
				throw new Exception( __( 'Error ', 'learnpress' ) );
			}
			$user = learn_press_get_current_user();

			$response = array(
				'result' => 'error'
			);
			if ( $result = $user->complete_lesson( $item_id ) ) {
				ob_start();
				learn_press_display_message( __( 'Congratulations! You have completed this lesson.', 'learnpress' ) );
				$response['message'] = ob_get_clean();
				$response['result']  = 'success';

				$response['course_result'] = $result * 100;
			}
			learn_press_send_json( $response );
			die();
			global $post;
			$user_id   = get_current_user_id();
			$lesson_id = !empty( $_POST['lesson'] ) ? $_POST['lesson'] : 0;
			if ( !$user_id || !$lesson_id ) {
				wp_die( __( 'Access denied!', 'learnpress' ) );
			}
			$response = array();
			if ( learn_press_mark_lesson_complete( $lesson_id, $user_id ) ) {
				$course_id        = learn_press_get_course_by_lesson( $lesson_id );
				$lessons          = learn_press_get_lessons_in_course( $course_id );
				$lesson_completed = get_user_meta( $user_id, '_lpr_lesson_completed', true );
				$lesson_completed = !empty( $lesson_completed[$course_id] ) ? $lesson_completed[$course_id] : array();

				if ( $lessons ) {
					if ( false !== ( $pos = array_search( $lesson_id, $lessons ) ) ) {
						$loop     = ( $pos == count( $lessons ) - 1 ) ? 0 : $pos + 1;
						$infinite = 0;
						$max      = count( $lessons );

						while ( in_array( $lessons[$loop], $lesson_completed ) && ( $lessons[$loop] != $lesson_id ) ) {
							$loop ++;
							if ( $loop == $max ) $loop = 0;
							if ( $infinite > $max ) break;
						}
						if ( $lessons[$loop] != $lesson_id ) {
							$response['url'] = learn_press_get_course_lesson_permalink( $lessons[$loop], $course_id );
						} else {
							$response['url'] = learn_press_get_course_lesson_permalink( $lesson_id, $course_id );
						}
					}
				}
			}

			learn_press_send_json( $response );
			die;
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

		static function start_quiz() {
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