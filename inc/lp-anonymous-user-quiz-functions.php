<?php

/**
 * Class LP_Anonymous_User_Quiz
 *
 * Functions for non-logged users can be access the course and do quiz
 *
 */
return;

class LP_Anonymous_User_Quiz {
	function __construct() {
		add_action( 'learn_press_after_reset_user_quiz', array( $this, 'after_reset_user_quiz' ), 99, 2 );
		add_filter( 'learn_press_get_question_answers', array( $this, 'anonymous_get_question_answers' ), 99, 3 );
		add_filter( 'learn_press_get_question_position', array( $this, 'anonymous_get_question_position' ), 99, 4 );
		add_action( 'learn_press_before_nav_question_form', array( $this, 'before_nav_question_form' ), 99, 2 );
		add_filter( 'learn_press_before_user_start_quiz', array( $this, 'do_quiz_for_anonymous_user' ), 999, 3 );
		add_filter( 'learn_press_user_has_completed_quiz', array( $this, 'user_has_completed_quiz' ), 999, 3 );

		add_filter( 'learn_press_user_has_started_quiz', array( $this, 'user_has_started_quiz' ), 999, 3 );
		add_action( 'learn_press_submit_answer', array( $this, 'submit_answer' ), 999, 5 );
		add_filter( 'learn_press_anonymous_user_can_retake_quiz', array( $this, 'user_can_retake_quiz' ), 999, 2 );
		add_action( 'learn_press_content_quiz_after_title_element', array( $this, '_debug' ) );
		add_filter( 'learn_press_user_has_passed_course', array( $this, 'user_has_passed_course' ), 999, 3 );
		add_filter( 'learn_press_get_user_quiz_time', array( $this, 'user_quiz_time' ), 999, 3 );

		add_filter( 'learn_press_get_user_quiz_questions', array( $this, 'user_quiz_questions' ), 10, 3 );
		add_filter( 'learn_press_user_quiz_start_time', array( $this, 'quiz_start_time' ), 10, 3 );
		//add_filter( 'learn_press_reset_user_quiz', array( $this, 'reset_user_quiz'), 999, 3 );

	}

	function quiz_start_time( $time, $quiz_id, $user_id ) {
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		$session   = LP_Session::instance();
		$quiz      = $session->get( 'anonymous_quiz' );
		if ( ( 'no' == get_post_meta( $course_id, '_lpr_course_enrolled_require', true ) ) && isset( $quiz['start'] ) ) {
			$time = $quiz['start'];
		}
		return $time;
	}

	function user_quiz_questions( $quiz_questions, $user_id, $quiz_id ) {
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( 'no' == get_post_meta( $course_id, '_lpr_course_enrolled_require', true ) ) {
			$quiz_questions = $this->get_questions_for_anonymous_user();
		}

		return $quiz_questions;
	}

	function user_quiz_time( $time, $quiz_id, $user_id ) {
		if ( $this->is_public_quiz( learn_press_get_course_by_quiz( $quiz_id ) ) ) {
			$quiz  = $this->get_session();
			$start = !empty( $quiz['start'] ) ? $quiz['start'] : 0;
			$end   = !empty( $quiz['end'] ) ? $quiz['end'] : 0;
			$time  = absint( $end - $start );
		}
		return $time;
	}

	function user_has_passed_course( $user_passed, $course_id, $user_id ) {
		if ( $this->is_public_quiz( $course_id ) ) {
			if ( ( get_post_meta( $course_id, '_lpr_course_final', true ) == 'yes' ) && ( $quiz = lpr_get_final_quiz( $course_id ) ) ) {
				$passed            = learn_press_quiz_evaluation( $quiz, $user_id );
				$passing_condition = learn_press_get_course_passing_condition( $course_id );

			} else {
				$passed            = lpr_course_evaluation( $course_id );
				$passing_condition = 0;
			}
			$user_passed = $passing_condition ? ( $passed >= $passing_condition ? $passed : 0 ) : ( $passed == 100 );
		}

		return $user_passed;
	}

	function _debug() {
		//print_r( $this->get_session( ) );
	}

	function reset_user_quiz( $reset, $quiz_id, $user_id ) {

	}

	/**
	 * Filter hook to enable non-logged in user can be retake a quiz
	 *
	 * @param boolean
	 * @param int
	 *
	 * @return bool
	 */
	function user_can_retake_quiz( $can_retake, $quiz_id ) {
		if ( $this->is_public_quiz( learn_press_get_course_by_quiz( $quiz_id ) ) ) {
			if ( $this->get_session() ) {
				$can_retake = true;
			}
		}
		return $can_retake;
	}

	/**
	 * Get a value from our session variable
	 *
	 * @param string
	 *
	 * @return bool|mixed
	 */
	function get_session( $name = null ) {
		$session = LP_Session::instance();
		$quiz    = $session->get( 'anonymous_quiz' );
		return !$name ? $quiz : ( isset( $quiz[$name] ) ? $quiz[$name] : false );
	}

	/**
	 * Filter hook to check if non-logged in user has completed quiz
	 *
	 * @param boolean $completed
	 * @param int     $user_id
	 * @param int     $quiz_id
	 *
	 * @return bool|mixed
	 */
	function user_has_completed_quiz( $completed, $user_id, $quiz_id ) {
		if ( $this->is_public_quiz( learn_press_get_course_by_quiz( $quiz_id ) ) ) {
			$completed = $this->get_session( 'finished' );
		}
		return $completed;
	}

