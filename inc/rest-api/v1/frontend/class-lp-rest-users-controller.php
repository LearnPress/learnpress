<?php

use LearnPress\Models\UserItemMeta\UserQuizMetaModel;
use LearnPress\Models\UserItems\UserQuizModel;

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
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'start-quiz'   => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'start_quiz' ),
					// 'permission_callback' => array( $this, 'check_admin_permission' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_item_endpoint_args(),
				),
			),
			'submit-quiz'  => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'submit_quiz' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_item_endpoint_args(),
				),
			),
			'hint-answer'  => array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'hint_answer' ),
					// 'permission_callback' => array( $this, 'check_admin_permission' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_item_endpoint_args(),
				),
			),
			'check-answer' => array(
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
				'description'       => __( 'The ID of the course item object.', 'learnpress' ),
				'type'              => 'int',
				'validate_callback' => array( $this, 'validate_arg' ),
				'required'          => true,
			),
			'course_id' => array(
				'description'       => __( 'The ID of the course object.', 'learnpress' ),
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

	public function check_admin_permission() {
		return LP_Abstract_API::check_admin_permission();
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
				throw new Exception( __( 'The course is invalid!', 'learnpress' ) );
			}

			if ( ! $quiz ) {
				throw new Exception( __( 'The quiz is invalid!', 'learnpress' ) );
			}

			$quiz->set_course( $course );

			do_action( 'learn-press/user/before/start-quiz', $item_id, $course_id, $user_id );

			// For no required enroll course
			if ( $user->is_guest() && $course->is_no_required_enroll() ) {
				$no_required_enroll = new LP_Course_No_Required_Enroll( $course );
				$response           = $no_required_enroll->guest_start_quiz( $quiz );

				return rest_ensure_response( $response );
			}

			/**
			 * Require enroll course
			 *
			 * @var UserQuizModel $user_quiz
			 */
			$checked_questions         = [];
			$hinted_questions          = [];
			$retaken_count             = 0;
			$attempts                  = [];
			$user_item_id              = 0;
			$filter_user_quiz          = new LP_User_Items_Filter();
			$filter_user_quiz->user_id = $user_id;
			$filter_user_quiz->item_id = $item_id;
			$filter_user_quiz->ref_id  = $course_id;
			$user_quiz_exists          = UserQuizModel::get_user_item_model_from_db( $filter_user_quiz, true );
			if ( $user_quiz_exists instanceof UserQuizModel
				&& $user_quiz_exists->status === LP_ITEM_COMPLETED ) {
				/**
				 * @uses LP_User::retake_quiz
				 */
				//$user_quiz = $user->retake_quiz( $item_id, $course_id, true );
				$user_quiz = $user_quiz_exists;
				$user_quiz->retake();
				$results['answered'] = []; // Reset answered for js
				$retaken_count       = $user_quiz->get_retaken_count();
				$attempts            = $user_quiz->get_attempts();
				//$checked_questions   = $user_quiz->get_checked_questions();
				//$hinted_questions    = $user_quiz->get_hint_questions();
			} else { // Create new user quiz and insert to database.
				/**
				 * @uses LP_User::start_quiz
				 */
				//$user_quiz                = $user->start_quiz( $item_id, $course_id, true );
				$user_quiz_new          = new UserQuizModel();
				$user_quiz_new->user_id = $user_id;
				$user_quiz_new->item_id = $item_id;
				$user_quiz_new->ref_id  = $course_id;
				$user_quiz_new->start_quiz();
				$user_quiz = $user_quiz_new;
			}

			$user_item_id = $user_quiz->get_user_item_id();

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
			$time_remaining      = $user_quiz->get_timestamp_remaining();

			$questions = learn_press_rest_prepare_user_questions(
				$question_ids,
				array(
					'instant_check'       => $show_check,
					//'quiz_status'         => $status,
					//'checked_questions'   => $checked_questions,
					//'hinted_questions'    => $hinted_questions,
					'answered'            => [],
					'show_correct_review' => $show_correct_review,
				)
			);

			$results['question_ids'] = $question_ids;
			$results['questions']    = $questions;
			$results['total_time']   = $time_remaining;
			$results['duration']     = $duration ? $duration->get() : false;
			$results['status']       = $status; // Must be started
			$results['retaken']      = $retaken_count;
			$results['attempts']     = $attempts;
			$results['user_item_id'] = $user_item_id;
			$response['status']      = 'success';
			$response['results']     = $results;
		} catch ( Throwable $e ) {
			$response['message'] = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Submit quiz answer
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @editor tungnx
	 * @modify 4.1.4.1
	 * @version 1.0.2
	 */
	public function submit_quiz( WP_REST_Request $request ) {
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
				throw new Exception( 'The course is invalid!' );
			}
			// Use for Review Quiz.
			$quiz = learn_press_get_quiz( $item_id );
			if ( ! $quiz ) {
				throw new Exception( __( 'The quiz is invalid!', 'learnpress' ) );
			}
			$quiz->set_course( $course );

			// Course is no required enroll
			if ( $course->is_no_required_enroll() ) {
				$no_required_enroll = new LP_Course_No_Required_Enroll( $course );

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

				return rest_ensure_response( $response );
			}

			$user_course = $user->get_course_data( $course_id );

			// Course required enroll
			if ( ! $user_course ) {
				throw new Exception( 'User not enrolled course!' );
			}

			/**
			 * @var LP_User_Item_Quiz $user_quiz
			 */
			$user_quiz = $user_course->get_item( $item_id );
			if ( ! $user_quiz ) {
				throw new Exception();
			}

			// For case save result when check instant answer
			$result_instant_check = LP_User_Items_Result_DB::instance()->get_result( $user_quiz->get_user_item_id() );
			if ( $result_instant_check ) {
				foreach ( $result_instant_check['questions'] as $question_answer_id => $question_answer ) {
					if ( ! empty( $question_answer['answered'] ) ) {
						$answered[ $question_answer_id ] = $question_answer['answered'];
					}
				}
			}

			// Set end time.
			$start_time = $user_quiz->get_start_time()->getTimestamp();
			$user_quiz->set_end_time( $start_time + $time_spend );

			// Calculate quiz result and save.
			$result = $user_quiz->calculate_quiz_result( $answered );
			// Save
			LP_User_Items_Result_DB::instance()->update( $user_quiz->get_user_item_id(), wp_json_encode( $result ) );

			if ( $result['pass'] ) {
				$user_quiz->set_graduation( LP_COURSE_GRADUATION_PASSED );
			} else {
				$user_quiz->set_graduation( LP_COURSE_GRADUATION_FAILED );
			}

			$user_quiz->complete();

			do_action( 'learn-press/user/quiz-finished', $item_id, $course_id, $user_id, $user_quiz );

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
					throw new Exception( 'The user is invalid', 'learnrpess' );
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
}
