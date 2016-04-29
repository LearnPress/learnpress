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
		/*
		add_action( 'learn_press_before_user_start_quiz', array( __CLASS__, 'xxx' ), 5, 3 );
		add_action( 'init', array( __CLASS__, 'yyy' ) );
		add_action( 'init', array( __CLASS__, '_delete_anonymous_users' ) );*/
	}

	static function yyy() {
		$user = learn_press_get_current_user();
		if ( $user instanceof LP_User_Guest ) {
			$expire  = get_user_meta( $user->id, '_lp_anonymous_user_expire', true );
			$current = time();
			if ( ( $current - ( $expire - 60 ) ) < 10 ) {
				update_user_meta( $user->id, '_lp_anonymous_user_expire', $current + 60 );
			}
		}
	}

	static function _delete_anonymous_users() {
		global $wpdb;
		$sql = $wpdb->prepare( "
		DELETE a, b FROM $wpdb->users a, $wpdb->usermeta b
		WHERE a.ID = b.user_id
		AND b.meta_key = %s
		AND b.meta_value < %d
		", '_lp_anonymous_user_expire', time() );
		//$wpdb->query( $sql );
	}

	static function xxx( $start, $quiz_id, $user_id ) {
		$start  = false;
		$x      = 60;
		$expire = $x + time();
		$user   = get_user_by( 'id', $user_id );
		if ( $user ) {
			if ( $expire_time = get_user_meta( $user_id, '_lp_anonymous_user_expire', true ) ) {
				$current_time = time();
				if ( $expire_time - $current_time <= 0 ) {
					update_user_meta( $user_id, '_lp_anonymous_user_expire', $expire );
				}
			}
			return $start;
		}
		$new_user_id = wp_create_user( uniqid( 'user_' . time() ), '12345' );
		if ( $new_user_id ) {
			global $wpdb;
			if ( $wpdb->update( $wpdb->users, array( 'ID' => $user_id ), array( 'ID' => $new_user_id ) ) ) {
				update_user_meta( $user_id, '_lp_anonymous_user_expire', $expire );
			}
		}
		return $start;
	}

	static function start_quiz() {
		$quiz_id  = learn_press_get_request( 'quiz_id' );
		$response = array( 'result' => 'success' );
		$quiz     = LP_Quiz::get_quiz( $quiz_id );

		self::_verify_nonce();

		LP()->set_object( 'quiz', $quiz, true );
		$user = learn_press_get_current_user();

		if ( $quiz->is_require_enrollment() && $user->is( 'guest' ) ) {
			learn_press_send_json(
				array(
					'result'  => 'error',
					'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You need to login to do this quiz.', 'learnpress' ) )
				)
			);
		}

		if ( learn_press_get_request( 'preview' ) == 'true' ) {
			learn_press_send_json(
				array(
					'result'  => 'error',
					'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You can not start quiz in preview mode.', 'learnpress' ) )
				)
			);
		}
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
				$question         = LP_Question_Factory::get_question( $current_question );
				if ( $question ) {
					$quiz->current_question = $question;
					ob_start();
					$question->render();
					$content = ob_get_clean();
				} else {
					$content = '';
				}
				learn_press_send_json(
					array(
						'result'   => $result === false ? 'fail' : 'success',
						'message'  => $result === false ? array(
							'title'   => __( 'Error', 'learnpress' ),
							'message' => __( 'Start quiz failed', 'learnpress' )
						) : '',
						'data'     => $result,
						'question' =>
							array(
								'id'        => $current_question,
								'permalink' => learn_press_get_user_question_url( $quiz_id, $current_question ),
								'content'   => $content
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
		LP()->set_object( 'quiz', $quiz, true );

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

		LP()->set_object( 'quiz', LP_Quiz::get_quiz( $quiz_id ), true );

		if ( $user->get_quiz_status( $quiz_id ) == 'completed' ) {
			$response = $user->retake_quiz( $quiz_id );
			learn_press_send_json( $response );
		}

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
		LP()->set_object( 'quiz', $quiz, true );

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