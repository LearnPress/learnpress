<?php

/**
 * Class LP_REST_Users_Controller
 *
 * @since 3.3.0
 */
class LP_REST_Users_Controller extends LP_Abstract_REST_Controller {
	/**
	 * @var LP_User
	 */
	protected $user = null;

	/**
	 * @var LP_Course
	 */
	protected $course = null;

	/**
	 * @var LP_Course_Item|LP_Quiz|LP_Lesson
	 */
	protected $item = null;

	/**
	 * @var LP_User_Item_Course
	 */
	protected $user_course = null;

	/**
	 * @var LP_User_Item|LP_User_Item_Quiz
	 */
	protected $user_item = null;

	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'users';
		parent::__construct();

		// tungnx - 4.1.4.1 - comment - not use - run wrong
		// add_filter( 'rest_pre_dispatch', array( $this, 'rest_pre_dispatch' ), 10, 3 );
	}

	/**
	 * Init data prepares for callbacks of rest
	 *
	 * @param                 $null
	 * @param WP_REST_Server  $server
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use - run wrong
	 */
	/*public function rest_pre_dispatch( $null, $server, $request ) {
		$user_id   = get_current_user_id();
		$item_id   = $request['item_id'] ?? 0;
		$course_id = $request['course_id'] ?? 0;

		$this->user   = learn_press_get_user( $user_id );
		$this->course = learn_press_get_course( $course_id );

		if ( $this->course ) {
			$this->item = $this->course->get_item( $item_id );
		}

		if ( is_user_logged_in() ) {
			$this->user_course = $this->user->get_course_data( $course_id );

			if ( $this->user_course ) {
				$this->user_item = $this->user_course->get_item( $item_id );
			}
		}

		return $null;
	}*/

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			''               => array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			),

			'(?P<key>[\w]+)' => array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'enroll_course' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),

			'start-quiz'     => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'start_quiz' ),
					// 'permission_callback' => array( $this, 'check_admin_permission' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_item_endpoint_args(),
				),
			),

			'submit-quiz'    => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					// 'callback' => array( $this, 'submit_quiz' ),
					'callback'            => array( $this, 'submit_quiz_new' ),
					// 'permission_callback' => array( $this, 'check_admin_permission' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_item_endpoint_args(),
				),
			),

			'hint-answer'    => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'hint_answer' ),
					// 'permission_callback' => array( $this, 'check_admin_permission' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_item_endpoint_args(),
				),
			),

			'check-answer'   => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'check_answer' ),
					// 'permission_callback' => array( $this, 'check_admin_permission' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_item_endpoint_args(),
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Get args for user item endpoints.
	 *
	 * @return array
	 */
	public function get_item_endpoint_args() {
		return array(
			'item_id'   => array(
				'description'       => __( 'The ID of course item object.', 'learnpress' ),
				'type'              => 'int',
				'validate_callback' => array( $this, 'validate_arg' ),
				'required'          => true,
			),
			'course_id' => array(
				'description'       => __( 'The ID of course object.', 'learnpress' ),
				'type'              => 'int',
				'validate_callback' => array( $this, 'validate_arg' ),
				'required'          => true,
			),
		);
	}

	/**
	 * Validation callback to verify rest args.
	 *
	 * @param mixed           $value
	 * @param WP_REST_Request $request
	 * @param string          $param
	 *
	 * @return bool|WP_Error
	 */
	public function validate_arg( $value, $request, $param ) {
		$attributes = $request->get_attributes();

		if ( ! isset( $attributes['args'][ $param ] ) ) {
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%s was not registered as a request argument.', 'learnpress' ), $param ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Sanitize callback.
	 *
	 * @param mixed           $value
	 * @param WP_REST_Request $request
	 * @param string          $param
	 *
	 * @return mixed
	 */
	public function sanitize_arg( $value, $request, $param ) {
		switch ( $param ) {
			case 'user_id':
			case 'item_id':
			case 'course_id':
				return absint( $value );
		}

		return $value;
	}

	public function check_admin_permission() {
		return LP_REST_Authentication::check_admin_permission();
	}

	/**
	 * Enroll an user to a course.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function enroll_course( $request ) {
		$response = array(
			$_REQUEST,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * User starts a quiz.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @editor tungnx
	 * @version 1.0.1
	 * @sicne 4.0.0
	 * @return WP_REST_Response
	 */
	public function start_quiz( WP_REST_Request $request ): WP_REST_Response {
		$user_id   = get_current_user_id();
		$item_id   = $request['item_id'] ?? 0;
		$course_id = $request['course_id'] ?? 0;
		$results   = array(
			'question_ids' => array(),
			'questions'    => array(),
			'total_time'   => 0,
		);
		$response  = array(
			'status'  => 'error',
			'message' => '',
		);

		try {
			$user   = learn_press_get_user( $user_id );
			$course = learn_press_get_course( $course_id );
			$quiz   = learn_press_get_quiz( $item_id );

			if ( ! $course ) {
				throw new Exception( __( 'Course is invalid!', 'learnpress' ) );
			}

			if ( ! $quiz ) {
				throw new Exception( __( 'Quiz is invalid!', 'learnpress' ) );
			}

			// For no required enroll course
			if ( $user->is_guest() && $course->is_no_required_enroll() ) {
				if ( $quiz->get_retake_count() >= 0 ) {
					//learn_press_remove_cookie( 'quiz_submit_status_' . $course_id . '_' . $item_id . '' );
				}
				$no_required_enroll = new LP_Course_No_Required_Enroll( $course );
				$response           = $no_required_enroll->guest_start_quiz( $quiz );

				return rest_ensure_response( $response );
			}

			// Require enroll course
			if ( $user->has_started_quiz( $item_id, $course_id ) ) {
				/**
				 * @var LP_User_Item_Quiz $user_quiz
				 */
				$user_quiz           = $user->retake_quiz( $item_id, $course_id, true );
				$results['answered'] = []; // Reset answered before on js
			} else {
				/**
				 * @var LP_User_Item_Quiz $user_quiz
				 */
				$user_quiz = $user->start_quiz( $item_id, $course_id, true );
			}

			if ( is_wp_error( $user_quiz ) ) {
				throw new Exception( $user_quiz->get_error_message() );
			}

			/**
			 * Clear cache result quiz
			 * Cache set on @see LP_User_Item_Quiz::get_results
			 */
			$lp_quiz_cache = LP_Quiz_Cache::instance();
			$key_cache     = sprintf( '%d/user/%d/course/%d', $item_id, $user_id, $course_id );
			$lp_quiz_cache->clear( $key_cache );
			// End

			$show_check          = $quiz->get_instant_check();
			$duration            = $quiz->get_duration();
			$show_correct_review = $quiz->get_show_correct_review();
			$question_ids        = $quiz->get_question_ids();
			$status              = $user_quiz->get_status();
			$checked_questions   = $user_quiz->get_checked_questions();
			$hinted_questions    = $user_quiz->get_hint_questions();
			$time_remaining      = $user_quiz->get_timestamp_remaining();

			$questions = learn_press_rest_prepare_user_questions(
				$question_ids,
				array(
					'instant_check'       => $show_check,
					'quiz_status'         => $status,
					'checked_questions'   => $checked_questions,
					'hinted_questions'    => $hinted_questions,
					'answered'            => [],
					'show_correct_review' => $show_correct_review,
				)
			);

			$results['question_ids'] = $question_ids;
			$results['questions']    = $questions;
			$results['total_time']   = $time_remaining;
			$results['duration']     = $duration ? $duration->get() : false;
			$results['status']       = $status; // Must be started
			$results['retaken']      = absint( $user_quiz->get_retaken_count() );
			$results['attempts']     = $user_quiz->get_attempts();
			$results['user_item_id'] = $user_quiz->get_user_item_id();
			$response['status']      = 'success';
			$response['results']     = $results;
		} catch ( Throwable $e ) {
			$response['message'] = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	/*public function submit_quiz_bk( $request ) {
		$user_id     = get_current_user_id();
		$item_id     = $request['item_id'];
		$course_id   = $request['course_id'];
		$answered    = $request['answered'];
		$user        = learn_press_get_user( $user_id );
		$course      = learn_press_get_course( $course_id );
		$user_course = $user->get_course_data( $course_id );
		$results     = array();
		$user_quiz   = false;

		if ( $course->is_no_required_enroll() ) {
			$no_required_enroll = new LP_Course_No_Required_Enroll();
			// Course is no required enroll
			$success  = true;
			$response = array(
				'success' => $success,
				'message' => __( 'Success!', 'learnpress' ),
			);
			if ( $success ) {
				// Use for Review Quiz.
				$quiz = learn_press_get_quiz( $item_id );
				if ( get_post_meta( $item_id, '_lp_review', true ) === 'yes' ) {

					$question_ids = $quiz->get_question_ids();
					if ( $question_ids ) {
						foreach ( $question_ids as $id ) {
							$question = learn_press_get_question( $id );

							$results['questions'][ $id ] = array(
								'explanation' => $question->get_explanation(),
								'options'     => learn_press_get_question_options_for_js(
									$question,
									array(
										'include_is_true' => get_post_meta( $item_id, '_lp_show_correct_review', true ) === 'yes',
										'answer'          => isset( $answered[ $id ] ) ? $answered[ $id ] : '',
									)
								),
							);
						}
					}
				}

				$results['answered'] = $no_required_enroll->guest_get_quiz_answered( $request['answered'], $item_id );
				$results['status']   = 'completed';
				$results['results']  = $no_required_enroll->guest_quiz_get_results( '', false, $item_id, $request['answered'], $course_id );
				$results['attempts'] = $no_required_enroll->guest_quiz_get_attempts( $item_id, $request['answered'], $course_id );
				$response['results'] = $results;

				learn_press_setcookie( 'quiz_submit_status_' . $course_id . '_' . $item_id . '', 'completed', time() + ( 7 * DAY_IN_SECONDS ), false );
			}
		} else {
			// Course required enroll
			if ( $user_course ) {
				$user_quiz = $user_course->get_item( $item_id );

				if ( $user_quiz ) {
					$user_quiz->add_question_answer( $answered );
				}
			}

			$finished = $user->finish_quiz( $item_id, $course_id, true );
			$success  = ! is_wp_error( $finished );

			$response = array(
				'success' => $success,
				'message' => ! $success ? $finished->get_error_message() : __( 'Success!', 'learnpress' ),
			);

			if ( $success ) {
				$user_quiz    = $user_course->get_item( $item_id );
				$quiz_results = $user_quiz->get_results( '' );
				$attempts     = $user_quiz->get_attempts();
				// Use for Review Quiz.
				if ( get_post_meta( $item_id, '_lp_review', true ) === 'yes' ) {
					$question_ids = $quiz_results->getQuestions( 'ids' );
					if ( $question_ids ) {
						foreach ( $question_ids as $id ) {
							$question = learn_press_get_question( $id );

							$results['questions'][ $id ] = array(
								'explanation' => $question->get_explanation(),
								'options'     => learn_press_get_question_options_for_js(
									$question,
									array(
										'include_is_true' => get_post_meta( $item_id, '_lp_show_correct_review', true ) === 'yes',
										'answer'          => isset( $answered[ $id ] ) ? $answered[ $id ] : '',
									)
								),
							);
						}
					}
				}

				$results['answered'] = $quiz_results->getQuestions();
				$results['status']   = $quiz_results->get( 'status' );
				$results['results']  = $quiz_results->get();
				$results['attempts'] = $attempts;
				$response['results'] = $results;

			}
		}
		return rest_ensure_response( $response );
	}*/

	/**
	 * Submit quiz answer
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 * @editor tungnx
	 * @modify 4.1.4.1
	 * @version 1.0.1
	 */
	public function submit_quiz_new( WP_REST_Request $request ) {
		//$response = new LP_REST_Response();
		$response = array(
			'status'  => 'error',
			'message' => '',
		);

		try {
			$user_id    = get_current_user_id();
			$item_id    = $request['item_id'] ?? 0;
			$course_id  = $request['course_id'] ?? 0;
			$answered   = $request['answered'] ?? [];
			$time_spend = $request['time_spend'] ?? 0;
			$user       = learn_press_get_user( $user_id );
			$course     = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( 'Course is invalid!' );
			}

			// Course is no required enroll
			if ( $course->is_no_required_enroll() ) {
				$no_required_enroll = new LP_Course_No_Required_Enroll( $course );

				// Use for Review Quiz.
				$quiz = learn_press_get_quiz( $item_id );

				if ( ! $quiz ) {
					throw new Exception( __( 'Quiz is invalid!', 'learnpress' ) );
				}

				$result = $no_required_enroll->get_result_quiz( $quiz, $answered );

				// Set time spent
				$interval             = new LP_Duration( $time_spend );
				$interval             = $interval->to_timer();
				$result['time_spend'] = $interval;
				// End

				$result['status'] = LP_ITEM_COMPLETED;
				//$result['answered']  = $result['questions'];
				$result['attempts']  = [];
				$result['results']   = $result;
				$response['status']  = 'success';
				$response['results'] = $result;

				//learn_press_setcookie( 'quiz_submit_status_' . $course_id . '_' . $item_id . '', 'completed', time() + ( 7 * DAY_IN_SECONDS ), false );

				return rest_ensure_response( $response );
			}

			$user_course = $user->get_course_data( $course_id );

			// Course required enroll
			if ( ! $user_course ) {
				throw new Exception( 'User not enrolled course!' );
			}

			$user_quiz = $user_course->get_item( $item_id );

			if ( ! $user_quiz ) {
				throw new Exception();
			}

			$end_time = gmdate( 'Y-m-d H:i:s', strtotime( $user_quiz->get_start_time( 'mysql' ) . " + $time_spend second" ) );
			$user_quiz->set_end_time( $end_time );

			// For case save result when check instant answer
			$result_instant_check = LP_User_Items_Result_DB::instance()->get_result( $user_quiz->get_user_item_id() );
			if ( $result_instant_check ) {
				foreach ( $result_instant_check['questions'] as $question_answer_id => $question_answer ) {
					if ( ! empty( $question_answer['answered'] ) ) {
						$answered[ $question_answer_id ] = $question_answer['answered'];
					}
				}
			}

			// Calculate quiz result and save
			$result = $user_quiz->calculate_quiz_result( $answered );
			// Save
			LP_User_Items_Result_DB::instance()->update( $user_quiz->get_user_item_id(), wp_json_encode( $result ) );

			if ( $result['pass'] ) {
				$user_quiz->set_graduation( LP_COURSE_GRADUATION_PASSED );
			} else {
				$user_quiz->set_graduation( LP_COURSE_GRADUATION_FAILED );
			}

			$user_quiz->complete();

			do_action( 'learn-press/user/quiz-finished', $item_id, $course_id, $user_id );

			$result['status']    = $user_quiz->get_status(); // Must be completed
			$result['attempts']  = $user_quiz->get_attempts();
			$result['answered']  = $result['questions'];
			$result['results']   = $result;
			$response['status']  = 'success';
			$response['results'] = $result;
		} catch ( Throwable $e ) {
			$response['message'] = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Hint the question and response hint content.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function hint_answer( $request ) {
		$question_id = $request['question_id'];
		$hint_count  = $this->user_item->hint( $question_id );
		$question    = learn_press_get_question( $question_id );

		$response = array(
			'hint' => $question->get_hint(),
		);

		return rest_ensure_response( $response );
	}

	public function check_answer( $request ) {
		$response = array(
			'status'  => 'error',
			'message' => '',
		);

		try {
			$question_id = $request['question_id'] ?? 0;
			$answered    = $request['answered'] ?? '';
			$course_id   = $request['course_id'] ?? 0;
			$quiz_id     = $request['item_id'] ?? 0;
			$course      = learn_press_get_course( $course_id );
			$checked     = [];

			if ( $course->is_no_required_enroll() ) {
				$no_required_enroll = new LP_Course_No_Required_Enroll( $course );
				$checked            = $no_required_enroll->guest_check_question( $question_id, $answered );
			} else {
				$user = learn_press_get_current_user();
				if ( $user->is_guest() ) {
					throw new Exception( 'User is invalid!', 'learnrpess' );
				}

				$user_course = $user->get_course_data( $course_id );
				if ( ! $user_course ) {
					throw new Exception( 'User\'s course no data!', 'learnrpess' );
				}

				$user_quiz = $user_course->get_item( $quiz_id );
				if ( ! $user_quiz ) {
					throw new Exception( 'User\'s quiz no data!', 'learnrpess' );
				}

				$checked = $user_quiz->instant_check_question( $question_id, $answered );
			}

			$question                = learn_press_get_question( $question_id );
			$response['explanation'] = $question->get_explanation();
			$response['options']     = learn_press_get_question_options_for_js( $question, array( 'answer' => $answered ) );
			$response['result']      = $checked;
			$response['status']      = 'success';
		} catch ( Throwable $e ) {
			$response['message'] = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$settings = LP()->settings();
		$response = array(
			'result' => $settings->get(),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$settings = LP()->settings();
		$response = array(
			'result' => $settings->get( $request['key'] ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_item( $request ) {
		$response = array();
		$settings = LP()->settings();
		$option   = $settings->get( $request['key'] );

		$settings->update( $request['key'], $request['data'] );
		$new_option = $settings->get( $request['key'] );
		$success    = maybe_serialize( $option ) !== maybe_serialize( $new_option );

		$response['success'] = $success;
		$response['result']  = $success ? $new_option : $option;

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$response = array();

		return rest_ensure_response( $response );
	}
}
