<?php

/**
 * Class UserItemModel
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItems;

use LP_Course_Cache;

class UserItemCourseModel extends UserItemModel {
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

	public function clean_caches() {
		parent::clean_caches();
		// Clear cache total students enrolled.
		$lp_course_cache = new LP_Course_Cache( true );
		$lp_course_cache->clean_total_students_enrolled( $this->item_id );
		$lp_course_cache->clean_total_students_enrolled_or_purchased( $this->item_id );
	}
}
