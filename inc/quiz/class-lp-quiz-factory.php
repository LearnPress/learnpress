<?php
/**
 * Class LP_Quiz_Factory
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Quiz_Factory' ) ) {

	/**
	 * Class LP_Quiz_Factory
	 */
	class LP_Quiz_Factory {
		/**
		 * @var null
		 */
		static $user = null;

		/**
		 * @var null
		 */
		static $quiz = null;

		/**
		 * Quiz factory init.
		 *
		 * @since 3.0.0
		 */
		public static function init() {

			// Handle user do quiz actions in frontend
			$actions = array(
				'start-quiz:nopriv' => 'start_quiz',
				'nav-question-quiz' => 'nav_question',
				'check-answer-quiz' => 'check_answer',
				'show-hint-quiz'    => 'hint_answer',
				'complete-quiz'     => 'finish_quiz',
				'redo-quiz'         => 'redo_quiz',
				'show-result-quiz'  => 'show_result',
				'show-review-quiz'  => 'show_review',

				////
				'retake-quiz'       => 'retake_quiz',
				'check-question'    => 'check_question',
				'fetch-question'    => 'fetch_question',
				'get-question-hint' => 'get_question_hint'
			);
			foreach ( $actions as $action => $function ) {
				LP_Request_Handler::register_ajax( $action, array( __CLASS__, $function ) );
				LP_Request_Handler::register( "lp-{$action}", array( __CLASS__, $function ) );
			}

			add_action( 'learn-press/quiz-started', array( __CLASS__, 'update_user_current_question' ), 10, 3 );
			add_action( 'learn-press/before-start-quiz', array( __CLASS__, 'before_start_quiz' ), 10, 4 );
		}

		/**
		 * Function called before user start a quiz. We will check if course of quiz
		 * is not required enroll (and maybe user is a Guest) then we will enroll user to
		 * the course automatically. And finally, start this quiz for the user.
		 *
		 * @param bool $true
		 * @param int $quiz_id
		 * @param int $course_id
		 * @param int $user_id
		 *
		 * @return bool
		 * @throws Exception
		 */
		public static function before_start_quiz( $true, $quiz_id, $course_id, $user_id ) {
			if ( is_user_logged_in() ) {
				$user = learn_press_get_user( $user_id );
			} else {
				$user = learn_press_get_current_user( true );
			}

			$course  = learn_press_get_course( $course_id );
			$quiz    = learn_press_get_quiz( $quiz_id );
			$preview = $quiz->get_preview();

			if ( ( ! $course->is_required_enroll() && ! $user->has_course_status( $course_id, 'enrolled' ) ) ) { //not support preview feature of quiz any more
				// if quiz is previewable, $preview = true, force create enroll course record notwithstanding course require enroll or not
				$ret = $user->enroll( $course_id, 0, $preview );

				if ( $ret ) {
					$true = true;
				} else {
					$true = false;
				}
			}

			remove_action( 'learn-press/before-start-quiz', array( __CLASS__, 'maybe_guest_start_quiz' ) );

			return $true;
		}

		/**
		 * Callback function for starting quiz.
		 *
		 * @since 3.0.0
		 */
		public static function start_quiz() {

			$course_id = LP_Request::get_int( 'course-id' );
			$quiz_id   = LP_Request::get_int( 'quiz-id' );
			$user      = learn_press_get_current_user();
			$quiz      = learn_press_get_quiz( $quiz_id );
			$result    = array( 'result' => 'success' );

			try {
				// Actually, no save question here. Just check nonce here.
				$check = self::maybe_save_questions( 'start' );

				// PHP Exception
				if ( learn_press_is_exception( $check ) ) {
					throw $check;
				}

				$data = $user->start_quiz( $quiz_id, $course_id, true );
				if ( is_wp_error( $data ) ) {
					throw new Exception( $data->get_error_message() );
				} else {

					$redirect           = $quiz->get_question_link( learn_press_get_user_item_meta( $data['user_item_id'], '_current_question', true ) );
					$result['result']   = 'success';
					$result['redirect'] = apply_filters( 'learn-press/quiz/started-redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
				}
			} catch ( Exception $ex ) {
				$result['message']  = $ex->getMessage();
				$result['result']   = 'failure';
				$result['redirect'] = apply_filters( 'learn-press/quiz/start-quiz-failure-redirect', learn_press_get_current_url(), $quiz_id, $course_id, $user->get_id() );
			}

			learn_press_maybe_send_json( $result );

			if ( ! empty( $result['message'] ) ) {
				learn_press_add_message( $result['message'] );
			}

			if ( ! empty( $result['redirect'] ) ) {
				wp_redirect( $result['redirect'] );
				exit();
			}

		}

		/**
		 * Save question data when navigating question.
		 *
		 * @since 3.0.0
		 */
		public static function nav_question() {
			$return = self::maybe_save_questions( 'nav-question' );
			$nav    = LP_Request::get( 'nav-type' );
			if ( is_array( $return ) ) {
				$quiz     = learn_press_get_quiz( $return['quiz_id'] );
				$redirect = false;
				if ( $nav === 'prev-question' && ! empty( $return['prev_question'] ) ) {
					$redirect = $quiz->get_question_link( $return['prev_question'] );
				} elseif ( ! empty( $return['next_question'] ) ) {
					$redirect = $quiz->get_question_link( $return['next_question'] );
				}
				if ( $redirect ) {
					wp_safe_redirect( $redirect );
					exit();
				}
			} else {
				$quiz_id     = LP_Request::get( 'quiz-id' );
				$question_id = LP_Request::get( 'question-id' );
				$quiz        = learn_press_get_quiz( $quiz_id );

				if ( $nav === 'prev-question' && $question_id ) {
					$prev_question_id = $quiz->get_prev_question( $question_id );
					$redirect         = $quiz->get_question_link( $prev_question_id );
				} else {
					$next_question_id = $quiz->get_next_question( $question_id );
					$redirect         = $quiz->get_question_link( $next_question_id );
				}
				if ( $redirect ) {
					wp_safe_redirect( $redirect );
					exit();
				}
			}

		}

		/**
		 * Callback for check quiz question answer.
		 *
		 * @since 3.0.0
		 */
		public static function check_answer() {
			$user        = LP_Global::user();
			$course_id   = LP_Request::get_int( 'course-id' );
			$quiz_id     = LP_Request::get_int( 'quiz-id' );
			$question_id = LP_Request::get_int( 'question-id' );
			//LP_Debug::startTransaction();
			try {
				$result = array( 'result' => 'failure' );

				$check = self::maybe_save_questions( 'check-answer' );

				// PHP Exception
				if ( learn_press_is_exception( $check ) ) {
					throw $check;
				}

				$remain = $user->check_question( $question_id, $quiz_id, $course_id );
				if ( is_wp_error( $remain ) ) {
					throw new Exception( $remain->get_error_message(), $remain->get_error_code() );
				} else {
					if ( $course = learn_press_get_course( $course_id ) ) {
						$quiz = $course->get_item( $quiz_id );
						//$quiz_data = $user->get_item_data( $quiz_id, $course_id );
						$redirect = $quiz->get_question_link( $question_id );
						$question = learn_press_get_question( $question_id );
						$question->show_correct_answers( 'yes' );

						$result['result']   = 'success';
						$result['redirect'] = apply_filters( 'learn-press/quiz/completed-redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
						$result['remain']   = $remain;
						$result['html']     = learn_press_get_template_content( 'content-question/content.php', array( 'quiz' => $quiz ) );
					}
				}
			} catch ( Exception $ex ) {
				$result['message'] = $ex->getMessage();
				$result['code']    = $ex->getCode();
			}

			$result = apply_filters( 'learn-press/quiz/hint-answer-result', $result, $quiz_id, $course_id, $user->get_id() );

			// Send json if the ajax is calling
			learn_press_maybe_send_json( $result );

			// Message
			if ( ! empty( $result['message'] ) ) {
				learn_press_add_message( $result['message'] );
			}

			// Redirecting...
			if ( ! empty( $result['redirect'] ) ) {
				wp_redirect( $result['redirect'] );
				exit();
			}
		}

		/**
		 * Callback for show quiz question hint.
		 *
		 * @since 3.0.0
		 */
		public static function hint_answer() {
			$user        = LP_Global::user();
			$course_id   = LP_Request::get_int( 'course-id' );
			$quiz_id     = LP_Request::get_int( 'quiz-id' );
			$question_id = LP_Request::get_int( 'question-id' );
			try {
				$result = array( 'result' => 'failure' );

				$check = self::maybe_save_questions( 'show-hint' );
				// PHP Exception
				if ( learn_press_is_exception( $check ) ) {
					throw $check;
				}

				$remain = $user->hint( $question_id, $quiz_id, $course_id );

				if ( is_wp_error( $remain ) ) {
					throw new Exception( $remain->get_error_message(), $remain->get_error_code() );
				} else {
					if ( $course = learn_press_get_course( $course_id ) ) {
						$quiz      = $course->get_item( $quiz_id );
						$quiz_data = $user->get_item_data( $quiz_id, $course_id );
						$redirect  = $quiz->get_question_link( $quiz_data->get_current_question() );

						$result['result']   = 'success';
						$result['redirect'] = apply_filters( 'learn-press/quiz/completed-redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
						$result['remain']   = $remain;
						$result['html']     = learn_press_get_template_content( 'content-question/content.php', array( 'quiz' => $quiz ) );
					}
				}
			} catch ( Exception $ex ) {
				$result['message'] = $ex->getMessage();
				$result['code']    = $ex->getCode();
			}
			//LP_Debug::rollbackTransaction();
			$result = apply_filters( 'learn-press/quiz/hint-answer-result', $result, $quiz_id, $course_id, $user->get_id() );

			// Send json if the ajax is calling
			learn_press_maybe_send_json( $result );

			// Message
			if ( ! empty( $result['message'] ) ) {
				learn_press_add_message( $result['message'] );
			}

			// Redirecting...
			if ( ! empty( $result['redirect'] ) ) {
				wp_redirect( $result['redirect'] );
				exit();
			}
		}

		/**
		 * Callback for finishing quiz.
		 *
		 * @since 3.0.0
		 */
		public static function finish_quiz() {

			try {
				$result = array( 'result' => 'failure' );

				$check = self::maybe_save_questions( 'complete' );
				// PHP Exception
				// Comment by tungnx
				/*if ( learn_press_is_exception( $check ) ) {
					throw $check;
				}*/

				$user = LP_Global::user();

				$course_id = LP_Request::get_int( 'course-id' );
				$quiz_id   = LP_Request::get_int( 'quiz-id' );

				$data = $user->finish_quiz( $quiz_id, $course_id, true );

				if ( is_wp_error( $data ) ) {
					throw new LP_Exception( $data->get_error_message(), $data->get_error_code() );
				} else {
					if ( $course = learn_press_get_course( $course_id ) ) {
						$quiz     = $course->get_item( $quiz_id );
						$redirect = $quiz->get_permalink();// _question_link( learn_press_get_user_item_meta( $data['user_item_id'], '_current_question' ), true );

						$result['result']   = 'success';
						$result['redirect'] = apply_filters( 'learn-press/quiz/completed-redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
						$result['data']     = $data;

						// Redirecting...
						if ( ! empty( $result['redirect'] ) ) {
							wp_cache_flush();
							wp_redirect( $result['redirect'] );
							exit();
						}
					}
				}
			} catch ( LP_Exception $ex ) {
				$result['message'] = $ex->getMessage();
				$result['code']    = $ex->getCode();
			}

			// Filter the result
			$result = apply_filters( 'learn-press/quiz/finish-result', $result );

			// Send json if the ajax is calling
			learn_press_maybe_send_json( $result );

			// Message
			if ( ! empty( $result['message'] ) ) {
				learn_press_add_message( $result['message'] );
			}
		}

		/**
		 * Callback function for retaking quiz.
		 *
		 * @since 3.0.0
		 */
		public static function redo_quiz() {
			try {
				$result = array( 'result' => 'failure' );

				// Actually, no need to save question here. Just check wp nonce in this case.
				$check = self::maybe_save_questions( 'redo' );

				// PHP Exception
				if ( learn_press_is_exception( $check ) && $check->getCode() === 'user_completed_quiz' ) {
					throw $check;
				}

				$course_id = LP_Request::get_int( 'course-id' );
				$quiz_id   = LP_Request::get_int( 'quiz-id' );
				$user      = learn_press_get_current_user();
				$quiz      = learn_press_get_quiz( $quiz_id );
				$data      = $user->retake_quiz( $quiz_id, $course_id, true );

				if ( is_wp_error( $data ) ) {
					throw new LP_Exception( $data->get_error_message(), $data->get_error_code() );
				} elseif ( $data ) {
					$current_question_id = learn_press_get_user_item_meta( $data->get_user_item_id(), '_current_question' );
					$redirect            = $quiz->get_question_link( $current_question_id );

					$result['result']   = 'success';
					$result['redirect'] = apply_filters( 'learn-press/quiz/retaken-redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
					$result['data']     = $data;
				}
			} catch ( LP_Exception $ex ) {
				$result['message'] = $ex->getMessage();
				$result['code']    = $ex->getCode();
				$result['result']  = 'failure';
			}
			// Filter
			$result = apply_filters( 'learn-press/quiz/redo-result', $result );
			// Send ajax json
			learn_press_maybe_send_json( $result );

			// Message
			if ( ! empty( $result['message'] ) ) {
				learn_press_add_message( $result['message'] );
			}

			// Redirecting...
			if ( ! empty( $result['redirect'] ) ) {
				wp_redirect( $result['redirect'] );
				exit();
			}
		}

		/**
		 * Callback function for show quiz result.
		 *
		 * @since 3.0.0
		 */
		public static function show_result() {
			$quiz_id = LP_Request::get_int( 'quiz-id' );
			$quiz    = learn_press_get_quiz( $quiz_id );
			if ( $quiz ) {
				$redirect = $quiz->get_permalink();
			} else {
				$redirect = get_the_permalink();
			}
			wp_redirect( $redirect );
			exit();
		}

		/**
		 * Callback function for show quiz review.
		 *
		 * @since 3.0.0
		 */
		public static function show_review() {
			$quiz_id = LP_Request::get_int( 'quiz-id' );
			$quiz    = learn_press_get_quiz( $quiz_id );
			if ( $quiz ) {
				$redirect = $quiz->get_question_link( $quiz->get_question_at( 0 ) );
			} else {
				$redirect = get_the_permalink();
			}
			wp_redirect( $redirect );
			exit();
		}

		/**
		 * Verify nonce and/or save question answers when posting.
		 *
		 * @param string $action
		 * @param string $nonce
		 *
		 * @return array|bool|LP_Exception
		 *
		 * @since 3.0.0
		 */
		public static function maybe_save_questions( $action = '', $nonce = '' ) {

			try {

				$nav_type    = LP_Request::get_string( 'nav-type' );
				$course_id   = LP_Request::get_int( 'course-id' );
				$quiz_id     = LP_Request::get_int( 'quiz-id' );
				$question_id = LP_Request::get_int( 'question-id' );

				learn_press_setup_object_data( get_post( $course_id ) );

				if ( ! LP_Nonce_Helper::verify_quiz_action( $action, $nonce, $quiz_id, $course_id ) ) {
					throw new LP_Exception( __( 'Something went wrong!', 'learnpress' ), LP_INVALID_REQUEST );
				}

				if ( ! $questions = self::get_answers_posted() ) {
					$questions = array();
				}

				$user        = learn_press_get_current_user();
				$course      = learn_press_get_course( $course_id );
				$quiz        = learn_press_get_quiz( $quiz_id );
				$course_data = $user->get_course_data( $course->get_id() );

				if ( $quiz_data = $course_data->get_item_quiz( $quiz->get_id() ) ) {

					if ( 'completed' === $quiz_data->get_status() ) {
						throw new LP_Exception( LP_Strings::get( 'you_have_completed_quiz' ), 'user_completed_quiz' );
					}

					// If user click 'Skip' button
					if ( $nav_type === 'skip-question' ) {
						if ( $quiz_data->get_question_answer( $question_id ) == '' ) {
							$questions[ $question_id ] = '__SKIPPED__';
						} else {
							unset( $questions[ $question_id ] );
						}
					} else {
						if ( ! array_key_exists( $question_id, $questions ) ) {
							$questions[ $question_id ] = array();
						}
					}

					$quiz_data->add_question_answer( $questions );
					$quiz_data->update_meta( '_current_question', $question_id );
					$quiz_data->update();
				}

				$return = array(
					'quiz_id'       => $quiz_id,
					'course_id'     => $course_id,
					'prev_question' => $user->get_prev_question( $quiz_id, $course_id ),
					'next_question' => $user->get_next_question( $quiz_id, $course_id )
				);
			} catch ( LP_Exception $ex ) {
				$return = $ex;
			}

			return $return;
		}

		/**
		 * Update current question for user while doing quiz.
		 *
		 * @param int $quiz_id
		 * @param int $course_id
		 * @param int $user_id
		 */
		public static function update_user_current_question( $quiz_id, $course_id, $user_id ) {
			$user   = learn_press_get_user( $user_id );
			$quiz   = learn_press_get_quiz( $quiz_id );
			$return = $user->get_item_archive( $quiz_id, $course_id, true );

			if ( ! empty( $return['user_item_id'] ) && $questions = $quiz->get_questions() ) {
				$question_id = reset( $questions );
				learn_press_update_user_item_meta( $return['user_item_id'], '_current_question', $question_id );
			}
		}

		/**
		 * Parse question answers when posting.
		 *
		 * @param int $question_id
		 *
		 * @return array|bool
		 */
		public static function get_answers_posted( $question_id = 0 ) {
			$questions = array();
			try {

				$post_data = stripslashes_deep( $_REQUEST );

				/**
				 * Find the questions in the origin $_REQUEST
				 */
				if ( ! $questions = self::_get_answer( $post_data ) ) {

					// If there is no questions then try to find in param with key name is 'question-data'
					if ( empty( $post_data['question-data'] ) ) {
						return false;
					}

					$data = is_string( $post_data['question-data'] ) ? @json_decode( $post_data['question-data'] ) : $post_data['question-data'];
					settype( $data, 'array' );

					$questions = self::_get_answer( $data );
				}
			} catch ( Exception $ex ) {
			}

			return $question_id ? ( array_key_exists( $question_id, $questions ) ? $questions[ $question_id ] : false ) : $questions;
		}

		/**
		 * Get answers for questions from post data
		 *
		 * @param array $post_data
		 *
		 * @return array
		 * @since 3.0.0
		 *
		 */
		protected static function _get_answer( $post_data ) {
			$questions = array();
			if ( is_array( $post_data ) ) {
				foreach ( $post_data as $k => $v ) {
					$id = absint( str_replace( 'learn-press-question-', '', $k ) );
					if ( $id ) {
						if ( is_object( $v ) ) {
							$v = (array) $v;
						}
						$questions[ $id ] = $v;
					}
				}
			}

			return $questions;
		}

	}

}

LP_Quiz_Factory::init();
