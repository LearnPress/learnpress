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
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserModel;
use LP_Cache;
use LP_Course;
use LP_Course_Cache;
use LP_Course_Item;
use LP_Courses_Cache;
use LP_Datetime;
use LP_User;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use LP_User_Items_Result_DB;
use stdClass;
use Thim_Cache_DB;
use Throwable;
use WP_Error;

class UserCourseModel extends UserItemModel {
	/**
	 * Item type Course
	 *
	 * @var string Item type
	 */
	public $item_type = LP_COURSE_CPT;
	/**
	 * Ref type Order
	 *
	 * @var string
	 */
	public $ref_type = LP_ORDER_CPT;

	public function __construct( $data = null ) {
		parent::__construct( $data );

		if ( $data ) {
			$this->get_course_model();
		}
	}

	/**
	 * Get course model
	 *
	 * @return bool|CourseModel
	 */
	public function get_course_model() {
		return CourseModel::find( $this->item_id, true );
	}

	/**
	 * Find User Item by user_id, item_id, item_type.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param bool $check_cache
	 *
	 * @return false|UserItemModel|static
	 * @since 4.2.7
	 * @version 1.0.1
	 */
	public static function find( int $user_id, int $course_id, bool $check_cache = false ) {
		$filter            = new LP_User_Items_Filter();
		$filter->user_id   = $user_id;
		$filter->item_id   = $course_id;
		$filter->item_type = LP_COURSE_CPT;
		$key_cache         = "userCourseModel/find/{$user_id}/{$course_id}/{$filter->item_type}";
		$lpUserCourseCache = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$userCourseModel = $lpUserCourseCache->get_cache( $key_cache );
			if ( $userCourseModel instanceof UserCourseModel ) {
				return $userCourseModel;
			}
		}

		$userCourseModel = static::get_user_item_model_from_db( $filter );

		// Set cache
		if ( $userCourseModel instanceof UserCourseModel ) {
			$lpUserCourseCache->set_cache( $key_cache, $userCourseModel );
		}