	/**
	 * Action hook to handle the answer submitted by non-logged in user
	 *
	 * @param array   $question_answer
	 * @param int     $question_id
	 * @param int     $quiz_id
	 * @param int     $user_id
	 * @param boolean $finished
	 */
	function submit_answer( $question_answer, $question_id, $quiz_id, $user_id, $finished ) {
		if ( $this->is_public_quiz( learn_press_get_course_by_quiz( $quiz_id ) ) ) {
			$session = LP_Session::instance();
			$quiz    = $session->get( 'anonymous_quiz' );
			if ( is_array( $question_answer ) ) {
				$quiz['answers'][$question_id] = reset( $question_answer );
			} else {
				$quiz['answers'][$question_id] = null;
			}
			if ( $finished ) {
				$quiz['finished'] = true;
				$quiz['end']      = current_time( 'timestamp' );
			}
			$session->set( 'anonymous_quiz', $quiz );
		}
	}

	/**
	 * Check if non-logged in user has started quiz
	 *
	 * @param boolean $started
	 * @param int     $quiz_id
	 * @param int     $user_id
	 *
	 * @return bool
	 */
	function user_has_started_quiz( $started, $quiz_id, $user_id ) {
		if ( $this->is_public_quiz( $quiz_id ) ) {
			$session = LP_Session::instance();
			$quiz    = $session->get( 'anonymous_quiz' );
			$started = $quiz['questions'] ? true : false;
		}
		return $started;
	}

	/**
	 * Check if a quiz is accessible for non-logged in user
	 *
	 * @param int $course_id
	 *
	 * @return bool
	 */
	function is_public_quiz( $course_id ) {
		return ( false == learn_press_course_enroll_required( $course_id ) && !is_user_logged_in() );
	}

	/**
	 * Action hook to reset the quiz data for non-logged in user
	 *
	 * @param int $quiz_id
	 * @param int $user_id
	 */
	function after_reset_user_quiz( $quiz_id, $user_id ) {
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( $this->is_public_quiz( $course_id ) ) {
			$session = LP_Session::instance();
			$session->remove( 'anonymous_quiz' );
		}
	}

	/**
	 * @param $question_id
	 * @param $course_id
	 */
	function before_nav_question_form( $question_id, $course_id ) {

		if ( $this->is_public_quiz( $course_id ) ) {
			if ( !empty( $_POST['data']['user_questions'] ) ) {
				$questions = explode( ',', $_POST['data']['user_questions'] );
			} else {
				$questions = $this->get_questions_for_anonymous_user();
			}
			if ( $questions ) {

			}
		}
	}

	/**
	 * Filter questions for non-logged in user
	 *
	 * @return bool|array
	 */
	function get_questions_for_anonymous_user() {
		$questions = false;
		$session   = LP_Session::instance();
		$quiz      = $session->get( 'anonymous_quiz' );
		if ( $quiz ) {
			$questions = $quiz['questions'];
		}
		return $questions;
	}

	/**
	 * Get the position of current question
	 *
	 * @param int $return
	 * @param int $user_id
	 * @param int $quiz_id
	 * @param int $question_id
	 *
	 * @return mixed
	 */
	function anonymous_get_question_position( $return, $user_id, $quiz_id, $question_id ) {
		$course_id = learn_press_get_course_by_quiz( $quiz_id );

		if ( $this->is_public_quiz( $course_id ) ) {
			if ( $questions = $this->get_questions_for_anonymous_user() ) {
				if ( !$question_id ) {
					$question_id = !empty( $_POST['current'] ) ? $_POST['current'] : 0;
				}

				$pos                = array_search( $question_id, $questions );
				$pos                = false !== $pos ? $pos : 0;
				$return['position'] = $pos;
				$return['id']       = $questions[$pos];

			}
		}
		return $return;
	}

	/**
	 * Gets the answers
	 *
	 * @param array $answer
	 * @param int   $quiz_id
	 * @param int   $user_id
	 *
	 * @return bool|mixed
	 */
	function anonymous_get_question_answers( $answer, $quiz_id, $user_id ) {
		if ( $this->is_public_quiz( learn_press_get_course_by_quiz( $quiz_id ) ) ) {
			$answer = $this->get_session( 'answers' );
		}
		return $answer;
	}

	/**
	 * Get all questions in a quiz
	 *
	 * @param array   $questions
	 * @param int     $quiz_id
	 * @param boolean $only_ids
	 *
	 * @return array|bool
	 */
	function anonymous_get_quiz_questions( $questions, $quiz_id, $only_ids ) {
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( $this->is_public_quiz( $course_id ) ) {
			if ( $_questions = $this->get_questions_for_anonymous_user() ) {
				$questions = $_questions;
			}
		}
		return $questions;
	}

	/**
	 * Quiz for anonymous users
	 *
	 * @param boolean
	 * @param int
	 * @param int
	 *
	 * @return boolean
	 */
	function do_quiz_for_anonymous_user( $continue, $quiz_id, $user_id ) {
		$course_id = learn_press_get_course_by_quiz( $quiz_id );
		if ( $this->is_public_quiz( $course_id ) ) {
			$session = LP_Session::instance();
			$session->set(
				'anonymous_quiz',
				array(
					'questions' => array_values( learn_press_get_quiz_questions( $quiz_id ) ),
					'finished'  => 0,
					'answers'   => array(),
					'start'     => time(),
					'end'       => null
				)
			);
			$continue = false;
			do_action( 'learn_press_user_start_quiz', $quiz_id, $user_id );
		}
		return $continue;
	}

}

// create new an anonymous instance
new LP_Anonymous_User_Quiz();
