<?php

/**
 * Class Courses
 *
 * Handle all method about list courses
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5.4
 */

namespace LearnPress\Models;

use LP_Course_DB;
use LP_Course_Filter;

class Courses {
	/**
	 * Count total courses free
	 *
	 * @param LP_Course_Filter $filter
	 * @return int
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public static function count_course_free( LP_Course_Filter $filter ): int {
		//Todo: Check cache
		$lp_course_db = LP_Course_DB::getInstance();

		//Todo: Set cache
		$count = $lp_course_db->count_course_free( $filter );

		return $count;
	}
}