		return $userCourseModel;
	}

	/**
	 * Get user_items is child of user course.
	 *
	 * @param int $item_id
	 * @param string $item_type
	 * @return false|UserItemModel
	 */
	public function get_item_attend( int $item_id, string $item_type = '' ) {
		$item = false;

		try {
			$filter            = new LP_User_Items_Filter();
			$filter->parent_id = $this->get_user_item_id();
			$filter->item_id   = $item_id;
			$filter->item_type = $item_type;
			$filter->ref_type  = $this->item_type;
			$filter->ref_id    = $this->item_id;
			$filter->user_id   = $this->user_id;
			$item              = UserItemModel::get_user_item_model_from_db( $filter );

			if ( $item ) {
				switch ( $item_type ) {
					case LP_QUIZ_CPT:
						$item = new UserQuizModel( $item );
						break;
					default:
						$item = new UserItemModel( $item );
						break;
				}

				$item = apply_filters( 'learn-press/user-course-has-item-attend', $item, $item_type, $this );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $item;
	}

	/**
	 * Count students.
	 *
	 * @param LP_User_Items_Filter $filter
	 * @return int
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public static function count_students( LP_User_Items_Filter $filter ): int {
		// Check cache
		$key_cache = 'count-courses-student-' . md5( json_encode( $filter ) );
		$count     = LP_Cache::cache_load_first( 'get', $key_cache );
		if ( false !== $count ) {
			return $count;
		}

		$lp_courses_cache = new LP_Courses_Cache( true );
		$count            = $lp_courses_cache->get_cache( $key_cache );
		if ( false !== $count ) {
			LP_Cache::cache_load_first( 'set', $key_cache, $count );
			return $count;
		}

		$lp_user_items_db = LP_User_Items_DB::getInstance();
		$count            = $lp_user_items_db->count_students( $filter );

		// Set cache
		$lp_courses_cache->set_cache( $key_cache, $count );
		$lp_courses_cache_keys = new LP_Courses_Cache( true );
		$lp_courses_cache_keys->save_cache_keys_count_student_courses( $key_cache );
		LP_Cache::cache_load_first( 'set', $key_cache, $count );

		return $count;
	}

	/**
	 * Check course of user is enrolled or finished
	 *
	 * @return bool
	 */
	public function has_enrolled_or_finished(): bool {
		return $this->status === LP_COURSE_ENROLLED || $this->status === LP_COURSE_FINISHED;
	}

	/**
	 * Check user has enrolled course
	 *
	 * @return bool
	 */
	public function has_enrolled(): bool {
		return $this->status === LP_COURSE_ENROLLED;
	}

	/**
	 * Check user has purchased course
	 *
	 * @return bool
	 */
	public function has_purchased(): bool {
		return $this->status === LP_COURSE_PURCHASED;
	}

	/**
	 * Check user has finished course
	 *
	 * @return bool
	 */
	public function has_finished(): bool {
		return $this->status === LP_COURSE_FINISHED;
	}

	/**
	 * Check course has finished or not.
	 *
	 * @return bool
	 * @move from class-lp-user-item-course.php
	 * @since  3.0.0
	 * @version 1.0.1
	 */
	public function is_finished(): bool {
		return $this->status === LP_COURSE_FINISHED;
	}

	/**
	 * Calculate course result
	 *
	 * @move from class-lp-user-item-course.php
	 * @since 4.1.4
	 * @version 1.0.2
	 */
	public function calculate_course_results( bool $force_cache = false ) {
		$items   = array();
		$results = array(
			'count_items'     => 0,
			'completed_items' => 0,
			'items'           => array(),
			'evaluate_type'   => '',
			'pass'            => 0,
			'result'          => 0,
		);

		try {
			$courseModel = $this->get_course_model();
			if ( ! $courseModel instanceof CourseModel ) {
				throw new Exception( 'Course invalid!' );
			}

			$key_first_cache = 'calculate_course/' . $this->user_id . '/' . $courseModel->get_id();
			$results_cache   = LP_Cache::cache_load_first( 'get', $key_first_cache );
			if ( false !== $results_cache && ! $force_cache ) {
				return $results_cache;
			}

			if ( $this->is_finished() ) {
				// Get result from lp_user_item_results
				// Todo: tungnx - set cache
				return LP_User_Items_Result_DB::instance()->get_result( $this->get_user_item_id() );
			}

			if ( $this->status !== LP_COURSE_ENROLLED ) {
				return $results;
			}

			$count_items           = $courseModel->get_total_items();
			$count_items_completed = $this->count_items_completed();

			$evaluate_type = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_EVALUATION_TYPE, 'evaluate_lesson' );
			switch ( $evaluate_type ) {
				case 'evaluate_lesson':
					$results_evaluate = $this->evaluate_course_by_lesson( $count_items_completed, $courseModel->count_items( LP_LESSON_CPT ) );
					break;
				case 'evaluate_final_quiz':
					$results_evaluate = $this->evaluate_course_by_final_quiz();
					break;
				case 'evaluate_quiz':
					$results_evaluate = $this->evaluate_course_by_quizzes_passed( $count_items_completed, $courseModel->count_items( LP_QUIZ_CPT ) );
					break;
				case 'evaluate_questions':
				case 'evaluate_mark':
					$results_evaluate = $this->evaluate_course_by_question( $evaluate_type );
					break;
				default:
					$results_evaluate = apply_filters( 'learn-press/evaluate_passed_conditions', $results, $evaluate_type, $this );
					break;
			}

			if ( ! is_array( $results_evaluate ) ) {
				$results_evaluate = array(
					'result' => 0,
					'pass'   => 0,
				);
			}

			$results_evaluate['result'] = round( $results_evaluate['result'], 2 );

			$completed_items = intval( $count_items_completed->count_status ?? 0 );

			$item_types = learn_press_get_course_item_types();
			foreach ( $item_types as $item_type ) {
				$item_type_key = str_replace( 'lp_', '', $item_type );

				$items[ $item_type_key ] = array(
					'completed' => $count_items_completed->{$item_type . '_status_completed'} ?? 0,
					'passed'    => $count_items_completed->{$item_type . '_graduation_passed'} ?? 0,
					'total'     => $courseModel->count_items( $item_type ),
				);
			}

			$results = array_merge(
				$results_evaluate,
				compact( 'count_items', 'completed_items', 'items', 'evaluate_type' )
			);

			$results = apply_filters(
				'learn-press/update-course-results',
				$results,
				$this->item_id,
				$this->user_id,
				$this
			);

			LP_Cache::cache_load_first( 'set', $key_first_cache, $results );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $results;
	}

	/**
	 * Check user can retake course or not.
	 *
	 * @move from class-lp-user.php method can_retry_course since 4.0.0
	 * @use LP_User::can_retry_course
	 * @return int|mixed|null
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function can_retake() {
		$flag = 0;

		try {
			$course = $this->get_course_model();
			if ( ! $course ) {
				return $flag;
			}

			$retake_option = (int) $course->get_meta_value_by_key( CoursePostModel::META_KEY_RETAKE_COUNT, 0 );
			if ( $retake_option > 0 ) {
				/**
				 * Check course is finished
				 * Check duration is blocked
				 */
				if ( ! $this->has_finished() ) {
					if ( 0 !== $this->timestamp_remaining_duration() ) {
						throw new Exception();
					}
				}

				$retaken          = $this->get_retaken_count();
				$can_retake_times = $retake_option - $retaken;

				if ( $can_retake_times > 0 ) {
					$flag = $can_retake_times;
				}
			}
		} catch ( Exception $e ) {

		}

		return apply_filters( 'learn-press/user/course/can-retake', $flag, $this );
	}

	/**
	 * Get retaken count of user attend course.
	 *
	 * @return int
	 */
	public function get_retaken_count(): int {
		return (int) $this->get_meta_value_from_key( '_lp_retaken_count', 0 );
	}

	/**
	 * Check time remaining course when enable duration expire
	 * Value: -1 is no limit (default)
	 * Value: 0 is block
	 * Administrator || (is instructor && is author course) will be not block.
	 *
	 * @return int second
	 * @since 4.0.0
	 * @author tungnx
	 * @version 1.0.1
	 */
	public function timestamp_remaining_duration(): int {
		$timestamp_remaining = - 1;
		$user                = $this->get_user_model();
		/**
		 * @var CourseModel $course
		 */
		$course = $this->get_course_model();
		if ( ! $user || ! $course ) {
			return $timestamp_remaining;
		}

		$author = $course->get_author_model();
		if ( ! $author ) {
			return $timestamp_remaining;
		}

		$user_id = $user->get_id();

		if ( current_user_can( 'administrator' ) ||
			( current_user_can( LP_TEACHER_ROLE ) && $author->get_id() === $user_id ) ) {
			return $timestamp_remaining;
		}

		if ( 0 === (int) $course->get_duration() ) {
			return $timestamp_remaining;
		}

		if ( ! $course->enable_block_when_expire() ) {
			return $timestamp_remaining;
		}

		$course_start_time_str = $this->get_start_time();
		$course_start_time     = new LP_Datetime( $course_start_time_str );
		$course_start_time     = $course_start_time->get_raw_date();
		$duration              = $course->get_duration();
		$timestamp_expire      = strtotime( $course_start_time . ' +' . $duration );
		$timestamp_current     = time();
		$timestamp_remaining   = $timestamp_expire - $timestamp_current;

		if ( $timestamp_remaining < 0 ) {
			$timestamp_remaining = 0;
		}

		return apply_filters( 'learnpress/course/block_duration_expire/timestamp_remaining', $timestamp_remaining );
	}

	/**
	 * Get completed items.
	 *
	 * @return object
	 * @editor tungnx
	 * @modify 4.1.4.1
	 * @since 4.0.0
	 * @version 4.0.1
	 */
	public function count_items_completed() {
		$lp_user_items_db      = LP_User_Items_DB::getInstance();
		$count_items_completed = new stdClass();

		try {
			$course = learn_press_get_course( $this->item_id );
			if ( ! $course ) {
				throw new Exception( 'Course is invalid!' );
			}

			$user_course = $this->get_last_user_course();
			if ( ! $user_course ) {
				throw new Exception( 'User course is invalid!' );
			}

			$filter_count             = new LP_User_Items_Filter();
			$filter_count->parent_id  = $user_course->user_item_id;
			$filter_count->item_id    = $this->item_id;
			$filter_count->user_id    = $this->user_id;
			$filter_count->status     = LP_ITEM_COMPLETED;
			$filter_count->graduation = LP_COURSE_GRADUATION_PASSED;
			$count_items_completed    = $lp_user_items_db->count_items_of_course_with_status( $filter_count );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $count_items_completed;
	}

	/**
	 * Get child item ids by type item
	 *
	 * @return object|null
	 */
	public function get_last_user_course() {
		$lp_user_items_db = LP_User_Items_DB::getInstance();
		$user_course      = null;

		try {
			$filter_user_course          = new LP_User_Items_Filter();
			$filter_user_course->item_id = $this->item_id;
			$filter_user_course->user_id = $this->user_id;
			$user_course                 = $lp_user_items_db->get_last_user_course( $filter_user_course );
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ':' . $e->getMessage() );
		}

		return $user_course;
	}

	/**
	 * Get graduation of course.
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function get_graduation(): string {
		return $this->graduation;
	}

	/**
	 * Check course is passed or not.
	 *
	 * @return bool
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function is_passed(): bool {
		return $this->graduation === LP_COURSE_GRADUATION_PASSED;
	}

	/**
	 * Evaluate course result by lessons.
	 *
	 * @param $count_items_completed
	 * @param int $total_item_lesson
	 *
	 * @return array
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	protected function evaluate_course_by_lesson( $count_items_completed, int $total_item_lesson = 0 ): array {
		$evaluate = array(
			'result' => 0,
			'pass'   => 0,
		);

		$count_items_completed = intval( $count_items_completed->{LP_LESSON_CPT . '_status_completed'} ?? 0 );

		if ( $total_item_lesson && $count_items_completed ) {
			$evaluate['result'] = $count_items_completed * 100 / $total_item_lesson;
		}

		$passing_condition = $this->get_course_model()->get_passing_condition();
		if ( $evaluate['result'] >= $passing_condition ) {
			$evaluate['pass'] = 1;
		}

		return $evaluate;
	}

	/**
	 * Evaluate course result by final quiz.
	 *
	 * @return array
	 */
	protected function evaluate_course_by_final_quiz(): array {
		$lp_user_items_db       = LP_User_Items_DB::getInstance();
		$lp_user_item_result_db = LP_User_Items_Result_DB::instance();
		$evaluate               = array(
			'result' => 0,
			'pass'   => 0,
		);

		try {
			$quiz_final_id = get_post_meta( $this->get_course_model()->get_id(), '_lp_final_quiz', true );
			if ( ! $quiz_final_id ) {
				throw new Exception( '' );
			}

			$quiz_final = learn_press_get_quiz( $quiz_final_id );

			if ( ! $quiz_final ) {
				throw new Exception( 'Quiz final invalid' );
			}

			$user_course = $this->get_last_user_course();

			if ( ! $user_course ) {
				throw new Exception( 'User course not exists' );
			}

			$filter             = new LP_User_Items_Filter();
			$filter->query_type = 'get_row';
			$filter->parent_id  = $user_course->user_item_id;
			$filter->item_type  = LP_QUIZ_CPT;
			$filter->item_id    = $quiz_final_id;
			$user_quiz          = $lp_user_items_db->get_user_course_items_by_item_type( $filter );

			if ( ! $user_quiz ) {
				throw new Exception();
			}

			// Get result did quiz
			$quiz_result = $lp_user_item_result_db->get_result( $user_quiz->user_item_id );

			if ( $quiz_result ) {
				if ( ! isset( $quiz_result['result'] ) ) {
					$evaluate['result'] = $quiz_result['user_mark'] * 100 / $quiz_result['mark'];
				} else {
					$evaluate['result'] = $quiz_result['result'];
				}

				$passing_condition = floatval( $quiz_final->get_data( 'passing_grade', 0 ) );
				if ( $evaluate['result'] >= $passing_condition ) {
					$evaluate['pass'] = 1;
				}
			}
		} catch ( Throwable $e ) {

		}

		return $evaluate;
	}

	/**
	 * Evaluate course results by count quizzes passed/all quizzes.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	protected function evaluate_course_by_quizzes_passed( $count_items_completed, $total_item_quizzes ): array {
		$evaluate = array(
			'result' => 0,
			'pass'   => 0,
		);

		$count_items_completed = intval( $count_items_completed->{LP_QUIZ_CPT . '_graduation_passed'} ?? 0 );

		if ( $total_item_quizzes && $count_items_completed ) {
			$evaluate['result'] = $count_items_completed * 100 / $total_item_quizzes;

			$passing_condition = $this->get_course_model()->get_passing_condition();
			if ( $evaluate['result'] >= $passing_condition ) {
				$evaluate['pass'] = 1;
			}
		}

		return $evaluate;
	}

	/**
	 * Evaluate course results by count questions true/all questions.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	private function evaluate_course_by_questions( &$evaluate, $lp_quizzes, $total_questions ) {
		$lp_user_item_results_db = LP_User_Items_Result_DB::instance();
		$count_questions_correct = 0;

		// get questions correct
		foreach ( $lp_quizzes as $lp_quiz ) {
			$lp_quiz_result = $lp_user_item_results_db->get_result( $lp_quiz->user_item_id );
			if ( $lp_quiz_result ) {
				$count_questions_correct += $lp_quiz_result['question_correct'];
			}
		}

		if ( $total_questions && $count_questions_correct ) {
			$evaluate['result'] = $count_questions_correct * 100 / $total_questions;

			$passing_condition = $this->get_course_model()->get_passing_condition();
			if ( $evaluate['result'] >= $passing_condition ) {
				$evaluate['pass'] = 1;
			}
		}
	}

	/**
	 * Evaluate course results by total mark of questions.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	private function evaluate_course_by_mark( &$evaluate, $lp_quizzes, $total_mark_questions ) {
		$lp_user_item_results_db       = LP_User_Items_Result_DB::instance();
		$count_mark_questions_receiver = 0;

		foreach ( $lp_quizzes as $lp_quiz ) {
			$lp_quiz_result = $lp_user_item_results_db->get_result( $lp_quiz->user_item_id );
			if ( $lp_quiz_result ) {
				$count_mark_questions_receiver += $lp_quiz_result['user_mark'];
			}
		}

		if ( $count_mark_questions_receiver && $total_mark_questions ) {
			$evaluate['result'] = $count_mark_questions_receiver * 100 / $total_mark_questions;

			$passing_condition = $this->get_course_model()->get_passing_condition();
			if ( $evaluate['result'] >= $passing_condition ) {
				$evaluate['pass'] = 1;
			}
		}
	}

	/**
	 * Evaluate course results by total mark of questions.
	 *
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.1
	 */
	protected function evaluate_course_by_question( string $evaluate_type ): array {
		$lp_user_items_db = LP_User_Items_DB::getInstance();
		$evaluate         = array(
			'result' => 0,
			'pass'   => 0,
		);

		try {
			$user_course = $this->get_last_user_course();

			if ( ! $user_course ) {
				throw new Exception( 'User course not exists!' );
			}

			// get quiz_ids
			$filter_get_quiz_ids            = new LP_User_Items_Filter();
			$filter_get_quiz_ids->parent_id = $user_course->user_item_id;
			$filter_get_quiz_ids->item_type = LP_QUIZ_CPT;
			$lp_quizzes                     = $lp_user_items_db->get_user_course_items_by_item_type( $filter_get_quiz_ids );

			// Get total questions, mark
			$course = $this->get_course_model();

			$total_questions     = 0;
			$total_mark_question = 0;

			// Get all items' course
			$sections_items = $course->get_section_items();

			foreach ( $sections_items as $section_items ) {
				foreach ( $section_items->items as $item ) {
					$itemObj = LP_Course_Item::get_item( $item->id );
					if ( ! $itemObj instanceof LP_Course_Item ) {
						continue;
					}

					if ( $item->type == LP_QUIZ_CPT ) {
						$total_questions     += count( $itemObj->get_question_ids() );
						$total_mark_question += $itemObj->get_mark();
					}
				}
			}
			// End get total questions, mark

			switch ( $evaluate_type ) {
				case 'evaluate_questions':
					$this->evaluate_course_by_questions( $evaluate, $lp_quizzes, $total_questions );
					break;
				case 'evaluate_mark':
					$this->evaluate_course_by_mark( $evaluate, $lp_quizzes, $total_mark_question );
					break;
				default:
					break;
			}

			// Get results of each quiz - has questions
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return $evaluate;
	}

	protected function count_item_completed() {
	}

	/**
	 * Check user can finish course or not.
	 *
	 * @return bool|WP_Error
	 *
	 * @since 4.2.7.5
	 * @version 1.0.0
	 */
	public function can_finish() {
		$can_finish = true;

		try {
			$courseModel = $this->get_course_model();
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not exists!', 'learnpress' ) );
			}

			if ( $this->has_finished() ) {
				throw new Exception( __( 'Course is finished!', 'learnpress' ) );
			}

			if ( ! $this->has_enrolled() ) {
				throw new Exception( __( 'Course is not enroll!', 'learnpress' ) );
			}

			$course_results = $this->calculate_course_results();

			$graduation = $this->get_graduation();
			if ( $graduation !== LP_GRADUATION_PASSED ) {
				$allow_finish_when_all_item_completed = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_HAS_FINISH, 'yes' );
				if ( $allow_finish_when_all_item_completed ) {
					$course_total_items_obj = $courseModel->get_total_items();
					if ( $course_total_items_obj && $course_results['completed_items'] < $course_total_items_obj->count_items ) {
						throw new Exception( __( 'You must complete all items in course', 'learnpress' ) );
					}
				} else {
					throw new Exception( __( 'You must passed course', 'learnpress' ) );
				}
			}
		} catch ( Throwable $e ) {
			$can_finish = new WP_Error( 'lp_user_course_can_finish_err', $e->getMessage() );
		}

		return $can_finish;
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 *
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function clean_caches() {
		$key_cache         = "userCourseModel/find/{$this->user_id}/{$this->ref_id}/{$this->item_type}";
		$lpUserCourseCache = new LP_Cache();
		$lpUserCourseCache->clear( $key_cache );

		parent::clean_caches();
		// Clear cache total students enrolled of a course.
		$lp_course_cache = new LP_Course_Cache( true );
		$lp_course_cache->clean_total_students_enrolled( $this->item_id );
		$lp_course_cache->clean_total_students_enrolled_or_purchased( $this->item_id );
		// Clear cache count students of many course
		$lp_courses_cache = new LP_Courses_Cache( true );
		$lp_courses_cache->clear_cache_on_group( LP_Courses_Cache::KEYS_COUNT_STUDENT_COURSES );
	}
}
