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
	//protected static $_data = array();

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
	protected static $_user_completed_items = 'user-completed-items';

	/**
	 * @var string
	 */
	protected static $_user_enrolled_courses = 'user-enrolled-courses';

	/**
	 * @var string
	 */
	protected static $_user_finished_courses = 'user-finished-courses';

	/**
	 * @var string
	 */
	protected static $_user_course_info = 'user-course-info';

	/**
	 * @var string
	 */
	protected static $_course_curriculum = 'course-curriculum';

	/**
	 * @var string
	 */
	protected static $_course_item_statuses = 'course-item-statuses';

	/**
	 * @var string
	 */
	protected static $_quiz_questions = 'quiz-questions';

	/**
	 * @var string
	 */
	protected static $_quiz_results = 'quiz-results';

	/**
	 * @var string
	 */
	protected static $_quiz_params = 'quiz-params';

	/**
	 * @var string
	 */
	protected static $_post_names = 'post-names';

	/**
	 * @var string
	 */
	protected static $_user_course_order = 'user-course-order';

	/**
	 * @var string
	 */
	protected static $_user_profile_orders = 'user-profile-orders';

	/**
	 * @var string
	 */
	protected static $_user_item_id = 'user-item-id';

	/**
	 * @var string
	 */
	protected static $_quiz_history = 'user-quiz-history';

	/**
	 * @var string
	 */
	protected static $_quiz_grade = 'quiz-grade';

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
			wp_cache_set( self::$_user_completed_items, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_user_completed_items, $key_or_value, $value );
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
		return self::_get_cache( self::$_user_completed_items, $key, $def );
	}

	/**
	 * @param $key_or_value
	 * @param $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_enrolled_courses( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_user_enrolled_courses, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_user_enrolled_courses, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_enrolled_courses( $key = false, $def = false ) {
		return self::_get_cache( self::$_user_enrolled_courses, $key, $def );
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
			wp_cache_set( self::$_user_finished_courses, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_user_finished_courses, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_finished_courses( $key = false, $def = false ) {
		return self::_get_cache( self::$_user_finished_courses, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_course_info( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_user_course_info, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_user_course_info, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_course_info( $key = false, $def = false ) {
		return self::_get_cache( self::$_user_course_info, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_course_curriculum( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_course_curriculum, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_course_curriculum, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_course_curriculum( $key = false, $def = false ) {
		return self::_get_cache( self::$_course_curriculum, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_item_statuses( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_course_item_statuses, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_course_item_statuses, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_item_statuses( $key = false, $def = false ) {
		return self::_get_cache( self::$_course_item_statuses, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_questions( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_quiz_questions, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_quiz_questions, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_questions( $key = false, $def = false ) {
		return self::_get_cache( self::$_quiz_questions, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_results( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_quiz_results, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_quiz_results, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_results( $key = false, $def = false ) {
		return self::_get_cache( self::$_quiz_results, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_params( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_quiz_params, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_quiz_params, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_params( $key = false, $def = false ) {
		return self::_get_cache( self::$_quiz_params, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_post_names( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_post_names, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_post_names, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_post_names( $key = false, $def = false ) {
		return self::_get_cache( self::$_post_names, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_user_course_order( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_user_course_order, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_user_course_order, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_user_course_order( $key = false, $def = false ) {
		return self::_get_cache( self::$_user_course_order, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_user_profile_orders( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_user_profile_orders, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_user_profile_orders, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_user_profile_orders( $key = false, $def = false ) {
		return self::_get_cache( self::$_user_profile_orders, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_user_item_id( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_user_item_id, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_user_item_id, $key_or_value, $value );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_user_item_id( $key = false, $def = false ) {
		return self::_get_cache( self::$_user_item_id, $key, $def );
	}

	/**
	 * @param bool $key
	 * @param bool $def
	 *
	 * @return array|bool|mixed
	 */
	public static function get_quiz_grade( $key = false, $def = false ) {
		return self::_get_cache( self::$_quiz_grade, $key, $def );
	}

	/**
	 * @param      $key_or_value
	 * @param bool $value
	 *
	 * @return array|bool|mixed
	 */
	public static function set_quiz_grade( $key_or_value, $value = false ) {
		if ( func_num_args() == 1 ) {
			wp_cache_set( self::$_quiz_grade, $key_or_value, self::$_group );
			return $key_or_value;
		}
		return self::_set_cache( self::$_quiz_grade, $key_or_value, $value );
	}

	/**
	 * Flush cache by sections or LP group
	 *
	 * @param string|array $section
	 */
	public static function flush( $section = '' ) {
		$func_args = func_get_args();
		if ( func_num_args() > 1 ) {
			foreach ( $func_args as $arg ) {
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
			try {
				$reflection       = new ReflectionClass( "LP_Cache" );
				$staticProperties = $reflection->getStaticProperties();
				foreach ( $staticProperties as $key => $value ) {
					if ( in_array( $key, array( '_group' ) ) ) {
						continue;
					}
					wp_cache_delete( $value, self::$_group );
				}
			} catch ( Exception $ex ) {
			}
		}
	}

	public static function cache_flush( $url ) {
		return add_query_arg( 'cache-flush', microtime( true ), $url );
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
			$return = array_key_exists( $field, $cached ) ? $cached[$field] : false;
		} else {
			$return = $cached;
		}
		return ( $return || $def === false ) ? $return : $def;
	}

	public static function init() {
		/*try {
			self::flush();
		} catch ( Exception $ex ) {

		}
		add_action( 'init', array( __CLASS__, 'redirect' ) );*/
	}

	public static function redirect() {
		if ( !empty( $_REQUEST['cache-flush'] ) ) {
		}
	}
}

LP_Cache::init();
