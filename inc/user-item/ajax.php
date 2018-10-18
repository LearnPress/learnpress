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
			'get-quiz'
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

	public static function complete_course_item() {

		self::verify();
		LP_Debug::startTransaction();
		$itemId      = LP_Request::get_int( 'itemId' );
		$course_data = self::$course_data;
		$response    = array();

		if ( $item = $course_data->get_item( $itemId ) ) {

			$course = $course_data->get_course();
			$it     = $course->get_item( $itemId );

			$response['result']  = $item->complete();
			$response['classes'] = array_values( $it->get_class() );
		}
		LP_Debug::rollbackTransaction();
		learn_press_send_json( $response );
	}

	public static function get_quiz() {
		self::verify();
		$user     = learn_press_get_current_user();
		$courseId = LP_Request::get_int( 'courseId' );
		$itemId   = LP_Request::get_int( 'itemId' );
		$course   = learn_press_get_course( $courseId );
		$quiz     = $course->get_item( $itemId );
		$quizData = self::$course_data->get_item( $itemId );


		$json = array(
			'checkCount'      => $user->can_check_answer( $quiz->get_id() ),
			'hintCount'       => $user->can_hint_answer( $quiz->get_id() ),
			'currentQuestion' => $user->get_current_question( $quiz->get_id(), get_the_ID() ),
			'questions'       => array(),
			'status'          => $quizData->get_status()
		);

		$questions = $quiz->get_question_ids();
		global $post;
		foreach ( $questions as $question_id ) {
			$question = learn_press_get_question( $question_id );
			$checked  = $user->has_checked_answer( $question->get_id(), $quiz->get_id(), get_the_ID() );
			$hinted   = $user->has_hinted_answer( $question->get_id(), $quiz->get_id(), get_the_ID() );

			$answered    = false;
			$course_data = $user->get_course_data( $course->get_id() );

			if ( $user_quiz = $course_data->get_item_quiz( $quiz->get_id() ) ) {
				$answered = $user_quiz->get_question_answer( $question->get_id() );
				$question->show_correct_answers( $user->has_checked_answer( $question->get_id(), $quiz->get_id(), $course->get_id() ) ? 'yes' : false );
				$question->disable_answers( $user_quiz->get_status() == 'completed' ? 'yes' : false );
			}

			ob_start();
			$post = $question->get_post();
			setup_postdata( $post );
			echo get_the_content();
			$question->render( $answered );
			$questionContent = ob_get_clean();

			$json['questions'][] = array(
				'id'             => absint( $question_id ),
				'checked'        => $checked,
				'hinted'         => $hinted,
				'explanation'    => $checked ? $question->get_explanation() : '',
				'hint'           => $checked || $hinted ? $question->get_hint() : '',
				'hasExplanation' => ! ! $question->get_explanation(),
				'hasHint'        => ! ! $question->get_hint(),
				'permalink'      => $quiz->get_question_link( $question_id ),
				'userAnswers'    => false,
				'content'        => $questionContent
			);
		}

		wp_reset_postdata();

		learn_press_send_json( $json );
	}

	public static function verify() {
		$courseId = LP_Request::get_int( 'courseId' );
		$user     = learn_press_get_current_user();

		if ( ! wp_verify_nonce( LP_Request::get_string( 'namespace' ), 'lp-' . $user->get_id() . '-' . $courseId ) ) {
			die( - 1 );
		}

		$course = learn_press_get_course( $courseId );

		if ( ! $course ) {
			die( - 2 );
		}

		self::$user        = $user;
		self::$course_data = $user->get_course_data( $courseId );
	}

}

LP_User_Item_Ajax::init();