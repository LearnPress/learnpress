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
use LP_Cache;
use LP_Course;
use LP_Course_Cache;
use LP_Courses_Cache;
use LP_User;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use LP_User_Items_Result_DB;
use stdClass;
use Thim_Cache_DB;
use Throwable;

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
	/**
	 * @var LP_User|null
	 */
	public $user;
	/**
	 * @var LP_Course|null
	 */
	public $course;

	public function __construct( $data = null ) {
		parent::__construct( $data );

		if ( $data ) {
			$this->get_course_model();
		}
	}

	/**
	 * Get course model
	 *
	 * @return bool|LP_Course
	 */
	public function get_course_model() {
		if ( empty( $this->course ) ) {
			$this->course = learn_press_get_course( $this->item_id );
		}

		return $this->course;
	}

	/**
	 * Find User Item by user_id, item_id, item_type.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param bool $check_cache
	 *
	 * @return false|UserItemModel|static
	 */
	public static function find( int $user_id, int $course_id, bool $check_cache = false ) {
		$filter            = new LP_User_Items_Filter();
		$filter->user_id   = $user_id;
		$filter->item_id   = $course_id;
		$filter->item_type = LP_COURSE_CPT;

		return static::get_user_item_model_from_db( $filter );
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

	public function clean_caches() {
		parent::clean_caches();
		// Clear cache total students enrolled of a course.
		$lp_course_cache = new LP_Course_Cache( true );
		$lp_course_cache->clean_total_students_enrolled( $this->item_id );
		$lp_course_cache->clean_total_students_enrolled_or_purchased( $this->item_id );
		// Clear cache count students of many course
		$lp_courses_cache = new LP_Courses_Cache( true );
		$lp_courses_cache->clear_cache_on_group( LP_Courses_Cache::KEYS_COUNT_STUDENT_COURSES );
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
	 * @version 1.0.1
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
			$course = $this->get_course_model();
			if ( empty( $course ) ) {
				throw new Exception( 'Course invalid!' );
			}

			$key_first_cache = 'calculate_course/' . $this->user_id . '/' . $course->get_id();
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

			$count_items           = $course->count_items();
			$count_items_completed = $this->count_items_completed();

			$evaluate_type = $course->get_data( 'course_result', 'evaluate_lesson' );
			switch ( $evaluate_type ) {
				case 'evaluate_lesson':
					$results_evaluate = $this->evaluate_course_by_lesson( $count_items_completed, $course->count_items( LP_LESSON_CPT ) );
					break;
				case 'evaluate_final_quiz':
					$results_evaluate = $this->evaluate_course_by_final_quiz();
					break;
				case 'evaluate_quiz':
					$results_evaluate = $this->evaluate_course_by_quizzes_passed( $count_items_completed, $course->count_items( LP_QUIZ_CPT ) );
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
					'total'     => $course->count_items( $item_type ),
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

		$passing_condition = $this->course->get_passing_condition();
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
			$quiz_final_id = get_post_meta( $this->get_course_id(), '_lp_final_quiz', true );

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

			$passing_condition = $this->course->get_passing_condition();
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

			$passing_condition = $this->course->get_passing_condition();
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

			$passing_condition = floatval( $this->course->get_passing_condition() );
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
			// Todo: Tungnx - save (questions, mark) total when save quiz, course, if not query again
			$course = $this->course;
			if ( is_int( $course ) ) {
				$course = learn_press_get_course( $course );
			}

			$total_questions     = 0;
			$total_mark_question = 0;

			// Get all items' course
			$sections_items = $course->get_full_sections_and_items_course();

			foreach ( $sections_items as $section_items ) {
				foreach ( $section_items->items as $item ) {
					$itemObj = $course->get_item( $item->id );

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
}
