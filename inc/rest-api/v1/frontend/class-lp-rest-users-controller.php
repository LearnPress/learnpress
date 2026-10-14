<?php

use LearnPress\Filters\UserItemsFilter;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Quiz\QuizQuestionModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItemMeta\UserQuizMetaModel;
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
		$item_id   = absint( $request['item_id'] ?? 0 );
		$course_id = absint( $request['course_id'] ?? 0 );
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
			$userModel = UserModel::find( $user_id, true );
			if ( $user_id > 0 && ! $userModel instanceof UserModel ) {
				throw new Exception( esc_html__( 'The user is invalid', 'learnpress' ) );
			}

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel instanceof CourseModel ) {
				throw new Exception( esc_html__( 'The course is invalid', 'learnpress' ) );
			}

			$quizPostModel = $courseModel->get_item_model( $item_id, LP_QUIZ_CPT );
			if ( ! $quizPostModel instanceof QuizPostModel ) {
				throw new Exception( esc_html__( 'The quiz is invalid', 'learnpress' ) );
			}

			if ( post_password_required( $item_id ) && ! current_user_can( 'edit_post', $item_id ) ) {
				throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
			}

			if ( post_password_required( $course_id ) && ! current_user_can( 'edit_post', $course_id ) ) {
				throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
			}

			do_action( 'learn-press/user/before/start-quiz', $item_id, $course_id, $user_id );

			$link = LP_Helper::get_link_no_cache( $courseModel->get_item_link( $item_id, LP_QUIZ_CPT ) );

			// For no required enroll course (no need login)
			if ( ! $userModel && $courseModel->has_no_enroll_requirement() ) {
				$duration                 = new LP_Duration( $quizPostModel->get_duration() );
				$question_ids             = $quizPostModel->get_question_ids();
				$response['status']       = 'success';
				$results['question_ids']  = $question_ids;
				$results['questions']     = learn_press_rest_prepare_user_questions(
					$question_ids,
					array(
						'instant_check'       => $quizPostModel->has_instant_check(),
						'quiz_status'         => LP_ITEM_STARTED,
						'checked_questions'   => array(),
						'hinted_questions'    => array(),
						'answered'            => array(),
						'show_correct_review' => $quizPostModel->has_show_correct_review(),
					)
				);
				$results['total_time']    = $duration->get();
				$results['duration']      = $duration->get();
				$results['status']        = LP_ITEM_STARTED;
				$results['retaken']       = 0;
				$results['attempts']      = array();
				$results['user_item_id']  = 0;
				$results['answered']      = array();
				$response['results']      = $results;
				$response['redirect_url'] = $link;

				return rest_ensure_response( $response );
			}

			if ( ! $userModel instanceof UserModel ) {
				throw new Exception( esc_html__( 'You have to log in to start the quiz.', 'learnpress' ) );
			}

			$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $userCourseModel instanceof UserCourseModel ) {
				throw new Exception( esc_html__( 'The user course is invalid', 'learnpress' ) );
			}

			/**
			 * Require enroll course
			 *
			 * @var UserQuizModel $userQuizModel
			 */
			$retaken_count             = 0;
			$attempts                  = [];
			$filter_user_quiz          = new UserItemsFilter();
			$filter_user_quiz->user_id = $userModel->get_id();
			$filter_user_quiz->item_id = $item_id;
			$filter_user_quiz->ref_id  = $course_id;
			$userQuizModel             = UserQuizModel::get_user_item_model_from_db( $filter_user_quiz );
			if ( $userQuizModel instanceof UserQuizModel
				&& $userQuizModel->status === LP_ITEM_COMPLETED ) {
				$userQuizModel->retake();
				$results['answered'] = []; // Reset answered for js
				$retaken_count       = $userQuizModel->get_retaken_count();
				$attempts            = $userQuizModel->get_attempts();
			} else { // Create new user quiz and insert to database.
				$userQuizModelNew          = new UserQuizModel();
				$userQuizModelNew->user_id = $userModel->get_id();
				$userQuizModelNew->item_id = $item_id;
				$userQuizModelNew->ref_id  = $course_id;
				$userQuizModelNew->start_quiz();
				$userQuizModel = $userQuizModelNew;
			}

			$user_item_id = $userQuizModel->get_user_item_id();

			/**
			 * Clear cache result quiz
			 * Cache set on @see LP_User_Item_Quiz::get_results
			 */
			$lp_quiz_cache = LP_Quiz_Cache::instance();
			$key_cache     = sprintf( '%d/user/%d/course/%d', $item_id, $userModel->get_id(), $course_id );
			$lp_quiz_cache->clear( $key_cache );
			// End

			$show_check          = $quizPostModel->has_instant_check();
			$duration            = new LP_Duration( $quizPostModel->get_duration() );
			$show_correct_review = $quizPostModel->has_show_correct_review();
			$question_ids        = $quizPostModel->get_question_ids();
			$status              = $userQuizModel->get_status();
			$time_remaining      = $userQuizModel->get_time_remaining();

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
			$results['duration']      = $duration->get();
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
		$response = array(
			'status'  => 'error',
			'message' => '',
		);

		try {
			$user_id    = get_current_user_id();
			$item_id    = absint( $request['item_id'] ?? 0 );
			$course_id  = absint( $request['course_id'] ?? 0 );
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

			if ( post_password_required( $item_id ) && ! current_user_can( 'edit_post', $item_id ) ) {
				throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
			}

			if ( post_password_required( $course_id ) && ! current_user_can( 'edit_post', $course_id ) ) {
				throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
			}

			// Course is no required enroll (no need login)
			if ( ! $userModel instanceof UserModel && $courseModel->has_no_enroll_requirement() ) {
				$quiz               = learn_press_get_quiz( $item_id );
				$course             = learn_press_get_course( $course_id );
				$no_required_enroll = new LP_Course_No_Required_Enroll( $course );

				$result = $no_required_enroll->get_result_quiz( $quiz, $answered );

				// Set time spent
				$interval             = new LP_Duration( $time_spend );
				$interval             = $interval->to_timer();
				$result['time_spend'] = $interval;
				// End

				$result['status']    = LP_ITEM_COMPLETED;
				$result['attempts']  = [];
				$result['results']   = $result;
				$response['status']  = 'success';
				$response['results'] = $result;

				return rest_ensure_response( $response );
			}

			if ( ! $userModel instanceof UserModel ) {
				throw new Exception(
					esc_html__( 'You have to log in to submit the quiz.', 'learnpress' )
				);
			}

			// Course required enroll
			$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $userCourseModel instanceof UserCourseModel ) {
				throw new Exception( 'User not enrolled course!' );
			}

			$can_impact_quiz = $userCourseModel->can_impact_item();
			if ( $can_impact_quiz instanceof WP_Error ) {
				throw new Exception( $can_impact_quiz->get_error_message() );
			}

			/**
			 * @var UserQuizModel $user_quiz
			 */
			$userQuizModel = $userCourseModel->get_item_attend( $item_id, LP_QUIZ_CPT );
			if ( ! $userQuizModel instanceof UserQuizModel ) {
				throw new Exception( 'User not started quiz!' );
			}

			// For case save result when check instant answer
			$result_instant_check = LP_User_Items_Result_DB::instance()->get_result( $userQuizModel->get_user_item_id() );
			if ( $result_instant_check ) {
				foreach ( $result_instant_check['questions'] as $question_answer_id => $question_answer ) {
					if ( ! empty( $question_answer['answered'] ) ) {
						$answered[ $question_answer_id ] = $question_answer['answered'];
					}
				}
			}

			// Set end time.
			$start_time              = strtotime( $userQuizModel->get_start_time() );
			$userQuizModel->end_time = time();

			// Calculate quiz result and save.
			$result = $userQuizModel->calculate_quiz_result( $answered );
			// Save
			LP_User_Items_Result_DB::instance()->update( $userQuizModel->get_user_item_id(), wp_json_encode( $result ) );

			if ( $result['pass'] ) {
				$userQuizModel->graduation = ( UserItemModel::GRADUATION_PASSED );
			} else {
				$userQuizModel->graduation = UserItemModel::GRADUATION_FAILED;
			}

			$userQuizModel->status = UserItemModel::STATUS_COMPLETED;
			$userQuizModel->save();

			/**
			 * Clear cache result quiz
			 * Cache set on @see LP_User_Item_Quiz::get_results, and start_quiz() clears the same
			 * key. Without this the next read still serves the in-progress result and the
			 * submitted answers all come back as skipped.
			 */
			$key_cache = sprintf( '%d/user/%d/course/%d', $item_id, $user_id, $course_id );
			LP_Quiz_Cache::instance()->clear( $key_cache );

			do_action( 'learn-press/user/quiz-finished', $item_id, $course_id, $user_id, $userQuizModel );

			$result['status']    = $userQuizModel->get_status(); // Must be completed
			$result['attempts']  = $userQuizModel->get_attempts();
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
			$course_id   = absint( $request['course_id'] ?? 0 );
			$quiz_id     = absint( $request['item_id'] ?? 0 );
			$question_id = absint( $request['question_id'] ?? 0 );
			$answered    = $request['answered'] ?? '';
			$checked     = [];

			$userModel   = UserModel::find( $user_id, true );
			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel instanceof CourseModel ) {
				throw new Exception( esc_html__( 'The course is invalid', 'learnpress' ) );
			}

			$quizPostModel = $courseModel->get_item_model( $quiz_id, LP_QUIZ_CPT );
			if ( ! $quizPostModel instanceof QuizPostModel ) {
				throw new Exception( esc_html__( 'The quiz is invalid', 'learnpress' ) );
			}

			if ( post_password_required( $quiz_id ) && ! current_user_can( 'edit_post', $quiz_id ) ) {
				throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
			}

			if ( post_password_required( $course_id ) && ! current_user_can( 'edit_post', $course_id ) ) {
				throw new Exception( esc_html__( 'This content is password protected.', 'learnpress' ) );
			}

			$questionModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionModel ) {
				throw new Exception( esc_html__( 'The question is invalid', 'learnpress' ) );
			}

			// Check question has in quiz
			$quizQuestionModel = QuizQuestionModel::find( $quiz_id, $question_id );
			if ( ! $quizQuestionModel ) {
				throw new Exception( esc_html__( 'The quiz question is invalid', 'learnpress' ) );
			}

			// For case user not login
			if ( ! $userModel && $courseModel->has_no_enroll_requirement() ) {
				$course             = learn_press_get_course( $course_id );
				$no_required_enroll = new LP_Course_No_Required_Enroll( $course );
				$checked            = $no_required_enroll->guest_check_question( $question_id, $answered );
			} else { // For case user logged in
				if ( ! $userModel instanceof UserModel ) {
					throw new Exception( esc_html__( 'You must be logged in to check answer.', 'learnpress' ) );
				}

				$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
				if ( ! $userCourseModel instanceof UserCourseModel ) {
					throw new Exception( esc_html__( 'User\'s course no data!', 'learnpress' ) );
				}

				$can_impact_item = $userCourseModel->can_impact_item();
				if ( $can_impact_item instanceof WP_Error ) {
					throw new Exception( $can_impact_item->get_error_message() );
				}

				$userQuizModel = $userCourseModel->get_item_attend( $quiz_id, LP_QUIZ_CPT );
				if ( ! $userQuizModel instanceof UserQuizModel ) {
					throw new Exception( esc_html__( 'User\'s quiz no data!', 'learnpress' ) );
				}

				$question = learn_press_get_question( $question_id );
				if ( ! $question ) {
					throw new Exception( __( 'The question is invalid!', 'learnpress' ) );
				}

				if ( ! $quizPostModel->has_instant_check()
					|| $userQuizModel->get_status() !== UserQuizModel::STATUS_STARTED
					|| in_array( $question_id, $userQuizModel->get_checked_questions() ) ) {
					throw new Exception( __( 'Cannot check the answer to the question.', 'learnpress' ) );
				}

				$answered_check = array(
					'instant_check' => 1,
					$question_id    => $answered,
				);

				// For case save result when check instant answer
				$result_instant_check = LP_User_Items_Result_DB::instance()->get_result( $userQuizModel->get_user_item_id() );
				if ( $result_instant_check ) {
					foreach ( $result_instant_check['questions'] as $question_answer_id => $question_answer ) {
						if ( ! empty( $question_answer['answered'] ) ) {
							$answered_check[ $question_answer_id ] = $question_answer['answered'];
						}
					}
				}

				$result_answer = $userQuizModel->calculate_quiz_result( $answered_check );

				LP_User_Items_Result_DB::instance()->update( $userQuizModel->get_user_item_id(), wp_json_encode( $result_answer ) );

				$key_cache = sprintf( '%d/user/%d/course/%d', $quiz_id, $user_id, $course_id );
				LP_Quiz_Cache::instance()->clear( $key_cache );

				$checked_questions = $userQuizModel->get_checked_questions();
				$checked_questions = array_merge( $checked_questions, array( $question_id ) );
				$userQuizModel->set_meta_value_for_key( UserQuizMetaModel::KEY_QUESTION_CHECKED, $checked_questions );

				$checked = array(
					'answered' => $answered,
					'mark'     => $result_answer['questions'][ $question_id ]['mark'],
					'correct'  => $result_answer['questions'][ $question_id ]['correct'],
				);
			}

			$question                = learn_press_get_question( $question_id );
			$response['explanation'] = $question->get_explanation();
			$response['options']     = learn_press_get_question_options_for_js(
				$question,
				array(
					'include_is_true' => $quizPostModel->has_instant_check(),
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
