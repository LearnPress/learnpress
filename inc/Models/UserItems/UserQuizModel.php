<?php

/**
 * Class UserQuizModel
 *
 * @package LearnPress/Classes
 * @version 1.0.2
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItems;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItemMeta\UserItemMetaModel;
use LearnPress\Models\UserItemMeta\UserQuizMetaModel;
use LearnPress\Models\UserModel;
use LearnPressAssignment\Models\AssignmentPostModel;
use LP_Course;
use LP_Datetime;
use LP_Helper;
use LP_Question;
use LP_Quiz;
use LP_Quiz_CURD;
use LP_User;
use LP_User_Items_Result_DB;
use Throwable;
use WP_Error;

class UserQuizModel extends UserItemModel {
	/**
	 * Item type Course
	 *
	 * @var string Item type
	 */
	public $item_type = LP_QUIZ_CPT;
	/**
	 * Ref type Order
	 *
	 * @var string
	 */
	public $ref_type = LP_COURSE_CPT;

	public const STATUS_STARTED = 'started';

	public function __construct( $data = null ) {
		parent::__construct( $data );
	}

	/**
	 * Get quiz model
	 *
	 * @return bool|QuizPostModel
	 * @since 4.2.5
	 * @version 1.0.1
	 */
	public function get_quiz_post_model() {
		return QuizPostModel::find( $this->item_id, true );
	}

	/**
	 * Get course model
	 *
	 * @return false|CourseModel
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function get_course_model() {
		return CourseModel::find( $this->ref_id, true );
	}

	/**
	 * Get user course model
	 *
	 * @return false|UserCourseModel
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function get_user_course_model() {
		return UserCourseModel::find( $this->user_id, $this->ref_id, true );
	}

	/**
	 * Get status
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get all question's ids of the quiz.
	 *
	 * @param string $context
	 *
	 * @move from LP_Quiz
	 * @return int[]
	 * @editor tungnx
	 * @version 1.0.1
	 * @since 3.2.0
	 */
	public function get_question_ids( string $context = 'display' ): array {
		$curd         = new LP_Quiz_CURD();
		$question_ids = $curd->read_question_ids( $this->item_id, $context );
		$question_ids = apply_filters( 'learn-press/quiz/get-question-ids', $question_ids, $this->item_id, $this->ref_id, $context );
		if ( ! is_array( $question_ids ) ) {
			$question_ids = array();
		}

		return $question_ids;
	}

	/**
	 * Get time remaining when user doing assignment.
	 *
	 * @return int seconds
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function get_time_remaining(): int {
		$time_remaining = 0;
		$quizPostModel  = $this->get_quiz_post_model();
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return $time_remaining;
		}

		$duration          = $quizPostModel->get_duration();
		$start_time_stamp  = strtotime( $this->get_start_time() );
		$expire_time_stamp = strtotime( '+' . $duration, $start_time_stamp );
		$time_remaining    = $expire_time_stamp - time();

		if ( $time_remaining < 0 ) {
			$time_remaining = 0;
		}

		return $time_remaining;
	}

	/**
	 * Start quiz.
	 *
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function start_quiz() {
		$can_start = $this->check_can_start();
		if ( is_wp_error( $can_start ) ) {
			/**
			 * @var WP_Error $can_start
			 */
			throw new Exception( $can_start->get_error_message() );
		}

		$this->status     = self::STATUS_STARTED;
		$this->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
		$this->save();

		// Hook old - random quiz using.
		do_action( 'learn-press/user/quiz-started', $this->item_id, $this->ref_id, $this->user_id );
		// Hook new
		do_action( 'learn-press/user/quiz/started', $this );
	}

	/**
	 * Retake quiz.
	 *
	 * @throws Exception
	 */
	public function retake() {
		$can_retake = $this->check_can_retake();
		if ( is_wp_error( $can_retake ) ) {
			/**
			 * @var WP_Error $can_retake
			 */
			throw new Exception( $can_retake->get_error_message() );
		}

		// Update retaken count.
		$user_quiz_retaken = $this->get_meta_model_from_key( UserQuizMetaModel::KEY_RETAKEN_COUNT );
		if ( $user_quiz_retaken instanceof UserItemMetaModel ) {
			$number_retaken = absint( $user_quiz_retaken->meta_value );
			++$number_retaken;
			$user_quiz_retaken->meta_value = $number_retaken;
			$user_quiz_retaken->save();
		} else {
			$user_quiz_retaken_new                          = new UserQuizMetaModel();
			$user_quiz_retaken_new->learnpress_user_item_id = $this->get_user_item_id();
			$user_quiz_retaken_new->meta_key                = UserQuizMetaModel::KEY_RETAKEN_COUNT;
			$user_quiz_retaken_new->meta_value              = 1;
			$user_quiz_retaken_new->save();
		}

		//Todo: rewrite by object.
		//Create new result in table learnpress_user_item_results.
		LP_User_Items_Result_DB::instance()->insert( $this->get_user_item_id() );
		// Remove user_item_meta.
		learn_press_delete_user_item_meta( $this->get_user_item_id(), '_lp_question_checked' );

		$this->status     = self::STATUS_STARTED;
		$this->start_time = gmdate( LP_Datetime::$format, time() );
		$this->end_time   = null;
		$this->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
		$this->save();

		// Hook old - random quiz using.
		do_action( 'learn-press/user/quiz-retried', $this->item_id, $this->ref_id, $this->user_id );
		// Hook new
		do_action( 'learn-press/user/quiz/retake', $this );
	}

	/**
	 * Check user can start quiz.
	 * If user can start quiz, return true, else return WP_Error.
	 * Set user, quiz, course, user_course and parent_id for this object.
	 *
	 * @throws Exception
	 * return bool|WP_Error
	 *
	 * @since 4.2.5
	 * @version 1.0.2
	 */
	public function check_can_start() {
		$can_start = true;

		$userModel = $this->get_user_model();
		if ( ! $userModel instanceof UserModel ) {
			$can_start = new WP_Error( 'user_invalid', __( 'User is invalid.', 'learnpress' ) );
		}

		$quizPostModel = $this->get_quiz_post_model();
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			$can_start = new WP_Error( 'quiz_invalid', __( 'Quiz is invalid.', 'learnpress' ) );
		}

		$courseModel = $this->get_course_model();
		if ( ! $courseModel instanceof CourseModel ) {
			$can_start = new WP_Error( 'course_invalid', __( 'Course is invalid.', 'learnpress' ) );
		}

		$userQuizModel = UserQuizModel::find_user_item(
			$this->user_id,
			$this->item_id,
			$this->item_type,
			$this->ref_id,
			$this->ref_type,
			true
		);
		if ( $userQuizModel instanceof UserQuizModel ) {
			$can_start = new WP_Error( 'started_quiz', __( 'You have already started the quiz.', 'learnpress' ) );
		}

		// Check user, course of quiz is enrolled.
		$userCourseModel = $this->get_user_course_model();
		if ( ! $userCourseModel instanceof UserCourseModel
			|| $userCourseModel->status !== LP_COURSE_ENROLLED ) {
			$can_start = new WP_Error( 'not_errol_course', __( 'Please enroll in the course before starting the quiz.', 'learnpress' ) );
		} elseif ( $userCourseModel->status === LP_COURSE_FINISHED ) {
			$can_start = new WP_Error( 'finished_course', __( 'You have already finished the course of this quiz.', 'learnpress' ) );
		} else {
			// Set Parent id for user quiz to save DB.
			$this->parent_id = $userCourseModel->get_user_item_id();
		}

		// Hook old.
		if ( has_filter( 'learn-press/can-start-quiz' ) ) {
			try {
				$can_start = apply_filters(
					'learn-press/can-start-quiz',
					$can_start,
					$userQuizModel->item_id,
					$userQuizModel->ref_id,
					$userQuizModel->user_id
				);
			} catch ( Throwable $e ) {
				$can_start = new WP_Error( 'can_not_start_quiz', $e->getMessage() );
			}
		}

		// Hook can start quiz
		return apply_filters(
			'learn-press/user/can-start-quiz',
			$can_start,
			$this
		);
	}

	/**
	 * Check can retake quiz.
	 *
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.1
	 */
	public function check_can_retake() {
		$can_retake = true;

		$userModel = $this->get_user_model();
		if ( ! $userModel instanceof UserModel ) {
			$can_retake = new WP_Error( 'user_invalid', __( 'User is invalid.', 'learnpress' ) );
		}

		$quizPostModel = $this->get_quiz_post_model();
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			$can_retake = new WP_Error( 'quiz_invalid', __( 'Quiz is invalid.', 'learnpress' ) );
		}

		$course = CourseModel::find( $this->ref_id, true );
		if ( ! $course instanceof CourseModel ) {
			$can_retake = new WP_Error( 'course_invalid', __( 'Course is invalid.', 'learnpress' ) );
		}

		// Check user, course of quiz is enrolled.
		$userCourseModel = $this->get_user_course_model();
		if ( ! $userCourseModel instanceof UserCourseModel
			|| $userCourseModel->get_graduation() !== LP_COURSE_GRADUATION_IN_PROGRESS ) {
			$can_retake = new WP_Error(
				'not_errol_course',
				__( 'Please enroll in the course before starting the quiz.', 'learnpress' )
			);
		} elseif ( $userCourseModel->get_status() === LP_COURSE_FINISHED ) {
			$can_retake = new WP_Error(
				'finished_course',
				__( 'You have already finished the course of this quiz.', 'learnpress' )
			);
		}

		// Check user quiz start and completed?.
		if ( $this->get_status() !== LP_ITEM_COMPLETED ) {
			$can_retake = new WP_Error( 'not_completed_quiz', __( 'You have not completed the quiz.', 'learnpress' ) );
		}

		// Check retaken count.
		$retake_max = $quizPostModel->get_retake_count();
		if ( $retake_max != -1 && $this->get_remaining_retake() === 0 ) {
			$can_retake = new WP_Error( 'exceed_retaken_count', __( 'You have exceeded the number of retakes.', 'learnpress' ) );
		}

		// Hook can retake quiz
		return apply_filters(
			'learn-press/user/can-retake-quiz',
			$can_retake,
			$this
		);
	}

	/**
	 * Get number retake remaining.
	 *
	 * @return int
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function get_remaining_retake(): int {
		$quizPostModel    = $this->get_quiz_post_model();
		$retake_max       = $quizPostModel->get_retake_count();
		$retaken_count    = $this->get_retaken_count();
		$remaining_retake = $retake_max - $retaken_count;
		if ( $remaining_retake <= 0 ) {
			$remaining_retake = 0;
		}

		return $remaining_retake;
	}

	/**
	 * Get all attempts of a quiz.
	 *
	 * @move from LP_Quiz
	 *
	 * @return array
	 */
	public function get_attempts( $limit = 3 ) {
		$limit = $limit ?? 3;

		$limit = absint( apply_filters( 'lp/quiz/get-attempts/limit', $limit ) );

		$results = LP_User_Items_Result_DB::instance()->get_results( $this->get_user_item_id(), $limit, true );
		$output  = array();

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( $result && is_string( $result ) ) {
					$result = json_decode( $result );

					unset( $result->questions );

					$output[] = $result;
				}
			}
		}

		return $output;
	}

	/**
	 * Get number retaken count.
	 * @move from LP_Quiz
	 *
	 * @return integer
	 */
	public function get_retaken_count(): int {
		return absint( $this->get_meta_value_from_key( UserQuizMetaModel::KEY_RETAKEN_COUNT, 0 ) );
	}

	/**
	 * Get all questions user has already used "Check"
	 * @move from LP_Quiz
	 *
	 * @return array
	 */
	public function get_checked_questions(): array {
		$value_str = $this->get_meta_value_from_key( UserQuizMetaModel::KEY_QUESTION_CHECKED, [] );
		$value     = maybe_unserialize( $value_str );

		if ( $value ) {
			$value = (array) $value;
		} else {
			$value = array();
		}

		return $value;
	}

	/**
	 * Get result when user completed quiz.
	 *
	 * @move from LP_Quiz
	 * @return array
	 */
	public function get_result(): array {
		$result = array(
			'questions'         => array(),
			'mark'              => 0,
			'user_mark'         => 0,
			'minus_point'       => 0,
			'question_count'    => 0,
			'question_empty'    => 0,
			'question_answered' => 0,
			'question_wrong'    => 0,
			'question_correct'  => 0,
			'status'            => '',
			'result'            => 0,
			'time_spend'        => '',
			'passing_grade'     => '',
			'pass'              => 0,
		);

		try {
			$result_tmp = LP_User_Items_Result_DB::instance()->get_result( $this->get_user_item_id() );
			if ( $result_tmp ) {
				if ( isset( $result_tmp['user_mark'] ) && $result_tmp['user_mark'] < 0 ) {
					$result_tmp['user_mark'] = 0;
				}

				$result = $result_tmp;
			}

			return $result;
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $result;
	}

	/**
	 * Calculate result of quiz.
	 * @move from LP_Quiz
	 * @param array $answered [question_id => answered, 'instant_check' => 0]
	 *
	 * @return array
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	private function calculate_quiz_result( array $answered ): array {
		$result = array(
			'questions'         => array(),
			'mark'              => 0,
			'user_mark'         => 0,
			'minus_point'       => 0,
			'question_count'    => 0,
			'question_empty'    => 0,
			'question_answered' => 0,
			'question_wrong'    => 0,
			'question_correct'  => 0,
			'status'            => '',
			'result'            => 0,
			'time_spend'        => '',
			'passing_grade'     => '',
			'pass'              => 0,
		);

		$quizPostModel = $this->get_quiz_post_model();
		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return $result;
		}

		$question_ids             = $quizPostModel->get_question_ids();
		$result['mark']           = $quizPostModel->get_mark();
		$result['question_count'] = $quizPostModel->count_questions();
		$result['time_spend']     = $this->get_time_spend();
		$result['passing_grade']  = $quizPostModel->get_passing_grade();
		$checked_questions        = $this->get_checked_questions();

		foreach ( $question_ids as $question_id ) {
			$question = LP_Question::get_question( $question_id );
			$point    = floatval( $question->get_mark() );

			$result['questions'][ $question_id ]             = array();
			$result['questions'][ $question_id ]['answered'] = $answered[ $question_id ] ?? '';

			if ( isset( $answered[ $question_id ] ) ) { // User's answer
				++$result['question_answered'];

				$check = $question->check( $answered[ $question_id ] );
				$point = apply_filters( 'learn-press/user/calculate-quiz-result/point', $point, $question, $check );
				if ( $check['correct'] ) {
					++$result['question_correct'];
					$result['user_mark'] += $point;

					$result['questions'][ $question_id ]['correct'] = true;
					$result['questions'][ $question_id ]['mark']    = $point;
				} else {
					if ( $quizPostModel->has_negative_marking() ) {
						$result['user_mark']   -= $point;
						$result['minus_point'] += $point;
					}
					++$result['question_wrong'];

					$result['questions'][ $question_id ]['correct'] = false;
					$result['questions'][ $question_id ]['mark']    = 0;
				}
			} elseif ( ! array_key_exists( 'instant_check', $answered ) ) { // User skip question
				if ( $quizPostModel->has_minus_skip_questions() ) {
					$result['user_mark']   -= $point;
					$result['minus_point'] += $point;
				}
				++$result['question_empty'];

				$result['questions'][ $question_id ]['correct'] = false;
				$result['questions'][ $question_id ]['mark']    = 0;
			}

			if ( $quizPostModel->has_instant_check() && ! array_key_exists( 'instant_check', $answered ) ) {
				$result['questions'][ $question_id ]['explanation'] = $question->get_explanation();
				$result['questions'][ $question_id ]['options']     = learn_press_get_question_options_for_js(
					$question,
					array(
						'include_is_true' => in_array( $question_id, $checked_questions ) || $quizPostModel->has_show_correct_review(),
						'answer'          => $answered[ $question_id ] ?? '',
					)
				);
			}
		}

		if ( $result['user_mark'] < 0 ) {
			$result['user_mark'] = 0;
		}

		if ( $result['user_mark'] > 0 && $result['mark'] > 0 ) {
			$result['result'] = round( $result['user_mark'] * 100 / $result['mark'], 2, PHP_ROUND_HALF_DOWN );
		}

		$passing_grade = $quizPostModel->get_passing_grade();
		if ( $result['result'] >= $passing_grade ) {
			$result['pass'] = 1;
		} else {
			$result['pass'] = 0;
		}

		return $result;
	}

	/**
	 * Get string time spend.
	 *
	 * @return string
	 */
	public function get_time_spend(): string {
		$interval = $this->get_total_timestamp_completed();
		if ( empty( $interval ) ) {
			return '--:--';
		}

		$duration = new LP_Datetime( $interval );
		return $duration->format( 'H:i:s' );
	}

	/**
	 * Get history user did quiz.
	 *
	 * @param int $limit
	 *
	 * @return array
	 * @version 1.0.0
	 * @since 4.2.7.6
	 */
	public function get_history( int $limit = 3 ): array {
		$history = array();

		try {
			$results = LP_User_Items_Result_DB::instance()->get_results( $this->get_user_item_id(), $limit, true );

			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					if ( $result && is_string( $result ) ) {
						$result = LP_Helper::json_decode( $result );

						unset( $result->questions );

						$history[] = $result;
					}
				}
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $history;
	}
	public function get_status_label( $status = '' ) {
		$statuses = array(
			'started'     => __( 'In Progress', 'learnpress' ),
			'in-progress' => __( 'In Progress', 'learnpress' ),
			'completed'   => __( 'Completed', 'learnpress' ),
			'passed'      => __( 'Passed', 'learnpress' ),
			'failed'      => __( 'Failed', 'learnpress' ),
		);

		if ( ! $status ) {
			$status = $this->get_status();
		}

		return ! empty( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'Not Started', 'learnpress' );
	}
}
