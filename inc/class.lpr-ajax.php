<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LPR_AJAX' ) ) {
	/**
	 * Class LPR_AJAX
	 */
	class LPR_AJAX {

		public static function   init () {

			// learnpress_ajax_event => nopriv
			$ajaxEvents = array(
				'list_quiz'            => false,
				'load_quiz_question'   => false,
				'load_prev_question'   => false,
				'load_next_question'   => false,
				'save_question_answer' => false,
				'finish_quiz'          => false,
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
				'take_course'          => true
			);

			foreach ( $ajaxEvents as $ajax_event => $nopriv ) {
				add_action( 'wp_ajax_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_learnpress_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				}
			}
		}

		/**
		 * Student take course
		 * @return void
		 */
		public static function take_course() {
			$payment_method = !empty( $_POST['payment_method'] ) ? $_POST['payment_method'] : '';
			$course_id      = !empty( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : false;
			do_action( 'learn_press_take_course', $course_id, $payment_method );
		}

		/**
		 * List al quizzes
		 */
		public static function list_quiz() {
			if ( file_exists( $template = lpr_locate_template_part( 'single', 'quiz' ) ) ) {
				require_once( $template );
			}
		}

		/**
		 * Load quiz question
		 */
		public static function load_quiz_question() {
			$question_id = $_POST['question_id'];
			do_action( 'lpr_load_question', $question_id );
			die;
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

			$user_id         = get_current_user_id();
			$quiz_id         = $_POST['quiz_id'];
			$question_id     = $_POST['question_id'];
			$question_answer = $_POST['question_answer'];

			// save current answer as if user may change
			lpr_save_question_answer( $quiz_id, $question_id, $question_answer );

			// Show result to frontend
			$quiz_id        = $_POST['quiz_id'];
			$quiz_questions = get_post_meta( $quiz_id, '_lpr_quiz_questions', true );
			$mark           = 0;
			if ( $quiz_questions ) {
				foreach ( $quiz_questions as $question ) {
					$correct_answer = get_post_meta( $question, '_lpr_question_correct_answer', true );
					$question_mark  = get_post_meta( $question, '_lpr_question_mark', true );
					$student_answer = lpr_get_question_answer( $quiz_id, $question_id );

					if ( array_key_exists( $question, $student_answer ) ) {
						if ( $correct_answer == $student_answer ) {
							$mark += $question_mark;
						}
					}
				}
			}

			// add this quiz to list of completed quizzes
			$quiz_completed = get_user_meta( $user_id, '_lpr_quiz_completed', true );
			if ( $quiz_completed ) {
				if ( !isset( $quiz_completed[$quiz_id] ) || !is_array( $quiz_completed[$quiz_id] ) ) {
					$quiz_completed[$quiz_id] = array();
				}
				array_push( $quiz_completed[$quiz_id], $mark );
				update_user_meta( $user_id, '_lpr_quiz_completed', $quiz_completed );
			} else {
				$quiz_completed = array();
				if ( !isset( $quiz_completed[$quiz_id] ) || !is_array( $quiz_completed[$quiz_id] ) ) {
					$quiz_completed[$quiz_id] = array();
				}
				array_push( $quiz_completed[$quiz_id], $mark );
				update_user_meta( $user_id, '_lpr_quiz_completed', $quiz_completed );
			}

			$retake = get_user_meta( $user_id, '_lpr_quiz_retake', true );
			if ( isset( $retake ) && is_array( $retake ) ) {
				$key = array_search( $quiz_id, $retake );
				if ( $key !== false ) {
					unset( $retake[$key] );
					update_user_meta( $user_id, '_lpr_quiz_retake', $retake );
				}
			}
			die;
		}

		/**
		 *  Retake a quiz
		 */
		public static function retake_quiz() {

			$quiz_id = $_POST['quiz_id'];
			$user_id = get_current_user_id();

			$response = array();
			if ( !learn_press_user_can_retake_quiz( $quiz_id, $user_id ) ) {
				$response['message'] = __( 'Sorry! You can not retake this quiz', 'learn_press' );
				$response['error']   = true;
			} else {
				//lpr_reset_quiz_answer($quiz_id);
				learn_press_reset_user_quiz( $user_id, $quiz_id );
				add_user_meta( $user_id, '_lpr_quiz_taken', $quiz_id );
				$response['error'] = false;
			}
			wp_send_json( $response );
			die();
			// set this quiz to retake
			$retake = get_user_meta( $user_id, '_lpr_quiz_retake', true );
			if ( !isset( $retake ) || !is_array( $retake ) ) {
				$retake = array();
			}
			array_push( $retake, $quiz_id );
			update_user_meta( $user_id, '_lpr_quiz_retake', $retake );
		}

		/**
		 * Load lesson content
		 */
		public static function load_lesson_content() {
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
			global $post;
			$user_id   = get_current_user_id();
			$lesson_id = !empty( $_POST['lesson'] ) ? $_POST['lesson'] : 0;
			if ( !$user_id || !$lesson_id ) {
				wp_die( __( 'Access denied!', 'learn_press' ) );
			}
            $response = array();
			if ( learn_press_mark_lesson_complete( $lesson_id, $user_id ) ) {
                $course_id = learn_press_get_course_by_lesson( $lesson_id );
                $lessons = learn_press_get_lessons_in_course( $course_id );
                $lesson_completed = get_user_meta( $user_id, '_lpr_lesson_completed', true );
                $lesson_completed = ! empty( $lesson_completed[$course_id] ) ? $lesson_completed[$course_id] : array();

                if( $lessons ){
                    if( false !== ( $pos = array_search( $lesson_id, $lessons ) ) ){
                        $loop = ( $pos == count( $lessons ) - 1 ) ? 0 : $pos + 1;
                        $infinite = 0;
                        $max = count( $lessons );

                        while( in_array( $lessons[$loop], $lesson_completed ) && ( $lessons[$loop] != $lesson_id ) ){
                            $loop++;
                            if( $loop == $max ) $loop = 0;
                            if( $infinite > $max ) break;
                        }
                        if( $lessons[$loop] != $lesson_id ){
                            $response['url'] = learn_press_get_course_lesson_permalink( $lessons[$loop], $course_id );
                        }else{
                            $response['url'] = learn_press_get_course_lesson_permalink( $lesson_id, $course_id );
                        }
                    }
                }
			}

            learn_press_send_json( $response );
			die;
		}

		/**
		 * Finish course
		 */
		public static function finish_course() {

			$user_id   = get_current_user_id();
			$course_id = !empty( $_POST['course_id'] ) ? $_POST['course_id'] : 0;

			if ( !$user_id || !$course_id ) {
				wp_die( __( 'Access denied!', 'learn_press' ) );
			}

			$finish     = false;
			$json       = array(
				'finish' => true
			);
			$assessment = get_post_meta( $course_id, '_lpr_course_final', true );
			$pass       = floatval( get_post_meta( $course_id, '_lpr_course_condition', true ) );
			if ( $assessment == 'yes' ) {

				$final_quiz   = lpr_get_final_quiz( $course_id );
				$final_result = learn_press_get_quiz_result( $user_id, $final_quiz );// lpr_get_quiz_result( $final_quiz );
				if ( ! empty( $final_result ) && ! empty( $final_result['mark_percent'] ) && ( $final_result['mark_percent'] * 100 >= $pass ) ) {
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

				$json['message'] = __( 'Congratulation ! You have finished this course', 'learn_press' );
			} else {
				$json['finish']  = false;
				$json['message'] = __( 'Sorry! You can not finish this course now', 'learn_press' );
			}
			wp_send_json( $json );
			die;
		}

		/**
		 * Join event
		 */
		public static function join_event() {

			$user_id  = get_current_user_id();
			$event_id = $_POST['event_id'];
			$events   = get_user_meta( $user_id, '_lpr_event_participated', false );
			if ( $events ) {
				array_push( $events, $event_id );
				update_user_meta( $user_id, '_lpr_event_participated', $events );
			} else {
				array_push( $events, $event_id );
				add_user_meta( $user_id, '_lpr_event_participated', $events );
			}
			echo "Going";
			die;
		}

		/**
		 * not going
		 */
		public static function not_going() {

			$user_id  = get_current_user_id();
			$event_id = $_POST['event_id'];
			$events   = get_user_meta( $user_id, '_lpr_event_participated', true );
			if ( $events ) {
				$key = array_search( $event_id, $events, false );
				unset( $events[$key] );
				update_user_meta( $user_id, '_lpr_event_participated', $events );
			}
			echo "Join this event";
			die;
		}

		/**
		 * Retake course action
		 */
		public static function retake_course() {
			$course_id = !empty( $_REQUEST['course_id'] ) ? $_REQUEST['course_id'] : 0;
			$user_id   = !empty( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : get_current_user_id();

			if ( !$user_id || !$course_id ) {
				wp_die( __( 'Error', 'learn_press' ) );
			}

			////
			// Of course, user only retake course if he has finished
			if ( !learn_press_user_has_finished_course( $course_id, $user_id ) ) {
				wp_die( __( 'Error', 'learn_press' ) );
			}

			// reset course start time
			$course_time = get_user_meta( $user_id, '_lpr_course_time', true );
			if ( $course_time && !empty( $course_time[$course_id] ) ) {
				$course_time[$course_id] = array(
					'start' => time(),
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
	}
}
LPR_AJAX::init();