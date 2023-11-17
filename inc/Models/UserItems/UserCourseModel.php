<?php

/**
 * Class UserItemModel
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItems;

use LP_Cache;
use LP_Course;
use LP_Course_Cache;
use LP_Courses_Cache;
use LP_User;
use LP_User_Items_DB;
use LP_User_Items_Filter;
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
	 * Get quiz model
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
		$lp_courses_cache
			->set_action_thim_cache( Thim_Cache_DB::ACTION_INSERT )
			->set_cache( $key_cache, $count );
		$lp_courses_cache_keys = new LP_Courses_Cache( true );
		$lp_courses_cache_keys->save_cache_keys_count_student_courses( $key_cache );
		LP_Cache::cache_load_first( 'set', $key_cache, $count );

		return $count;
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
}
