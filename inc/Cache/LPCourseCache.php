<?php

namespace LearnPress\Cache;

defined( 'ABSPATH' ) || exit();

/**
 * Class LPCourseCache
 *
 * @since 4.1.11
 * @version 1.0.0
 */
class LPCourseCache extends LPCache {
	protected static $instance;
	protected $key_group_child                          = 'course';
	protected $key_total_students_enrolled_or_purchased = 'total-students-enrolled-or-purchased';

	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function set_total_students_enrolled_or_purchased( int $course_id, int $total ): bool {
		$key = "{$course_id}/{$this->key_total_students_enrolled_or_purchased}";
		return $this->set_cache( $key, $total );
	}

	public function get_total_students_enrolled_or_purchased( int $course_id ) {
		$key = "{$course_id}/{$this->key_total_students_enrolled_or_purchased}";
		return $this->get_cache( $key );
	}

	public function clean_total_students_enrolled_or_purchased( int $course_id ): bool {
		$key = "{$course_id}/{$this->key_total_students_enrolled_or_purchased}";
		return $this->clear( $key );
	}
}
