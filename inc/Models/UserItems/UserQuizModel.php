<?php

/**
 * Class UserItemModel
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItems;

use Exception;
use LearnPress\Models\UserItemMeta\UserItemMetaModel;
use LearnPress\Models\UserItemMeta\UserQuizMetaModel;
use LP_Course;
use LP_Datetime;
use LP_Quiz;
use LP_Quiz_CURD;
use LP_User;
use LP_User_Item_Meta_Filter;
use LP_User_Items_Filter;
use LP_User_Items_Result_DB;
use stdClass;
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
	/**
	 * @var LP_User $user not column in DB
	 */
	public $user;
	/**
	 * @var LP_Course $course not column in DB
	 */
	public $course;
	/**
	 * @var LP_Quiz $quiz not column in DB
	 */
	public $quiz;
	/**
	 * @var UserCourseModel $user_course not column in DB
	 */
	public $user_course;

	/**
	 * Get user_course from DB.
	 *
	 * @param LP_User_Items_Filter $filter
	 * @param bool $no_cache
	 * @return UserQuizModel|false
	 */
	public static function get_user_quiz_model_from_db( LP_User_Items_Filter $filter, bool $no_cache = true ) {
		$user_quiz         = false;
		$filter->item_type = ( new UserQuizModel )->item_type;
		$user_item         = self::get_user_item_model_from_db( $filter, $no_cache );

		if ( ! empty( $user_item ) ) {
			$user_quiz = new self( $user_item );
		}

		return $user_quiz;
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
	 * Get Timestamp remaining when user doing quiz
	 *
	 * @move from LP_Quiz
	 * @return int
	 * @throws Exception
	 * @author tungnx
	 * @version 1.0.1
	 * @sicne 4.1.4.1
	 */
	public function get_timestamp_remaining(): int {
		if ( $this->status != LP_ITEM_STARTED ) {
			throw new Exception( 'User quiz is not started.' );
		}

		if ( empty( $this->user_item_id ) ) {
			throw new Exception( 'User quiz is not exists.' );
		}

		$timestamp_remaining = - 1;

		$quiz = learn_press_get_quiz( $this->item_id );
		if ( ! $quiz ) {
			return $timestamp_remaining;
		}

		$duration            = $quiz->get_duration()->get() . ' second';
		$course_start_time   = $this->start_time;
		$timestamp_expire    = strtotime( $course_start_time . ' +' . $duration );
		$timestamp_current   = time();
		$timestamp_remaining = $timestamp_expire - $timestamp_current;

		if ( $timestamp_remaining < 0 ) {
			$timestamp_remaining = 0;
		}

		return apply_filters( 'learn-press/user-course-quiz/timestamp_remaining', $timestamp_remaining, $this, $quiz );
	}

	/**
	 * Start quiz.
	 *
	 * @return UserQuizModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function start_quiz(): UserQuizModel {
		$can_start = $this->check_can_start();
		if ( is_wp_error( $can_start ) ) {
			/**
			 * @var WP_Error $can_start
			 */
			throw new Exception( $can_start->get_error_message() );
		}

		$this->status     = LP_ITEM_STARTED;
		$this->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
		return $this->save();
	}

	/**
	 * Retake quiz.
	 *
	 * @throws Exception
	 */
	public function retake(): UserQuizModel {
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
			$user_quiz_retaken_new->learnpress_user_item_id = $this->user_item_id;
			$user_quiz_retaken_new->meta_key                = UserQuizMetaModel::KEY_RETAKEN_COUNT;
			$user_quiz_retaken_new->meta_value              = 1;
			$user_quiz_retaken_new->save();
		}

		//Todo: rewrite by object.
		//Create new result in table learnpress_user_item_results.
		LP_User_Items_Result_DB::instance()->insert( $this->user_item_id );
		// Remove user_item_meta.
		learn_press_delete_user_item_meta( $this->user_item_id, '_lp_question_checked' );

		$this->status     = LP_ITEM_STARTED;
		$this->start_time = gmdate( LP_Datetime::$format, time() );
		$this->end_time   = null;
		$this->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
		return $this->save();
	}

	/**
	 * Check user can start quiz.
	 * If user can start quiz, return true, else return WP_Error.
	 * Set user, quiz, course, user_course and parent_id for this object.
	 *
	 * @throws Exception
	 * return bool|WP_Error
	 */
	public function check_can_start() {
		$can_start = true;

		$this->user = learn_press_get_user( $this->user_id );
		if ( ! $this->user instanceof LP_User ) {
			$can_start = new WP_Error( 'user_invalid', __( 'User is invalid.', 'learnpress' ) );
		}

		$this->quiz = learn_press_get_quiz( $this->item_id );
		if ( empty( $this->quiz ) ) {
			$can_start = new WP_Error( 'quiz_invalid', __( 'Quiz is invalid.', 'learnpress' ) );
		}

		$this->course = learn_press_get_course( $this->ref_id );
		if ( empty( $this->course ) ) {
			$can_start = new WP_Error( 'course_invalid', __( 'Course is invalid.', 'learnpress' ) );
		}

		// Check user, course of quiz is enrolled.
		$user_course       = $this->user->get_course_attend( $this->ref_id );
		$this->user_course = $user_course;
		if ( ! $user_course instanceof UserCourseModel
			|| $user_course->graduation !== LP_COURSE_GRADUATION_IN_PROGRESS ) {
			$can_start = new WP_Error( 'not_errol_course', __( 'Please enroll in the course before starting the quiz.', 'learnpress' ) );
		} elseif ( $user_course->status === LP_COURSE_FINISHED ) {
			$can_start = new WP_Error( 'finished_course', __( 'You have already finished the course of this quiz.', 'learnpress' ) );
		} else {
			// Set Parent id for user quiz to save DB.
			$this->parent_id = $this->user_course->user_item_id;

			// Check if user has already started or completed quiz
			$user_quiz = $this->user_course->get_item_attend( $this->item_id, $this->item_type );
			if ( $user_quiz instanceof UserQuizModel ) {
				$can_start = new WP_Error( 'started_quiz', __( 'You have already started or completed the quiz.', 'learnpress' ) );
			}
		}

		// Hook can start quiz
		return apply_filters(
			'learn-press/can-start-quiz',
			$can_start,
			$user_course,
			$this
		);
	}

	/**
	 * Check can retake quiz.
	 *
	 * @throws Exception
	 */
	public function check_can_retake() {
		$can_retake = true;

		$this->user = learn_press_get_user( $this->user_id );
		if ( ! $this->user instanceof LP_User ) {
			$can_retake = new WP_Error( 'user_invalid', __( 'User is invalid.', 'learnpress' ) );
		}

		$this->quiz = learn_press_get_quiz( $this->item_id );
		if ( empty( $this->quiz ) ) {
			$can_start = new WP_Error( 'quiz_invalid', __( 'Quiz is invalid.', 'learnpress' ) );
		}

		$this->course = learn_press_get_course( $this->ref_id );
		if ( empty( $this->course ) ) {
			$can_retake = new WP_Error( 'course_invalid', __( 'Course is invalid.', 'learnpress' ) );
		}

		// Check user, course of quiz is enrolled.
		$user_course       = $this->user->get_course_attend( $this->ref_id );
		$this->user_course = $user_course;
		if ( ! $user_course instanceof UserCourseModel
			|| $user_course->graduation !== LP_COURSE_GRADUATION_IN_PROGRESS ) {
			$can_retake = new WP_Error( 'not_errol_course', __( 'Please enroll in the course before starting the quiz.', 'learnpress' ) );
		} elseif ( $user_course->status === LP_COURSE_FINISHED ) {
			$can_retake = new WP_Error( 'finished_course', __( 'You have already finished the course of this quiz.', 'learnpress' ) );
		}

		// Check user quiz start and completed?.
		$user_quiz_exists = $this->user_course->get_item_attend( $this->item_id, $this->item_type );
		if ( ! $user_quiz_exists instanceof UserQuizModel ) {
			$can_retake = new WP_Error( 'not_started_quiz', __( 'You have not start the quiz.', 'learnpress' ) );
		} elseif ( $user_quiz_exists->status !== LP_ITEM_COMPLETED ) {
			$can_retake = new WP_Error( 'not_completed_quiz', __( 'You have not completed the quiz.', 'learnpress' ) );
		}

		// Check retaken count.
		$retake_config = get_post_meta( $this->item_id, '_lp_retake_count', true );
		if ( $retake_config != '-1' ) {
			$number_retaken = absint( $this->get_meta_value_from_key( UserQuizMetaModel::KEY_RETAKEN_COUNT ) );
			if ( $number_retaken >= $retake_config ) {
				$can_retake = new WP_Error( 'exceed_retaken_count', __( 'You have exceeded the number of retakes.', 'learnpress' ) );
			}
		}

		// Hook can retake quiz
		return apply_filters(
			'learn-press/can-retake-quiz',
			$can_retake,
			$user_course,
			$this
		);
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

		$results = LP_User_Items_Result_DB::instance()->get_results( $this->user_item_id, $limit, true );
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
		return absint( $this->get_meta_value_from_key( UserQuizMetaModel::KEY_RETAKEN_COUNT ) );
	}

	/**
	 * Get all questions user has already used "Check"
	 * @move from LP_Quiz
	 *
	 * @return array
	 */
	public function get_checked_questions(): array {
		$value_str = $this->get_meta_value_from_key( UserQuizMetaModel::KEY_QUESTION_CHECKED );
		$value     = maybe_unserialize( $value_str );

		if ( $value ) {
			$value = (array) $value;
		} else {
			$value = array();
		}

		return $value;
	}
}
