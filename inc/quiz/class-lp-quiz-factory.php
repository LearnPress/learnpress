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
			'fetch-question'    => 'fetch_question',
			'get-question-hint' => 'get_question_hint'
		);
		foreach ( $actions as $k => $v ) {
			LP_Request_Handler::register_ajax( $k, array( __CLASS__, $v ) );
		}
		add_action( 'learn_press_after_single_quiz_summary', array( __CLASS__, 'output_quiz_params' ) );
		add_action( 'delete_post', array( __CLASS__, 'delete_quiz' ), 10, 2 );
	}

	public static function delete_quiz( $post_id, $force=false ) {
		global $wpdb;
		if('lp_quiz' === get_post_type($post_id) ){
			$sql = 'DELETE FROM `'.$wpdb->prefix.'learnpress_quiz_questions` WHERE `quiz_id` = '.$post_id;
			$wpdb->query($sql);
		}
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
					'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You need to login to take this quiz.', 'learnpress' ) )
				)
			);
		}

		if ( learn_press_get_request( 'preview' ) == 'true' ) {
			learn_press_send_json(
				array(
					'result'  => 'error',
					'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'You can not start a quiz in preview mode.', 'learnpress' ) )
				)
			);
		}

		if ( $user->has_quiz_status( array( 'started', 'completed' ), $quiz_id, $course_id ) ) {
			$response['html'] = learn_press_get_template_content( 'single-course/content-item-lp_quiz.php' );
		} else {
			$result = $user->start_quiz( $quiz_id, $course_id );
			if ( $result ) {
				learn_press_setup_user_course_data( $user->id, $course_id );
				$response['status']        = $result->status;
				$response['html']          = file_get_contents( learn_press_get_current_url() );// learn_press_get_template_content( 'single-course/content-item-lp_quiz.php' );
				$response['course_result'] = self::get_course_info( $user->id, $course_id );
			} else {
				$response['result'] = 'error';
			}
		}
		wp_redirect( add_query_arg( array( 'done-action' => 'start-quiz' ), learn_press_get_current_url() ) );
		exit();
	}

	public static function finish_quiz() {
		self::_verify_nonce( __FUNCTION__ );
		$course_id = learn_press_get_request( 'course_id' );
		$quiz_id   = learn_press_get_request( 'quiz_id' );
		$user      = learn_press_get_current_user();
		$course    = learn_press_get_course( $course_id );
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
			//if ( $answers ) {
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
			$keys           = array_keys( $_POST );
			$update         = false;
			foreach ( $keys as $key ) {
				if ( !preg_match( '!^learn-press-question-([0-9]+)$!', $key, $matches ) ) {
					continue;
				}
				$question_id                  = absint( $matches[1] );
				$update_answers[$question_id] = $_POST[$key];
				$update                       = true;
			}
			if ( $update ) {
				learn_press_update_user_item_meta( $history->history_id, 'question_answers', $update_answers );
			}
			$auto = learn_press_get_request( 'auto_finish' );
			if ( $auto ) {
				$args = array( 'auto_finish' => 'yes' );
			} else {
				$args = array();
			}
			/*
				foreach ( $answers as $question_id => $data ) {
					settype( $data, 'array' );
					if ( array_key_exists( 'learn-press-question-' . $question_id, $data ) ) {
						$update_answers[$question_id] = $data['learn-press-question-' . $question_id];
					}
				}
				print_r($update_answers);*/
			///}

			$result = $user->finish_quiz( $quiz_id, $course_id, $args );
			if ( $result ) {
				if ( !empty( $args['auto_finish'] ) ) {
					learn_press_add_message( __( 'Your quiz has finished automatically', 'learnpress' ) );
				} else {
					learn_press_add_message( __( 'You have finished quiz', 'learnpress' ) );
				}
			} else {
				learn_press_add_message( __( 'Finish quiz failed', 'learnpress' ) );
			}
		}
		wp_redirect( add_query_arg( array( 'content-item-only' => 'yes', 'done-action' => 'finish-quiz' ), $course->get_item_link( $quiz_id ) ) );
		exit();
	}

	public static function retake_quiz() {

		self::_verify_nonce( __FUNCTION__ );

		$course_id = learn_press_get_request( 'course_id' );
		$quiz_id   = learn_press_get_request( 'quiz_id' );
		$user      = learn_press_get_current_user();

		$is_completed = $user->has_quiz_status( array( 'completed' ), $quiz_id, $course_id );
		$can_finish   = $user->can_retake_quiz( $quiz_id, $course_id );
		if ( !$is_completed || !$can_finish ) {
			learn_press_add_message( __( 'Sorry! You can\'t retake quiz', 'learnpress' ) );
		} else {
			$result = $user->retake_quiz( $quiz_id, $course_id );
			if ( $result ) {
				/*LP_Cache::flush( 'user-completed-items', 'quiz-results', 'user-quiz-history', 'course-item-statuses', 'quiz-params' );
				//LP_Cache::set_quiz_status( $user->id . '-' . $course_id . '-' . $quiz_id, 'started' );
				learn_press_setup_user_course_data( $user->id, $course_id );
				LP()->global['course-item'] = LP_Quiz::get_quiz( $quiz_id );
				$response['status']         = $result->status;
				$response['course_result']  = self::get_course_info( $user->id, $course_id );
				$response['html']           = array(
					'content'  => learn_press_get_template_content( 'single-course/content-item-lp_quiz.php' ),
					'progress' => learn_press_get_template_content( 'single-course/progress.php' ),
					'buttons'  => learn_press_get_template_content( 'single-course/buttons.php' )
				);*/
				learn_press_add_message( __( 'You have retaken quiz', 'learnpress' ) );
			} else {
				//$response['result'] = 'error';
				learn_press_add_message( __( 'Retake quiz failed', 'learnpress' ) );

			}
		}
		wp_redirect( add_query_arg( 'done-action', 'retake-quiz', learn_press_get_current_url() ) );
		exit();
		learn_press_send_json( $response );
	}

	public static function check_question() {
		self::_verify_nonce( __FUNCTION__ );

		$user_id     = learn_press_get_request( 'user_id' );
		$quiz_id     = learn_press_get_request( 'quiz_id' );
		$course_id   = learn_press_get_request( 'course_id' );
		$question_id = learn_press_get_request( 'question_id' );
		$user        = learn_press_get_current_user( $user_id );
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
					learn_press_update_user_item_meta( $history->history_id, 'question_answers', $update_answers );
				}
			}
			if ( $history ) {
				$checked = learn_press_get_user_item_meta( $history->history_id, 'question_checked', true );
				if ( !$checked ) {
					$checked = array();
				} else {
					$checked = (array) $checked;
				}
				if ( !in_array( $question_id, $checked ) ) {
					$checked[] = $question_id;
					learn_press_update_user_item_meta( $history->history_id, 'question_checked', $checked );
				}
			}
		} else {
		}
		learn_press_setup_user_course_data( $user_id, $course_id );
		$question = LP_Question_Factory::get_question( $question_id );
		$question->render( array( 'quiz_id' => $quiz_id, 'course_id' => $course_id, 'force' => true ) );
		exit();
	}

	public static function fetch_question() {
		add_filter( 'learn_press_user_current_quiz_question', array( __CLASS__, '_current_question' ), 100, 4 );
	}

	public static function _current_question( $question_id, $quiz_id, $course_id, $user_id ) {
		$user    = learn_press_get_current_user();
		$history = $user->get_quiz_results( $quiz_id, $course_id, true );

		if ( !empty( $_REQUEST['lp-ajax'] ) && $_REQUEST['lp-ajax'] == 'fetch-question' ) {
			$question_id = !empty( $_REQUEST['id'] ) ? $_REQUEST['id'] : $question_id;
			learn_press_update_user_item_meta( $history->history_id, 'lp_current_question_after_close', $question_id );
		}

		if ( !empty( $_REQUEST['lp-update-current-question'] ) ) {
			$current_id = absint( $_REQUEST['id'] );
			learn_press_update_user_item_meta( $history->history_id, 'lp_current_question_after_close', $current_id );
		}

		if ( !empty( $_REQUEST['lp-current-question'] ) ) {
			$current_id = absint( $_REQUEST['lp-current-question'] );
			learn_press_update_user_item_meta( $history->history_id, 'lp_current_question_after_close', $current_id );
		}

		return $question_id;
	}

	public static function get_question_hint() {
		$check = self::_verify_nonce( __FUNCTION__ );
		list( $course_id, $quiz_id, $user_id, $security ) = array_values( $check );
		$question_id                = learn_press_get_request( 'question_id', 0 );
		$quiz                       = LP_Quiz::get_quiz( $quiz_id );
		$quiz->current_question     = LP_Question_Factory::get_question( $question_id );
		LP()->global['course']      = LP_Course::get_course( $course_id );
		LP()->global['course-item'] = $quiz;
		$_REQUEST['html']           = learn_press_get_template_content( 'content-question/hint.php' );
		learn_press_send_json( $_REQUEST );
		die();
	}

	public static function get_course_info( $user_id, $course_id ) {
		$user = learn_press_get_current_user( $user_id );
		return $user->get_course_info2( $course_id );
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
		do_action( 'learn_press_before_verify_quiz_action_none' );
		$action    = str_replace( '_', '-', $action );
		$course_id = learn_press_get_request( 'course_id', 0 );
		$quiz_id   = learn_press_get_request( 'quiz_id', 0 );
		$security  = learn_press_get_request( 'security', 0 );
		$user_id   = learn_press_get_current_user_id();

		$quiz = LP_Quiz::get_quiz( $quiz_id );
		if ( !$quiz->id || !wp_verify_nonce( $security, "{$action}-{$user_id}-{$course_id}-{$quiz_id}" ) ) {
			if ( $error_message === false ) {
				$error_message = array( 'title' => __( 'Error', 'learnpress' ), 'message' => sprintf( __( 'Action %s failed! Please contact site\'s administrator for more information.', 'learnpress' ), $action ) );
			}
			if ( learn_press_is_ajax() ) {
				learn_press_send_json(
					array(
						'result'  => 'error',
						'message' => $error_message
					)
				);
			} else {
				learn_press_add_message( $error_message['message'] );
				wp_redirect( learn_press_get_current_url() );
				exit();
			}
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
