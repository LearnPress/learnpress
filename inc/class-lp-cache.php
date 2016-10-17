<?php

/**
 * Class LP_Cache
 *
 * Cache helper using functions of WP Cache with default group is learnpress
 *
 * @since 1.1
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Cache {

	/**
	 * @var array
	 */
	protected static $_data = array();

	/**
	 * @var string
	 */
	protected static $_group = 'learnpress';

	/**
	 * @var string
	 */
	protected static $_quiz_status = 'user-quiz-status';

	/**
	 * @var string
	 */
	protected static $_lesson_status = 'user-lesson-status';

	/**
	 * @var string
	 */
	protected static $_count_retaken_quiz = 'user-count-retaken-quiz';

	/**
	 * @var string
	 */
	protected static $_completed_lessons = 'user-completed-lessons';

	/**
	 * @var string
	 */
	protected static $_evaluate_course_by_lesson = 'user-evaluate-course-by-lesson';

	/**
	 * @var string
	 */
	protected static $_quiz_history = 'user-quiz-history';

	/**
	 * Set data to cache
	 *
	 * @param int|string $key
	 * @param mixed      $value
	 * @param int        $expire
	 * @param int|string $group
	 *
	 * @return mixed
	 */
	public static function set( $key, $value, $expire = 0, $group = 'learnpress' ) {
		return wp_cache_set( $key, $value, $group, $expire );
	}

	/**
	 * Get data from cache
	 *
	 * @param int|string $key
	 * @param boolean    $force
	 * @param            $found
	 * @param int|string $group
	 *
	 * @return bool|mixed
	 */
	public static function get( $key, $force = false, &$found = 0, $group = 'learnpress' ) {
		return wp_cache_get( $key, $group, $force, $found );
	}

	/**
	 * Helper function to set user quiz status to cache
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_status( $key, $value = false ) {
		if ( is_array( $key ) || is_object( $key ) ) {
			return wp_cache_set( self::$_quiz_status, $key, self::$_group );
		} else {
			return self::_set_cache( self::$_quiz_status, $key, $value );
		}
	}

	/**
	 * Helper function to get user quiz status from cache
	 *
	 * @param bool|string $key
	 * @param mixed       $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_status( $key = false, $def = false ) {
		return self::_get_cache( self::$_quiz_status, $key, $def );
	}

	/**
	 * Helper function to set user lesson status to cache
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_lesson_status( $key, $value ) {
		return self::_set_cache( self::$_lesson_status, $key, $value );
	}

	/**
	 * Helper function to get user lesson status from cache
	 *
	 * @param bool|string $key
	 * @param mixed       $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_lesson_status( $key = false, $def = false ) {
		return self::_get_cache( self::$_lesson_status, $key, $def );
	}

	/**
	 * Helper function to set user lesson status to cache
	 *
	 * @param string $key_or_value
	 * @param mixed  $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_history( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_quiz_history, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_quiz_history, $key_or_value, $value );
	}

	/**
	 * Helper function to get user lesson status from cache
	 *
	 * @param bool|string $key
	 * @param mixed       $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_history( $key = false, $def = false ) {
		return self::_get_cache( self::$_quiz_history, $key, $def );
	}

	/**
	 * Helper function to set user lesson status to cache
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_count_retaken_quiz( $key, $value ) {
		return self::_set_cache( self::$_count_retaken_quiz, $key, $value );
	}

	/**
	 * Helper function to get user lesson status from cache
	 *
	 * @param bool|string $key
	 * @param mixed       $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_count_retaken_quiz( $key = false, $def = false ) {
		return self::_get_cache( self::$_count_retaken_quiz, $key, $def );
	}

	/**
	 * Helper function to set user lesson status to cache
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_completed_lessons( $key, $value ) {
		return self::_set_cache( self::$_completed_lessons, $key, $value );
	}

	/**
	 * Helper function to get user lesson status from cache
	 *
	 * @param bool|string $key
	 * @param mixed       $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_completed_lessons( $key = false, $def = false ) {
		return self::_get_cache( self::$_completed_lessons, $key, $def );
	}

	/**
	 * Helper function to set user lesson status to cache
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_evaluate_course_by_lesson( $key, $value ) {
		return self::_set_cache( self::$_evaluate_course_by_lesson, $key, $value );
	}

	/**
	 * Helper function to get user lesson status from cache
	 *
	 * @param bool|string $key
	 * @param mixed       $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_evaluate_course_by_lesson( $key = false, $def = false ) {
		return self::_get_cache( self::$_evaluate_course_by_lesson, $key, $def );
	}

	/**
	 * Helper function to set user lesson status to cache
	 *
	 * @param string $key_or_value
	 * @param mixed  $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_completed_items( $key_or_value, $value = false ) {
		//return self::_set_cache( 'user-completed-items', $key, $value );
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'user-completed-items', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'user-completed-items', $key_or_value, $value );
	}

	/**
	 * Helper function to get user lesson status from cache
	 *
	 * @param bool|string $key
	 * @param mixed       $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_completed_items( $key = false, $def = false ) {
		return self::_get_cache( 'user-completed-items', $key, $def );
	}

	/**
	 * @param $key_or_value
	 * @param $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_enrolled_courses( $key_or_value, $value ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'user-enrolled-courses', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'user-enrolled-courses', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_enrolled_courses( $key = false, $def = false ) {
		return self::_get_cache( 'user-enrolled-courses', $key, $def );
	}

	/**
	 * Set cache for user's course information
	 *
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_finished_courses( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'user-finished-courses', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'user-finished-courses', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_finished_courses( $key = false, $def = false ) {
		return self::_get_cache( 'user-finished-courses', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_course_info( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'user-course-info', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'user-course-info', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_course_info( $key = false, $def = false ) {
		return self::_get_cache( 'user-course-info', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_course_curriculum( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'course-curriculum', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'course-curriculum', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_course_curriculum( $key = false, $def = false ) {
		return self::_get_cache( 'course-curriculum', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_item_statuses( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'course-item-statuses', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'course-item-statuses', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_item_statuses( $key = false, $def = false ) {
		return self::_get_cache( 'course-item-statuses', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_questions( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'quiz-questions', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'quiz-questions', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_questions( $key = false, $def = false ) {
		return self::_get_cache( 'quiz-questions', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_results( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'quiz-results', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'quiz-results', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_results( $key = false, $def = false ) {
		return self::_get_cache( 'quiz-results', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_params( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'quiz-params', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'quiz-params', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_params( $key = false, $def = false ) {
		return self::_get_cache( 'quiz-params', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_post_names( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'post-names', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'post-names', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_post_names( $key = false, $def = false ) {
		return self::_get_cache( 'post-names', $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_user_course_order( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( 'user-course-order', $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( 'post-names', $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_user_course_order( $key = false, $def = false ) {
		return self::_get_cache( 'user-course-order', $key, $def );
	}

	/**
	 * @param string $section
	 */
	public static function flush( $section = '' ) {
		if ( func_num_args() > 1 ) {
			foreach ( func_get_args() as $arg ) {
				self::flush( $arg );
			}
			return;
		}
		if ( $section ) {
			if ( is_string( $section ) ) {
				wp_cache_set( $section, '', self::$_group );
			} else {
				foreach ( (array) $section as $sec ) {
					self::flush( $sec );
				}
			}
		} else {
			wp_cache_flush();
		}
	}

	/**
	 * @param int|string $key
	 * @param int|string $field
	 * @param  mixed     $value
	 *
	 * @return array|bool|mixed
	 */
	private static function _set_cache( $key, $field, $value ) {
		$cached = wp_cache_get( $key, self::$_group );
		if ( !is_array( $cached ) ) {
			$cached = array();
		}
		$cached[$field] = $value;
		wp_cache_set( $key, $cached, self::$_group );
		return $cached;
	}

	/**
	 * @param       $key
	 * @param bool  $field
	 * @param mixed $def
	 *
	 * @return array|bool|mixed
	 */
	private static function _get_cache( $key, $field = false, $def = false ) {
		$cached = wp_cache_get( $key, self::$_group );
		if ( is_array( $cached ) && $field ) {
			$return = !array_key_exists( $field, $cached ) ? $cached[$field] : false;
		} else {
			$return = $cached;
		}
		return $return ? $return : $def;
	}
}