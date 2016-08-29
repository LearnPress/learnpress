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
		/*
		add_action( 'learn_press_before_user_start_quiz', array( __CLASS__, 'xxx' ), 5, 3 );
		add_action( 'init', array( __CLASS__, 'yyy' ) );
		add_action( 'init', array( __CLASS__, '_delete_anonymous_users' ) );*/

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
		/*
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
		);*/
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
					//'data'     => $user->get_quiz_result(),
					'redirect' => $quiz->permalink
				)
			);
		} else {
			$result = $user->finish_quiz( $quiz_id, $course_id );
			LP_Cache::flush();
			if ( $result ) {
				$course = learn_press_get_course( $course_id );
				//LP()->course        = $course;
				//LP()->user          = $user;
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
					//'data'     => $user->get_quiz_result(),
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

				ob_start();
				learn_press_get_template( 'single-course/content-item-lp_quiz.php' );
				$response['html'] = ob_get_clean();
			} else {
				$response['result'] = 'error';
			}
		}

		learn_press_send_json( $response );

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

	public static function check_question() {
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
	 * @param $action
	 *
	 * @return mixed
	 */
	public static function _verify_nonce( $action ) {
		$action    = str_replace( '_', '-', $action );
		$course_id = learn_press_get_request( 'course_id', 0 );
		$quiz_id   = learn_press_get_request( 'quiz_id', 0 );
		$security  = learn_press_get_request( 'security', 0 );
		$user_id   = learn_press_get_current_user_id();

		$quiz = LP_Quiz::get_quiz( $quiz_id );
		if ( !$quiz->id || !wp_verify_nonce( $security, "{$action}-{$user_id}-{$course_id}-{$quiz_id}" ) ) {
			learn_press_send_json(
				array(
					'result'  => 'error',
					'message' => array( 'title' => __( 'Error', 'learnpress' ), 'message' => __( 'Please contact site\'s administrator for more information.', 'learnpress' ) )
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