<?php

/**
 * Class LP_Course_Cache
 *
 * @author tungnx
 * @since 4.0.9
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Course_Cache extends LP_Cache {
	protected static $instance;
	protected $key_group_child                          = 'course';
	protected $key_total_students_enrolled              = 'total-students-enrolled';
	protected $key_total_students_enrolled_or_purchased = 'total-students-enrolled-or-purchased';

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct( $has_thim_cache = false ) {
		parent::__construct( $has_thim_cache );
	}

	public function set_total_students_enrolled( $course_id, $total ) {
		$key = "{$course_id}/{$this->key_total_students_enrolled}";
		$this->set_cache( $key, $total );
	}

	public function get_total_students_enrolled( $course_id ) {
		$key = "{$course_id}/{$this->key_total_students_enrolled}";
		return $this->get_cache( $key );
	}

	public function clean_total_students_enrolled( $course_id ) {
		$key = "{$course_id}/{$this->key_total_students_enrolled}";
		$this->clear( $key );
	}

	public function set_total_students_enrolled_or_purchased( $course_id, $total ) {
		$key = "{$course_id}/{$this->key_total_students_enrolled_or_purchased}";
		$this->set_cache( $key, $total );
		LP_Cache::cache_load_first( 'set', $key, $total );
	}

	public function get_total_students_enrolled_or_purchased( $course_id ) {
		$key   = "{$course_id}/{$this->key_total_students_enrolled_or_purchased}";
		$total = LP_Cache::cache_load_first( 'get', $key );
		if ( false !== $total ) {
			return $total;
		}

		$total = $this->get_cache( $key );
		LP_Cache::cache_load_first( 'set', $key, $total );

		return $total;
	}

	public function clean_total_students_enrolled_or_purchased( $course_id ) {
		$key = "{$course_id}/{$this->key_total_students_enrolled_or_purchased}";
		$this->clear( $key );
	}
}
