<?php

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
	 *
	 */
	public static function init() {
		$actions = array(
			'start-quiz'        => 'start_quiz',
			'finish-quiz'       => 'finish_quiz',
			'retake-quiz'       => 'retake_quiz',
			'check-question'    => 'check_question',
			'get-question-hint' => 'get_question_hint'
		);
		foreach ( $actions as $k => $v ) {
			LP_Request_Handler::register_ajax( $k, array( __CLASS__, $v ) );
		}
		add_action( 'learn_press_after_single_quiz_summary', array( __CLASS__, 'output_quiz_params' ) );
	}

	public static function output_quiz_params( $quiz ) {
		$json = array(
			'id'                => $quiz->id,
			'show_hint'         => $quiz->show_hint,
			'show_check_answer' => $quiz->show_check_answer,
			'duration'          => $quiz->duration,
			'questions'         => $quiz->questions
		);
		$json = apply_filters( 'learn_press_single_quiz_params', $json );
		?>
		<script type="text/javascript">
			var LP_Quiz_Params = <?php echo wp_json_encode( $json ); ?>;
			jQuery(function () {
				LP._initQuiz(LP_Quiz_Params);
			});
		</script>
		<?php ;
	}

	public static function yyy() {
		$user = learn_press_get_current_user();
		if ( $user instanceof LP_User_Guest ) {
			$expire  = get_user_meta( $user->id, '_lp_anonymous_user_expire', true );
			$current = time();
			if ( ( $current - ( $expire - 60 ) ) < 10 ) {
				update_user_meta( $user->id, '_lp_anonymous_user_expire', $current + 60 );
			}
		}
	}

	public static function _delete_anonymous_users() {
		global $wpdb;
		$sql = $wpdb->prepare( "
		DELETE a, b FROM $wpdb->users a, $wpdb->usermeta b
		WHERE a.ID = b.user_id
		AND b.meta_key = %s
		AND b.meta_value < %d
		", '_lp_anonymous_user_expire', time() );
		//$wpdb->query( $sql );
	}

	public static function xxx( $start, $quiz_id, $user_id ) {
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

	/**
	 * Start quiz
	 */
	public static function start_quiz() {

		self::_verify_nonce( __FUNCTION__ );

		$course_id = learn_press_get_request( 'course_id' );
		$quiz_id   = learn_press_get_request( 'quiz_id' );
		$user      = learn_press_get_current_user();
		$quiz      = LP_Quiz::get_quiz( $quiz_id );

		$response = array( 'result' => 'success' );

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

		if ( $user->has_quiz_status( array( 'started', 'completed' ), $quiz_id, $course_id ) ) {
			learn_press_send_json(
				array(
					'result'   => 'error',
					'message'  => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'Error while starting quiz', 'learnpress' ) ),
					'data'     => $user->get_quiz_result(),
					'redirect' => $quiz->permalink
				)
			);
		} else {
			$result = $user->start_quiz( $quiz_id, $course_id );
			if ( $result ) {
				$course             = learn_press_get_course( $course_id );
				LP()->course        = $course;
				LP()->user          = $user;
				$response['status'] = $result->status;

				// update cache
				LP_Cache::set_quiz_status( $user->id . '-' . $course->id . '-' . $quiz_id, $result->status );

				ob_start();
				learn_press_get_template( 'single-course/content-item-lp_quiz.php' );
				$response['html'] = ob_get_clean();
			} else {
				$response['result'] = 'error';
			}
		}
		learn_press_send_json( $response );
	}

	public static function finish_quiz() {
		self::_verify_nonce( __FUNCTION__ );

		$course_id = learn_press_get_request( 'course_id' );
		$quiz_id   = learn_press_get_request( 'quiz_id' );
		$user      = learn_press_get_current_user();
		$quiz      = LP_Quiz::get_quiz( $quiz_id );

		$response = array( 'result' => 'success' );

		if ( $user->has_quiz_status( array( 'completed' ), $quiz_id, $course_id ) ) {
			learn_press_send_json(
				array(
					'result'   => 'error',
					'message'  => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You have already completed quiz', 'learnpress' ) ),
					'redirect' => $quiz->permalink
				)
			);
		} else {
			$answers = learn_press_get_request( 'answers' );
			if ( $answers ) {
				$history = $user->get_quiz_results( $quiz_id, $course_id, true );
				if ( !$history ) {
					learn_press_send_json(
						array(
							'result'  => 'error',
							'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'Error while finish quiz', 'learnpress' ) )
						)
					);
				}
				$update_answers = (array) $history->question_answers;
				foreach ( $answers as $question_id => $data ) {
					settype( $data, 'array' );
					if ( array_key_exists( 'learn-press-question-' . $question_id, $data ) ) {
						$update_answers[$question_id] = $data['learn-press-question-' . $question_id];
					}
				}
				learn_press_update_user_item_meta( $history->history_id, '_quiz_question_answers', $update_answers );
			}

			$result = $user->finish_quiz( $quiz_id, $course_id );
			LP_Cache::flush();

			if ( $result ) {
				$course             = learn_press_get_course( $course_id );
				$response['status'] = $result->status;
				// update cache
				LP_Cache::set_quiz_status( $user->id . '-' . $course->id . '-' . $quiz_id, $result->status );
				$response['html'] = learn_press_get_template_content( 'single-course/content-item-lp_quiz.php' );
			} else {
				$response['result'] = 'error';
			}
		}

		learn_press_send_json( $response );
	}

	public static function retake_quiz() {

		self::_verify_nonce( __FUNCTION__ );

		$course_id = learn_press_get_request( 'course_id' );
		$quiz_id   = learn_press_get_request( 'quiz_id' );
		$user      = learn_press_get_current_user();
		$quiz      = LP_Quiz::get_quiz( $quiz_id );

		$response = array( 'result' => 'success' );

		if ( !$user->has_quiz_status( array( 'completed' ), $quiz_id, $course_id ) || !$user->can_retake_quiz( $quiz_id, $course_id ) ) {
			learn_press_send_json(
				array(
					'result'   => 'error',
					'message'  => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You can not retake quiz', 'learnpress' ) ),
					'redirect' => $quiz->permalink
				)
			);
		} else {
			$result = $user->retake_quiz( $quiz_id, $course_id );
			LP_Cache::flush();
			if ( $result ) {
				$course = learn_press_get_course( $course_id );
				//LP()->course        = $course;
				//LP()->user          = $user;
				$response['status'] = $result->status;

				// update cache
				LP_Cache::set_quiz_status( $user->id . '-' . $course->id . '-' . $quiz_id, $result->status );
				$response['html'] = learn_press_get_template_content( 'single-course/content-item-lp_quiz.php' );
			} else {
				$response['result'] = 'error';
			}
		}

		learn_press_send_json( $response );
	}

	public static function check_question() {
		self::_verify_nonce( __FUNCTION__ );

		$user_id     = learn_press_get_request( 'user_id' );
		$quiz_id     = learn_press_get_request( 'quiz_id' );
		$course_id   = learn_press_get_request( 'course_id' );
		$question_id = learn_press_get_request( 'question_id' );
		$user        = learn_press_get_user( $user_id );
		$quiz        = LP_Quiz::get_quiz( $quiz_id );
		LP()->set_object( 'quiz', $quiz, true );
		if ( !$user->has_checked_answer( $question_id, $quiz_id, $course_id ) ) {
			$answer      = learn_press_get_request( 'question_answer' );
			$user_answer = false;
			$history     = $user->get_quiz_results( $quiz_id, $course_id, true );
			if ( is_string( $answer ) && strpos( $answer, '=' ) !== false ) {
				parse_str( $answer, $answer );
			}
			if ( $answer && $history ) {
				if ( array_key_exists( 'learn-press-question-' . $question_id, $answer ) ) {
					$user_answer = $answer['learn-press-question-' . $question_id];
				}

				if ( $user_answer ) {
					$update_answers               = (array) $history->question_answers;
					$update_answers[$question_id] = $user_answer;
					learn_press_update_user_item_meta( $history->history_id, '_quiz_question_answers', $update_answers );
				}
			}
			if ( $history ) {
				$checked = learn_press_get_user_item_meta( $history->history_id, '_quiz_question_checked', true );
				if ( !$checked ) {
					$checked = array();
				} else {
					$checked = (array) $checked;
				}
				if ( !in_array( $question_id, $checked ) ) {
					$checked[] = $question_id;
					learn_press_update_user_item_meta( $history->history_id, '_quiz_question_checked', $checked );
				}
			}

			LP_Cache::flush();
		}
		$question = LP_Question_Factory::get_question( $question_id );
		$question->render( array( 'quiz_id' => $quiz_id, 'course_id' => $course_id, 'force' => true ) );
		exit();
		////$question_answer = LP_Question_Factory::save_question_if_needed( $question_id, $quiz_id, $user_id );
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

	public static function get_question_hint() {
		$check = self::_verify_nonce( __FUNCTION__ );
		list( $course_id, $quiz_id, $user_id, $security ) = array_values( $check );
		$question_id                = learn_press_get_request( 'question_id', 0 );
		$quiz                       = LP_Quiz::get_quiz( $quiz_id );
		$quiz->current_question     = LP_Question_Factory::get_question( $question_id );
		LP()->global['course']      = LP_Course::get_course( $course_id );
		LP()->global['course-item'] = $quiz;
		$_REQUEST['html']           = learn_press_get_template_content( 'question/hint.php' );
		learn_press_send_json( $_REQUEST );
		die();
	}

	/**
	 * Verify quiz action security
	 *
	 * @param string $action
	 * @param mixed  $error_message
	 *
	 * @return mixed
	 */
	public static function _verify_nonce( $action, $error_message = false ) {
		$action    = str_replace( '_', '-', $action );
		$course_id = learn_press_get_request( 'course_id', 0 );
		$quiz_id   = learn_press_get_request( 'quiz_id', 0 );
		$security  = learn_press_get_request( 'security', 0 );
		$user_id   = learn_press_get_current_user_id();

		$quiz = LP_Quiz::get_quiz( $quiz_id );
		if ( !$quiz->id || !wp_verify_nonce( $security, "{$action}-{$user_id}-{$course_id}-{$quiz_id}" ) ) {
			if ( $error_message === false ) {
				$error_message = array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'Please contact site\'s administrator for more information.', 'learnpress' ) );
			}

			learn_press_send_json(
				array(
					'result'  => 'error',
					'message' => $error_message
				)
			);
		}
		return array(
			'course_id' => $course_id,
			'quiz_id'   => $quiz_id,
			'user_id'   => $user_id,
			'security'  => $security,
		);
	}
}

LP_Quiz_Factory::init();