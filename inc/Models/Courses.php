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
use LP_Courses_Cache;
use Thim_Cache_DB;

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
		// Check cache
		$key_cache        = 'count-courses-free-' . md5( json_encode( $filter ) );
		$lp_courses_cache = new LP_Courses_Cache( true );
		$count            = $lp_courses_cache->get_cache( $key_cache );
		if ( false !== $count ) {
			return $count;
		}

		$lp_course_db = LP_Course_DB::getInstance();
		$count        = $lp_course_db->count_course_free( $filter );

		// Set cache
		$lp_courses_cache
			->set_action_thim_cache( Thim_Cache_DB::ACTION_INSERT )
			->set_cache( $key_cache, $count );
		$lp_courses_cache_keys = new LP_Courses_Cache( true );
		$lp_courses_cache_keys->save_cache_keys_count_courses_free( $key_cache );

		return $count;
	}
}
