<?php

/**
 * Class LP_Global
 */
class LP_Global {
	/**
	 * @var array
	 */
	public static $users = array();

	/**
	 * @var array
	 */
	public static $courses = array();

	/**
	 * @var array
	 */
	public static $lessons = array();

	/**
	 * @var array
	 */
	public static $quizzes = array();

	/**
	 * @var array
	 */
	public static $questions = array();

	/**
	 * @var bool
	 */
	protected static $_user = false;

	/**
	 * @var bool
	 */
	protected static $_course = false;

	/**
	 * @var bool
	 */
	protected static $_course_item = false;

	/**
	 * @var LP_Profile
	 */
	protected static $_profile = false;

	/**
	 * @var array
	 */
	public static $custom_posts = array();

	/**
	 * @return LP_Quiz|LP_Lesson
	 */
	public static function course_item() {
		global $lp_course_item;

		return $lp_course_item;
	}

	public static function set_course_item( $course_item ) {
		global $lp_course_item;

		if ( self::$_course_item === false ) {
			self::$_course_item = $lp_course_item;
		}

		$lp_course_item = $course_item;
	}

	/**
	 * Check if current course item is viewing is a $type
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function is_course_item_type( $type ) {
		if ( $item = self::course_item() ) {
			return $type === get_post_type( $item->get_id() );
		}

		return false;
	}

	/**
	 * @param bool $id
	 *
	 * @return LP_Course|bool|int
	 */
	public static function course( $id = false ) {
		global $lp_course;

		return is_a( $lp_course, 'LP_Course' ) ? ( $id ? $lp_course->get_id() : $lp_course ) : false;
	}

	public static function set_course( $course ) {
		global $lp_course;

		if ( self::$_course === false ) {
			self::$_course = $lp_course;
		}
		$lp_course = $course;
	}

	/**
	 * @return LP_User
	 */
	public static function user() {
		global $lp_user;

		return $lp_user;
	}

	public static function set_user( $user ) {
		global $lp_user;

		if ( self::$_user === false ) {
			self::$_user = $lp_user;
		}

		$lp_user = $user;
	}

	/**
	 * Alias of course item for highlighting in dev
	 *
	 * @return LP_Quiz|bool
	 */
	public static function course_item_quiz() {
		$item = self::course_item();

		return $item instanceof LP_Quiz ? $item : false;
	}

	/**
	 * @return LP_Question
	 */
	public static function quiz_question() {
		global $lp_quiz_question;

		return $lp_quiz_question;
	}

	/**
	 * Reset global variables to default
	 */
	public static function reset() {
		global $lp_user, $lp_course, $lp_course_item, $lp_quiz_question;

		if ( self::$_user ) {
			$lp_user = self::$_user;
		}

		if ( self::$_course ) {
			$lp_course = self::$_course;
		}

		if ( self::$_course_item ) {
			$lp_course_item = self::$_course_item;
		}
	}

	/**
	 * @param bool $global
	 * @param bool $reset
	 *
	 * @return LP_Profile|WP_Error
	 */
	public static function profile( $global = false, $reset = false ) {
		return LP_Profile::instance();
	}

	public static function init() {
		global $profile;
		self::$_profile = $profile = LP_Profile::instance( get_current_user_id() );
	}

	/**
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	public static function get_custom_posts( $key ) {
		if ( array_key_exists( $key, self::$custom_posts ) ) {
			return self::$custom_posts[ $key ];
		}

		return false;
	}
}