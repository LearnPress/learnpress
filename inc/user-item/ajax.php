<?php

/**
 * Class LP_User_Item_Ajax
 *
 * @since 3.2.0
 */
class LP_User_Item_Ajax {
	/**
	 * @var LP_User_Item_Course
	 */
	protected static $course_data = null;

	/**
	 * @var LP_User
	 */
	protected static $user = null;

	/**
	 * Init
	 */
	public static function init() {
		$ajaxEvents = array(
			'complete-course-item',
			'get-quiz',
			'start-quiz',
			'retake-quiz',
			'complete-quiz',
			'get-question-data',
			'update-quiz-state',
			'update-current-question',
		);

		foreach ( $ajaxEvents as $action => $callback ) {

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

	/**
	 * Update current question
	 *
	 * @since 3.2.0
	 */
	public static function update_current_question() {
		self::verify();

		$quizId     = LP_Request::get_int( 'itemId' );
		$questionId = LP_Request::get_int( 'questionId' );

		$quizData = self::$course_data->get_item( $quizId );

		if ( $quizData ) {
			$quizData->set_current_question( $questionId );
		}

		die();
	}

	/**
	 * Update user state of current quiz
	 */
	public static function update_quiz_state() {
		self::verify();
		$itemId      = LP_Request::get_int( 'itemId' );
		$course_data = self::$course_data;
		$response    = array();

		if ( $item = $course_data->get_item( $itemId ) ) {
			if ( ! $item->is_completed() ) {
				$item->update_meta( '_time_spend', LP_Request::get( 'timeSpend' ) );

				$item->add_question_answer( LP_Request::get( 'answers' ) );
				$item->update();
			}
		}
		learn_press_send_json( $_REQUEST );
	}

	public static function complete_course_item() {

		self::verify();
		//LP_Debug::startTransaction();
		$itemId      = LP_Request::get_int( 'itemId' );
		$course_data = self::$course_data;
		$response    = array();

		if ( $item = $course_data->get_item( $itemId ) ) {

			$course = $course_data->get_course();
			$it     = $course->get_item( $itemId );

			if ( $item->is_completed() ) {
				$item->set_status( 'started' );
				$item->update();
			} else {
				$item->complete();
			}
			$response['completed'] = $item->is_completed();
			$response['classes']   = array_values( $it->get_class() );
			$response['results']   = $course_data->get_percent_result();
		}
		//LP_Debug::rollbackTransaction();
		learn_press_send_json( $response );
	}

	/**
	 * Load quiz data
	 *
	 * @since 3.2.0
	 */
	public static function get_quiz() {
		self::verify();

		$json = self::get_quiz_json_data( LP_Request::get_int( 'itemId' ), LP_Request::get_int( 'courseId' ) );

		learn_press_send_json( $json );
	}

	public static function start_quiz() {
		self::verify();

		if ( ! self::$course_data ) {
			die( '-3' );
		}

		//LP_Debug::startTransaction();
		$user     = learn_press_get_current_user();
		$courseId = LP_Request::get_int( 'courseId' );
		$itemId   = LP_Request::get_int( 'itemId' );
		$result   = array( 'result' => 'success' );

		try {

			$data = $user->start_quiz( $itemId, $courseId, true );
			if ( is_wp_error( $data ) ) {
				throw new Exception( $data->get_error_message() );
			}

			$result['notifications'] = array(
				array(
					'message' => sprintf( __( 'You have started quiz "%s"', 'learnpress' ), get_the_title( $itemId ) ),
					'type'    => 'success'
				)
			);

			$result['quizData'] = self::get_quiz_json_data( $itemId, $courseId );
		}
		catch ( Exception $ex ) {
			$result['notifications'] = array( array( 'message' => $ex->getMessage(), 'type' => 'error' ) );
			$result['result']        = 'error';
		}
		//LP_Debug::rollbackTransaction();
		learn_press_send_json( $result );
	}

	public static function complete_quiz() {
		self::verify();
		$course_id = LP_Request::get_int( 'courseId' );
		$quiz_id   = LP_Request::get_int( 'itemId' );
		$user      = learn_press_get_current_user();
		$course    = learn_press_get_course( $course_id );
		$quiz      = learn_press_get_quiz( $quiz_id );
		$quiz->set_course( $course_id );
		$course_data = $user->get_course_data( $course->get_id() );
		$questions   = LP_Request::get( 'answers' );
		$result      = array();

		if ( $quiz_data = $course_data->get_item_quiz( $quiz->get_id() ) ) {

			if ( 'completed' === $quiz_data->get_status() ) {
				throw new Exception( __( '#2. Something went wrong!', 'learnpress' ), LP_INVALID_REQUEST );
			}

			$quiz_data->update_meta( '_time_spend', LP_Request::get( 'timeSpend' ) );
			$quiz_data->add_question_answer( $questions );
			$r = $quiz_data->update();
			$quiz_data->complete();

			$result['results'] = $quiz_data->calculate_results();
		}

		$result['result'] = 'success';
		$result['status'] = 'completed';

		LP_Notifications::instance()->add( 'You have completed quiz' );

		learn_press_send_json( $result );
	}

	public static function retake_quiz() {
		//LP_Debug::startTransaction();
		self::verify();
		$course_id = LP_Request::get_int( 'courseId' );
		$quiz_id   = LP_Request::get_int( 'itemId' );
		$user      = learn_press_get_current_user();
//		$course    = learn_press_get_course( $course_id );
//		$quiz      = learn_press_get_quiz( $quiz_id );
//		$quiz->set_course( $course_id );
//		$course_data = $user->get_course_data( $course->get_id() );
//		$questions   = LP_Request::get( 'answers' );
//		$result      = array();
//
//		if ( $quiz_data = $course_data->get_item_quiz( $quiz->get_id() ) ) {
//
//			if ( 'completed' === $quiz_data->get_status() ) {
//				throw new Exception( __( '#2. Something went wrong!', 'learnpress' ), LP_INVALID_REQUEST );
//			}
//
//			$quiz_data->add_question_answer( $questions );
//			$r                       = $quiz_data->update();
//			$result['results']       = $quiz_data->get_results( false );
//			$result['results']['passing_grade'] = $quiz->get_passing_grade();
//		}

		$user->retake_quiz( $quiz_id, $course_id );
		$quizData = self::$course_data->get_item( $quiz_id );
		$quizData->update_meta( '_time_spend', 0 );
		$quizData->update_meta( '_question_answers', 0 );
		$quizData->update_meta( 'grade', '' );
		$quizData->reset();
		$result['quizData']      = self::get_quiz_json_data( $quiz_id, $course_id );
		$result['notifications'] = __( 'You started quiz', 'learnpress' );

		//LP_Notifications::instance()->add( 'You have completed quiz' );
		//LP_Debug::rollbackTransaction();
		learn_press_send_json( $result );
	}

	public static function get_quiz_json_data( $quizId, $courseId ) {
		return learn_press_get_quiz_data_json( $quizId, $courseId );
	}

	protected static function _get_user_answers( $question_id, $quiz_id, $course_id ) {
		$answers  = array();
		$question = learn_press_get_question( $question_id );
		$question->setup_data( $quiz_id, $course_id );
		if ( $_answers = $question->get_answers() ) {
			foreach ( $_answers as $answer ) {

				$answers[] = array(
					'id'      => $answer->get_id(),
					'checked' => $answer->is_checked(),
					'is_true' => $answer->is_true()
				);

			}
		}

		return $answers;
	}

	public static function get_question_data() {
		self::verify();
		/**
		 * @var LP_Question               $question
		 * @var LP_Question_Answers       $answers
		 * @var LP_Question_Answer_Option $answer
		 */
		LP_Debug::startTransaction();
		$course = self::get_course();
		$user   = learn_press_get_current_user();
		$quiz   = self::get_item();

		$question_id = LP_Request::get_int( 'question_id' );

		$extraAction = LP_Request::get( 'extraAction' );
		$extraData   = LP_Request::get( 'extraData' );
		$extraReturn = false;
		$question    = learn_press_get_question( $question_id );
		$checkResult = false;
		switch ( $extraAction ) {
			case 'check-answer':
				if ( $extraData && array_key_exists( 'answers', $extraData ) ) {
					$quiz->set_course( $course );
					$course_data = $user->get_course_data( $course->get_id() );

					if ( $quiz_data = $course_data->get_item_quiz( $quiz->get_id() ) ) {

						if ( 'completed' === $quiz_data->get_status() ) {
							throw new Exception( __( '#2. Something went wrong!', 'learnpress' ), LP_INVALID_REQUEST );
						}

						$quiz_data->add_question_answer( array( $question_id => $extraData['answers'] ) );
						$a = $quiz_data->update();

						$question->setup_data( $quiz->get_id() );
						$checkResult = true;
					}

					$extraReturn = array(
						'quiz_id'       => $quiz->get_id(),
						'course_id'     => $course->get_id(),
						'prev_question' => $user->get_prev_question( $quiz->get_id(), $course->get_id() ),
						'next_question' => $user->get_next_question( $quiz->get_id(), $course->get_id() )
					);
				}
				$r = $user->check_question( $question_id, $quiz->get_id(), $course->get_id() );
				break;
			case 'do_hint':
				$user->hint( $question_id, $quiz->get_id(), $course->get_id() );
		}
		LP_Object_Cache::flush();
		$checked = $user->has_checked_answer( $question_id, $quiz->get_id(), $course->get_id() );
		$json    = array(
			'explanation'  => $checked ? $question->get_explanation() : '__NONE__',
			'extra_result' => $extraReturn,
			'userAnswers'  => array()
		);

		if ( $checkResult ) {
			if ( $answers = $question->get_answers() ) {
				foreach ( $answers as $answer ) {


					$json['userAnswers'][] = array(
						'id'      => $answer->get_id(),
						'checked' => $answer->is_checked(),
						'is_true' => $answer->is_true()
					);


				}
			}
		}

		LP_Debug::rollbackTransaction();
		learn_press_send_json( $json );
	}

	/**
	 * @param LP_User_Item_Quiz $quizData
	 *
	 * @return stdClass
	 */
	protected static function _get_quiz_answers( $quizData ) {
		$_answers = $quizData->get_meta( '_question_answers' );
		$answers  = new stdClass();
		if ( ! $_answers ) {
			return $answers;
		} else {
			foreach ( $_answers as $k => $answer ) {
				if ( $answer === '' || $answer === 'undefined' ) {
					continue;
				}

				$answers->{$k} = $answer;
			}
		}

		return $answers;
	}

	/**
	 * Verify requesting
	 */
	public static function verify() {
		$courseId = LP_Request::get_int( 'courseId' );
		$user     = learn_press_get_current_user();

		if ( ! wp_verify_nonce( LP_Request::get_string( 'namespace' ), 'lp-' . $user->get_id() . '-' . $courseId ) ) {
			die( '-1' );
		}

		$course = learn_press_get_course( $courseId );

		if ( ! $course ) {
			die( '-2' );
		}

		self::$user        = $user;
		self::$course_data = $user->get_course_data( $courseId );
	}

	public static function get_course() {
		return learn_press_get_course( LP_Request::get_int( 'courseId' ) );
	}

	public static function get_item() {
		if ( $course = self::get_course() ) {
			return $course->get_item( LP_Request::get_int( 'itemId' ) );
		}

		return false;
	}

}

LP_User_Item_Ajax::init();