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
use LearnPress\Models\UserItemMeta\UserQuizMetaModel;
use LP_Course;
use LP_Datetime;
use LP_Quiz;
use LP_User;
use LP_User_Items_Filter;
use WP_Error;

/**
 * @method update()
 */
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
	 * Meta data of quiz.
	 *
	 * @var UserQuizMetaModel
	 */
	public $meta_data;
	/**
	 * @var LP_User
	 */
	public $user;
	/**
	 * @var LP_Course
	 */
	public $course;
	/**
	 * @var LP_Quiz
	 */
	public $quiz;
	/**
	 * @var UserCourseModel
	 */
	public $user_course;

	/**
	 * Get user_course from DB.
	 *
	 * @param LP_User_Items_Filter $filter
	 * @param bool $no_cache
	 * @return UserQuizModel|false
	 */
	public static function get_user_quiz_model_from_db( LP_User_Items_Filter $filter, bool $no_cache = false ) {
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
	 * Get Timestamp remaining when user doing quiz
	 *
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
		$this->check_can_retake();

		//Todo: update quiz meta data.

		$this->status     = LP_ITEM_STARTED;
		$this->start_time = gmdate( LP_Datetime::$format, time() );
		$this->end_time   = null;
		$this->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
		return $this->save();
	}

	/**
	 * Check user can start quiz.
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
		$filter_user_course          = new LP_User_Items_Filter();
		$filter_user_course->user_id = $this->user_id;
		$filter_user_course->item_id = $this->ref_id;
		$user_course                 = UserCourseModel::get_user_course_model_from_db( $filter_user_course, true );
		$this->user_course           = $user_course;
		if ( ! $user_course instanceof UserCourseModel
			|| $user_course->graduation !== LP_COURSE_GRADUATION_IN_PROGRESS ) {
			$can_start = new WP_Error( 'not_errol_course', __( 'Please enroll in the course before starting the quiz.', 'learnpress' ) );
		} elseif ( $user_course->status === LP_COURSE_FINISHED ) {
			$can_start = new WP_Error( 'finished_course', __( 'You have already finished the course of this quiz.', 'learnpress' ) );
		}

		// Check if user has already started or completed quiz
		$filter_user_quiz            = new LP_User_Items_Filter();
		$filter_user_quiz->user_id   = $this->user_id;
		$filter_user_quiz->item_id   = $this->item_id;
		$filter_user_quiz->parent_id = $user_course->user_item_id;
		$user_quiz                   = self::get_user_quiz_model_from_db( $filter_user_quiz, true );
		// Set Parent id for user quiz to save DB.
		$this->parent_id = $user_course->user_item_id;
		if ( $user_quiz instanceof UserQuizModel ) {
			$can_start = new WP_Error( 'started_quiz', __( 'You have already started or completed the quiz.', 'learnpress' ) );
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
	 * @throws Exception
	 */
	public function check_can_retake() {

	}
}
