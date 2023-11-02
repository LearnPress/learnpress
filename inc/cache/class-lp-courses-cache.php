<?php

/**
 * Class LP_Courses_Cache
 *
 * @author tungnx
 * @since 4.1.5
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Courses_Cache extends LP_Cache {
	/**
	 * @var LP_Courses_Cache
	 */
	protected static $instance;
	/**
	 * @var string
	 */
	protected $key_group_child = 'courses';
	/**
	 * @var string Save list keys cached to clear
	 */
	public static $keys           = 'keys';
	const KEYS_QUERY_COURSES = 'keys/query_courses';
	const KEYS_QUERY_TOTAL_COURSES = 'keys/query_courses/total';
	const KEYS_COUNT_COURSES_FREE = 'keys/count_courses_free';
	const KEYS_COUNT_STUDENT_COURSES = 'keys/count_student_courses';

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

	/**
	 * Store list keys cache of query count courses free
	 *
	 * @param string $key_cache
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function save_cache_keys_count_courses_free( string $key_cache ) {
		$this->save_cache_keys( self::KEYS_COUNT_COURSES_FREE, $key_cache );
	}

	/**
	 * Store list keys cache of query count student of courses
	 *
	 * @param string $key_cache
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function save_cache_keys_count_student_courses( string $key_cache ) {
		$this->save_cache_keys( self::KEYS_COUNT_STUDENT_COURSES, $key_cache );
	}

	/**
	 * Store list keys cache query courses
	 *
	 * @param string $key_cache
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function save_cache_keys_query_courses( string $key_cache ) {
		$this->save_cache_keys( self::KEYS_QUERY_COURSES, $key_cache );
	}
}
