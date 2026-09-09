<?php

use LearnPress\Filters\UserItemsFilter;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Quiz\QuizQuestionModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserItems\UserQuizModel;
use LearnPress\Models\UserModel;

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
	 * @version 1.0.5
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

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( esc_html__( 'The course is invalid', 'learnpress' ) );
			}

			$quizPostModel = $courseModel->get_item_model( $item_id, LP_QUIZ_CPT );
			if ( ! $quizPostModel ) {
				throw new Exception( esc_html__( 'The quiz is invalid', 'learnpress' ) );
			}

			$quiz->set_course( $course );

			do_action( 'learn-press/user/before/start-quiz', $item_id, $course_id, $user_id );

			$link = LP_Helper::get_link_no_cache( $courseModel->get_item_link( $item_id, LP_QUIZ_CPT ) );

			// For no required enroll course (no need login)
			if ( $user->is_guest() && $course->is_no_required_enroll() ) {
				$no_required_enroll       = new LP_Course_No_Required_Enroll( $course );
				$response                 = $no_required_enroll->guest_start_quiz( $quiz );
				$response['redirect_url'] = $link;

				return rest_ensure_response( $response );
			}

			/**
			 * Require enroll course
			 *
			 * @var UserQuizModel $user_quiz
			 */
			$retaken_count             = 0;
			$attempts                  = [];
			$filter_user_quiz          = new UserItemsFilter();
			$filter_user_quiz->user_id = $user_id;
			$filter_user_quiz->item_id = $item_id;
			$filter_user_quiz->ref_id  = $course_id;
			$user_quiz_exists          = UserQuizModel::get_user_item_model_from_db( $filter_user_quiz );
			if ( $user_quiz_exists instanceof UserQuizModel
				&& $user_quiz_exists->status === LP_ITEM_COMPLETED ) {
				$user_quiz = $user_quiz_exists;
				$user_quiz->retake();
				$results['answered'] = []; // Reset answered for js
				$retaken_count       = $user_quiz->get_retaken_count();
				$attempts            = $user_quiz->get_attempts();
			} else { // Create new user quiz and insert to database.
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
			$time_remaining      = $user_quiz->get_time_remaining();

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

			$results['question_ids']  = $question_ids;
			$results['questions']     = $questions;
			$results['total_time']    = $time_remaining;
			$results['duration']      = $duration ? $duration->get() : false;
			$results['status']        = $status; // Must be started
			$results['retaken']       = $retaken_count;
			$results['attempts']      = $attempts;
			$results['user_item_id']  = $user_item_id;
			$response['status']       = 'success';
			$response['results']      = $results;
			$response['redirect_url'] = $link;
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
	 * @version 1.0.4
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
			$userModel  = UserModel::find( $user_id, true );

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel instanceof CourseModel ) {
				throw new Exception( esc_html__( 'The course is invalid', 'learnpress' ) );
			}

			$quizPostModel = $courseModel->get_item_model( $item_id, LP_QUIZ_CPT );
			if ( ! $quizPostModel instanceof QuizPostModel ) {
				throw new Exception( esc_html__( 'The quiz is invalid', 'learnpress' ) );
			}

			// Course is no required enroll (no need login)
			if ( ! $userModel && $courseModel->has_no_enroll_requirement() ) {
				$userQuizModelFake            = new UserQuizModel();
				$userQuizModelFake->item_id   = $item_id;
				$userQuizModelFake->item_type = LP_QUIZ_CPT;
				$userQuizModelFake->ref_id    = $course_id;
				$userQuizModelFake->ref_type  = LP_COURSE_CPT;
				$result                       = $userQuizModelFake->calculate_quiz_result( $answered );

				// Set time spent
				$interval             = gmdate( 'H:i:s', $time_spend );
				$result['time_spend'] = $interval;
				// End

				$result['status'] = UserItemModel::STATUS_COMPLETED;
				//$result['answered']  = $result['questions'];
				$result['attempts']  = [];
				$result['results']   = $result;
				$response['status']  = 'success';
				$response['results'] = $result;

				return rest_ensure_response( $response );
			}

			if ( ! $userModel instanceof UserModel ) {
				throw new Exception( esc_html__( 'The user is invalid', 'learnpress' ) );
			}

			$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $userCourseModel instanceof UserCourseModel ) {
				throw new Exception( esc_html__( 'User not enrolled course!', 'learnpress' ) );
			}

			$userQuizModel = $userCourseModel->get_item_attend( $item_id, LP_QUIZ_CPT );
			if ( ! $userQuizModel instanceof UserQuizModel ) {
				throw new Exception( esc_html__( 'User quiz is invalid!', 'learnpress' ) );
			}

			$data_send           = [
				'answered' => $answered,
				'time_spend' => $time_spend,
			];
			$result              = $userQuizModel->finish_quiz( $data_send );
			$response['status']  = 'success';
			$response['results'] = $result;
		} catch ( Throwable $e ) {
			$response['message'] = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Check answer
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @since 4.1.4.1
	 * @version 1.0.3
	 */
	public function check_answer( WP_REST_Request $request ) {
		$response = array(
			'status'  => 'error',
			'message' => '',
		);

		try {
			$user_id     = get_current_user_id();
			$item_id     = $request['item_id'] ?? 0;
			$course_id   = $request['course_id'] ?? 0;
			$question_id = $request['question_id'] ?? 0;
			$answered    = $request['answered'] ?? '';
			$userModel   = UserModel::find( $user_id, true );
			$checked     = [];

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel instanceof CourseModel ) {
				throw new Exception( esc_html__( 'The course is invalid', 'learnpress' ) );
			}

			$quizPostModel = $courseModel->get_item_model( $item_id, LP_QUIZ_CPT );
			if ( ! $quizPostModel instanceof QuizPostModel ) {
				throw new Exception( esc_html__( 'The quiz is invalid', 'learnpress' ) );
			}

			// Check question has in quiz.
			$quizQuestionModel = QuizQuestionModel::find( $item_id, $question_id );
			if ( ! $quizQuestionModel ) {
				throw new Exception( esc_html__( 'The quiz question is invalid', 'learnpress' ) );
			}

			$questionModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionModel ) {
				throw new Exception( esc_html__( 'The question is invalid', 'learnpress' ) );
			}

			// For case user not login.
			if ( ! $userModel && $courseModel->has_no_enroll_requirement() ) {
				$question            = QuestionPostModel::find( $question_id, true );
				$checked             = $question->check( $answered );
				$checked['answered'] = $answered;
			} else {
				if ( ! $userModel instanceof UserModel ) {
					throw new Exception( esc_html__( 'The user is invalid', 'learnpress' ) );
				}

				$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
				if ( ! $userCourseModel instanceof UserCourseModel ) {
					throw new Exception( esc_html__( 'User not enrolled course!', 'learnpress' ) );
				}

				$userQuizModel = $userCourseModel->get_item_attend( $item_id, LP_QUIZ_CPT );
				if ( ! $userQuizModel instanceof UserQuizModel ) {
					throw new Exception( esc_html__( 'User quiz is invalid!', 'learnpress' ) );
				}

				$checked = $userQuizModel->instant_check_question( $question_id, $answered );
			}

			$response['explanation'] = $questionModel->get_explanation();
			$response['options']     = learn_press_get_question_options_for_js(
				$questionModel,
				array(
					'include_is_true' => true,
					'answer'          => $answered,
				)
			);
			$response['result']      = $checked;
			$response['status']      = 'success';
		} catch ( Throwable $e ) {
			$response['message'] = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}
}
