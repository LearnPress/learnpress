<?php

class LP_Quiz_Factory {
	static $user = null;
	static $quiz = null;

	static function init() {
		$actions = array(
			'start-quiz'     => 'start_quiz',
			'finish-quiz'    => 'finish_quiz',
			'retake-quiz'    => 'retake_quiz',
			'check-question' => 'check_question'
		);
		foreach ( $actions as $k => $v ) {
			LP_Request_Handler::register_ajax( $k, array( __CLASS__, $v ) );
		}
	}

	static function start_quiz() {
		$quiz_id  = learn_press_get_request( 'quiz_id' );
		$response = array( 'result' => 'success' );
		$quiz     = LP_Quiz::get_quiz( $quiz_id );

		self::_verify_nonce();

		$user = learn_press_get_current_user();
		if ( $quiz->is_require_enrollment() && $user->is( 'guest' ) ) {
			learn_press_send_json(
				array(
					'result'  => 'error',
					'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You need to login to do this quiz.', 'learnpress' ) )
				)
			);
		}
		$user->set_quiz( $quiz );
		switch ( strtolower( $user->get_quiz_status( $quiz_id ) ) ) {
			case 'completed':
				learn_press_send_json(
					array(
						'result'   => 'error',
						'message'  => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You have completed this quiz', 'learnpress' ) ),
						'data'     => $user->get_quiz_result(),
						'redirect' => $quiz->permalink
					)
				);
				break;
			case 'started':
				learn_press_send_json(
					array(
						'result'   => 'error',
						'message'  => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You have started this quiz', 'learnpress' ) ),
						'data'     => array(
							'status' => $user->get_quiz_status()
						),
						'redirect' => $quiz->permalink
					)
				);
				break;
			default:
				$result           = $user->start_quiz();
				$current_question = !empty( $result['current_question'] ) ? $result['current_question'] : $user->get_current_question_id( $quiz_id );
				learn_press_send_json(
					array(
						'result'   => 'success',
						'data'     => $result,
						'question' =>
							array(
								'id'        => $current_question,
								'permalink' => learn_press_get_user_question_url( $quiz_id, $current_question ),
								'content'   => LP_Question_Factory::fetch_question_content( $current_question )
							)
					)
				);
		}
		learn_press_send_json( $response );
	}

	static function finish_quiz() {
		$quiz_id = learn_press_get_request( 'quiz_id' );
		$quiz    = LP_Quiz::get_quiz( $quiz_id );
		$user    = learn_press_get_current_user();
		self::_verify_nonce();
		if ( $user->get_quiz_status( $quiz->id ) != 'completed' ) {
			$user->finish_quiz( $quiz_id );
			$response = array(
				'redirect' => get_the_permalink( $quiz_id )
			);
			learn_press_send_json( $response );
		}
	}

	static function retake_quiz() {
		$quiz_id = learn_press_get_request( 'quiz_id' );
		$user    = learn_press_get_current_user();
		self::_verify_nonce();
		if ( $user->get_quiz_status( $quiz_id ) == 'completed' ) {
			$response = $user->retake_quiz( $quiz_id );
			echo 'xxxxxxxxxxxxx';
			learn_press_send_json( $response );

		}
		echo 'yyyyyyyyyy';
		learn_press_send_json(
			array(
				'result'  => 'error',
				'message' => array(
					'title'   => __( 'Error', 'learnpress' ),
					'message' => __( 'You can not retake this quiz', 'learnpress' )
				)
			)
		);
	}

	static function check_question() {
		self::_verify_nonce();
		$user_id     = learn_press_get_request( 'user_id' );
		$quiz_id     = learn_press_get_request( 'quiz_id' );
		$question_id = learn_press_get_request( 'question_id' );
		$user        = learn_press_get_user( $user_id );
		$quiz        = LP_Quiz::get_quiz( $quiz_id );

		$question_answer = LP_Question_Factory::save_question_if_needed( $question_id, $quiz_id, $user_id );
		if ( !$quiz || !$quiz->id ) {
			return;
		}
		if ( $quiz->show_check_answer != 'yes' ) {
			return;
		}
		if ( $quiz ) {
			$quiz->check_question( $question_id, $user );
		}
		if ( $question_id && $question = LP_Question_Factory::get_question( $question_id ) ) {
			$include = apply_filters( 'learn_press_check_question_answers_include_fields', null, $question_id, $quiz_id, $user_id );
			$exclude = apply_filters( 'learn_press_check_question_answers_exclude_fields', array( 'text' ), $question_id, $quiz_id, $user_id );
			$checked = $question->get_answers( $include, $exclude );
			if ( $checked ) {
				$checked = array_values( $checked );
			}
		} else {
			$checked = false;
		}
		$checked = apply_filters( 'learn_press_check_question_answers', $checked, $question_id, $quiz_id, $user_id );

		$response = array(
			'result'   => 'success',
			'checked'  => $checked,
			'answered' => $question_answer

		);
		learn_press_send_json( $response );
	}

	static function _verify_nonce() {
		$quiz_id  = learn_press_get_request( 'quiz_id' );
		$user_id  = learn_press_get_current_user_id();
		$security = learn_press_get_request( 'nonce' );
		$quiz     = LP_Quiz::get_quiz( $quiz_id );
		if ( !wp_verify_nonce( $security, 'learn-press-quiz-action-' . $quiz_id . '-' . $user_id ) || !$quiz->id ) {
			learn_press_send_json(
				array(
					'result'  => 'error',
					'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'Please contact site\'s administrator for more information.', 'learnpress' ) )
				)
			);
		}
	}
}

LP_Quiz_Factory::init();