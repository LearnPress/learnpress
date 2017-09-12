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
			'redo-quiz'         => 'redo_quiz',
			'nav-question-quiz' => 'nav_question',
			'complete-quiz'     => 'finish_quiz',

			////
			'retake-quiz'       => 'retake_quiz',
			'check-question'    => 'check_question',
			'fetch-question'    => 'fetch_question',
			'get-question-hint' => 'get_question_hint'
		);
		foreach ( $actions as $k => $v ) {
			LP_Request_Handler::register_ajax( $k, array( __CLASS__, $v ) );
			LP_Request_Handler::register( "lp-{$k}", array( __CLASS__, $v ) );
		}
		add_action( 'learn_press_after_single_quiz_summary', array( __CLASS__, 'output_quiz_params' ) );
		add_action( 'delete_post', array( __CLASS__, 'delete_quiz' ), 10, 2 );
		add_action( 'edit_form_after_editor', array( __CLASS__, 'admin_template' ), - 990 );
		add_filter( 'learn-press/question/admin-option-template-args', array(
			__CLASS__,
			'question_icon_class'
		), 10, 2 );
		add_action( 'learn-press/quiz-started', array( __CLASS__, 'update_user_current_question' ), 10, 3 );
	}

	public static function nav_question() {
		$check = self::maybe_save_questions( 'nav-question' );
	}

	/**
	 * Callback function for starting quiz
	 */
	public static function start_quiz() {

		try {

			// Actually, no save question here. Just check nonce here.
			$check = self::maybe_save_questions( 'start' );

			// PHP Exception
			if ( true !== $check ) {
				throw $check;
			}

			$course_id = LP_Request::get_int( 'course-id' );
			$quiz_id   = LP_Request::get_int( 'quiz-id' );
			$nonce     = LP_Request::get_string( 'start-quiz-nonce' );
			$result    = array( 'result' => 'failure' );

			$user   = learn_press_get_current_user();
			$quiz   = learn_press_get_quiz( $quiz_id );
			$course = learn_press_get_course( $course_id );


			$data = $user->start_quiz( $quiz_id, $course_id, true );
			if ( is_wp_error( $data ) ) {
				$result['message'] = $data->get_error_message();
			} else {
				$redirect = $quiz->get_question_link( learn_press_get_user_item_meta( $data['user_item_id'], '_current_question' ), true );

				$result['result']   = 'success';
				$result['redirect'] = apply_filters( 'learn-press/quiz-started/redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
			}
//				if ( $result ) {
//					//learn_press_setup_user_course_data( $user->get_id(), $course_id );
//					$response['status']        = $result->status;
//					$response['html']          = file_get_contents( learn_press_get_current_url() );// learn_press_get_template_content( 'single-course/content-item-lp_quiz.php' );
//					$response['course_result'] = self::get_course_info( $user->get_id(), $course_id );
//				} else {
//					$response['result'] = 'error';
//				}

		}
		catch ( Exception $ex ) {
			$result['message'] = $ex->getMessage();
			$result['result']  = 'failure';
		}

		if ( learn_press_is_ajax() ) {
			learn_press_send_json( $result );
		}

		if ( ! empty( $result['redirect'] ) ) {
			if ( ! empty( $result['message'] ) ) {
				learn_press_add_message( $result['message'] );
			}
			wp_redirect( $result['redirect'] );
			exit();
		}
	}

	/**
	 * Callback function for retaking quiz
	 */
	public static function redo_quiz() {
		try {
			// Actually, no need to save question here. Just check wp nonce in this case.
			$check = self::maybe_save_questions( 'redo' );

			// PHP Exception
			if ( true !== $check ) {
				throw $check;
			}

			$result    = array( 'result' => 'failure' );
			$course_id = LP_Request::get_int( 'course-id' );
			$quiz_id   = LP_Request::get_int( 'quiz-id' );
			$user      = learn_press_get_current_user();
			$quiz      = learn_press_get_quiz( $quiz_id );

			$data = $user->retake_quiz( $quiz_id, $course_id, true );

			if ( is_wp_error( $data ) ) {
				throw new Exception( $data->get_error_message(), $data->get_error_code() );
			} else {
				$redirect = $quiz->get_question_link( learn_press_get_user_item_meta( $data['user_item_id'], '_current_question' ), true );

				$result['result']   = 'success';
				$result['redirect'] = apply_filters( 'learn-press/quiz-started/redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
				$result['data']     = $data;
			}
		}
		catch ( Exception $ex ) {
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
	 * Callback for finishing quiz
	 */
	public static function finish_quiz() {

		try {
			$check = self::maybe_save_questions( 'finish' );

			// PHP Exception
			if ( true !== $check ) {
				throw $check;
			}

			$user   = LP_Global::user();
			$result = array( 'result' => 'failure' );

			$course_id = LP_Request::get_int( 'course-id' );
			$quiz_id   = LP_Request::get_int( 'quiz-id' );

			$data = $user->finish_quiz( $quiz_id, $course_id );

			if ( is_wp_error( $data ) ) {
				throw new Exception( $data->get_error_message(), $data->get_error_code() );
			} else {
				if ( $course = learn_press_get_course( $course_id ) ) {
					$quiz     = $course->get_item( $quiz_id );
					$redirect = $quiz->get_question_link( learn_press_get_user_item_meta( $data['user_item_id'], '_current_question' ), true );

					$result['result']   = 'success';
					$result['redirect'] = apply_filters( 'learn-press/quiz/completed-redirect', $redirect, $quiz_id, $course_id, $user->get_id() );
					$result['data']     = $data;
				}
			}
		}
		catch ( Exception $ex ) {
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

		// Redirecting...
		if ( ! empty( $result['redirect'] ) ) {
			wp_redirect( $result['redirect'] );
			exit();
		}
	}

	/**
	 * Verify nonce and/or save question answers when posting.
	 *
	 * @param string $action
	 * @param string $nonce
	 *
	 * @return bool|Exception
	 *
	 * @since 3.x.x
	 */
	public static function maybe_save_questions( $action = '', $nonce = '' ) {
		try {
			if ( ! LP_Nonce_Helper::verify_quiz_action( $action, $nonce ) ) {
				throw new Exception( __( 'Something went wrong!', 'learnpress' ), LP_INVALID_REQUEST );
			}

			if ( $questions = self::get_answers_posted() ) {

				$user   = learn_press_get_current_user();
				$course = learn_press_get_course( LP_Request::get_int( 'course-id' ) );
				$quiz   = learn_press_get_quiz( LP_Request::get_int( 'quiz-id' ) );

				$course_data = $user->get_course_data( $course->get_id() );
				$quiz_data   = $course_data->get_item_quiz( $quiz->get_id() );

				$quiz_data->add_question_answer( $questions );
				$quiz_data->update();
			}
		}
		catch ( Exception $ex ) {
			return $ex;
		}

		return true;
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
	 * @return array|bool
	 */
	public static function get_answers_posted() {
		$questions = array();
		try {
			$post_data = stripslashes_deep( $_REQUEST );
			if ( empty( $post_data['question-data'] ) ) {
				return false;
			}

			$data = @json_decode( $post_data['question-data'] );
			settype( $data, 'array' );

			foreach ( $data as $k => $v ) {
				$id = absint( str_replace( 'learn-press-question-', '', $k ) );
				if ( $id ) {
					$questions[ $id ] = $v;
				}
			}
		}
		catch ( Exception $ex ) {
		}

		return $questions;
	}

	public static function question_icon_class( $data, $type ) {
		switch ( $type ) {
			case 'true_or_false':
				$data['icon-class'] = 'xxxx';
				break;
			case 'single_choice':
				$data['icon-class'] = 'fa fa-dot-circle-o';
				break;
			case 'multiple_choice':
				$data['icon-class'] = 'fa fa-check-circle';
				break;
		}

		return $data;
	}

	public static function admin_template() {
		add_filter( 'learn-press/question/none/admin-option-template-args', array(
			__CLASS__,
			'question_template_js'
		), 10, 2 );
		echo '<script type="text/ng-template" id="tmpl-quiz-question">';

		$none = new LP_Question_None();

		$none->admin_interface();
		/*
		learn_press_admin_view( 'meta-boxes/question/base-options', array(
			'question' => new LP_Question_None()
		) );*/
		echo '</script>';
		remove_filter( 'learn-press/question/none/admin-option-template-args', array(
			__CLASS__,
			'question_template_js'
		), 10, 2 );

		learn_press_admin_view( 'meta-boxes/html-search-items' );
	}

	public static function question_template_js( $args, $type ) {
		$args = array(
			'id'             => '{{questionData.id}}',
			'type'           => '{{questionData.type}}',
			'title'          => '{{questionData.title}}',
			'answer_options' => array(
				'value'   => '',
				'text'    => '',
				'is_true' => ''
			)
		);

		return $args;
	}

	public static function delete_quiz( $post_id, $force = false ) {
		global $wpdb;
		if ( 'lp_quiz' === get_post_type( $post_id ) ) {
			$sql = 'DELETE FROM `' . $wpdb->prefix . 'learnpress_quiz_questions` WHERE `quiz_id` = ' . $post_id;
			$wpdb->query( $sql );
		}
	}

	public static function output_quiz_params( $quiz ) {
		$json = array(
			'id'                => $quiz->id,
			'show_hint'         => $quiz->show_hint,
			'show_check_answer' => $quiz->show_check_answer,
			'duration'          => $quiz->get_duration(),
			'questions'         => $quiz->get_questions()
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
			$expire  = get_user_meta( $user->get_id(), '_lp_anonymous_user_expire', true );
			$current = time();
			if ( ( $current - ( $expire - 60 ) ) < 10 ) {
				update_user_meta( $user->get_id(), '_lp_anonymous_user_expire', $current + 60 );
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



	//////////////////////////////////////////////////
	/// //////////////////////////////////////////////
	///

	public static function retake_quiz() {

		self::_verify_nonce( __FUNCTION__ );

		$course_id = learn_press_get_request( 'course_id' );
		$quiz_id   = learn_press_get_request( 'quiz_id' );
		$user      = learn_press_get_current_user();

		$is_completed = $user->has_quiz_status( array( 'completed' ), $quiz_id, $course_id );
		$can_finish   = $user->can_retake_quiz( $quiz_id, $course_id );
		if ( ! $is_completed || ! $can_finish ) {
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
		if ( ! $user->has_checked_answer( $question_id, $quiz_id, $course_id ) ) {
			$answer      = learn_press_get_request( 'question_answer' );
			$user_answer = false;
			$history     = $user->get_quiz_results( $quiz_id, $course_id, true );
			if ( is_string( $answer ) && strpos( $answer, '=' ) !== false ) {
				parse_str( $answer, $answer );
			}
			if ( $answer && $history ) {
				if ( array_key_exists( 'learn-press-question-' . $question_id, $answer ) ) {
					$user_answer = $answer[ 'learn-press-question-' . $question_id ];
				}

				if ( $user_answer ) {
					$update_answers                 = (array) $history->question_answers;
					$update_answers[ $question_id ] = $user_answer;
					learn_press_update_user_item_meta( $history->history_id, 'question_answers', $update_answers );
				}
			}
			if ( $history ) {
				$checked = learn_press_get_user_item_meta( $history->history_id, 'question_checked', true );
				if ( ! $checked ) {
					$checked = array();
				} else {
					$checked = (array) $checked;
				}
				if ( ! in_array( $question_id, $checked ) ) {
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

		if ( ! empty( $_REQUEST['lp-ajax'] ) && $_REQUEST['lp-ajax'] == 'fetch-question' ) {
			$question_id = ! empty( $_REQUEST['id'] ) ? $_REQUEST['id'] : $question_id;
			learn_press_update_user_item_meta( $history->history_id, 'lp_current_question_after_close', $question_id );
		}

		if ( ! empty( $_REQUEST['lp-update-current-question'] ) ) {
			$current_id = absint( $_REQUEST['id'] );
			learn_press_update_user_item_meta( $history->history_id, 'lp_current_question_after_close', $current_id );
		}

		if ( ! empty( $_REQUEST['lp-current-question'] ) ) {
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
		if ( ! $quiz->id || ! wp_verify_nonce( $security, "{$action}-{$user_id}-{$course_id}-{$quiz_id}" ) ) {
			if ( $error_message === false ) {
				$error_message = array(
					'title'   => __( 'Error', 'learnpress' ),
					'message' => sprintf( __( 'Action %s failed! Please contact site\'s administrator for more information.', 'learnpress' ), $action )
				);
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

	public static function get_quiz( $the_quiz ) {
		static $quizzes = array();
		$the_id = 0;
		if ( $the_quiz instanceof LP_Quiz ) {
			$the_id = $the_quiz->get_id();
		} elseif ( $the_quiz instanceof WP_Post ) {
			$the_id = $the_quiz->ID;
		} elseif ( isset( $the_quiz->ID ) ) {
			$the_id = $the_quiz->ID;
		}
		if ( empty( $quizzes[ $the_id ] ) ) {
			if ( $the_quiz instanceof LP_Quiz ) {
				$quizzes[ $the_id ] = $the_quiz;
			} else {
				$quizzes[ $the_id ] = new LP_Quiz( $the_quiz );
			}
		}else{
        }

		return $quizzes[ $the_id ];
	}
}

return;
$s = microtime(true);
for($i  =0; $i<1000;$i++){
    new LP_Lesson(765);
}
echo microtime(true)-$s;
$s = microtime(true);
for($i  =0; $i<1000;$i++){
	new LP_Quiz(20);
}
echo microtime(true)-$s;
die();
LP_Quiz_Factory::init();
