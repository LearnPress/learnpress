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
	 * @var array
	 *
	 * @since 3.3.0
	 */
	public static $object_support_features = array();

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
		$item = self::course_item();

		if ( $item ) {
			return $type === get_post_type( $item->get_id() );
		}

		return false;
	}

	/**
	 * @param bool $get_only_id
	 * Todo: new call not use $get_only_id, only call LP_Global::courses()
	 * Recommend use learn_press_get_course()
	 *
	 * @editor tungnx
	 * @since 3.0.0
	 * @version 1.0.1
	 * @return LP_Course|bool|int
	 */
	public static function course( $get_only_id = false ) {
		$lp_course = learn_press_get_course();

		// Fix for old version use param $get_only_id
		if ( $get_only_id ) {
			if ( $lp_course ) {
				return $lp_course->get_id();
			} else {
				return 0;
			}
		} else {
			return $lp_course;
		}
	}

	public static function set_course( $course ) {
		global $lp_course;

		if ( self::$_course === false ) {
			self::$_course = $lp_course;
		}
		$lp_course = $course;
	}

	/**
	 * @return LP_User|LP_User_Guest|false
	 */
	public static function user() {
		/**
		 * @see learn_press_setup_user
		 */
		global $lp_user;

		return $lp_user;
	}

	/**
	 * Set global user
	 *
	 * @param LP_User $user
	 */
	//  public static function set_user( $user = null ) {
	//      global $lp_user;
	//
	//      var_dump(debug_backtrace());
	//
	//      if ( false === self::$_user ) {
	//          self::$_user = $lp_user;
	//      }
	//
	//      $lp_user = $user;
	//  }

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
		global $profile;

		if ( ! $profile ) {
			//self::$_profile = $profile = LP_Profile::instance( get_current_user_id() );
		}

		return LP_Profile::instance();

		/**
		 * Get origin global $profile (stored in class) if $global = TRUE
		 */
		if ( $global ) {

			/**
			 * If $reset = TRUE then set global $profile to origin global $profile (stored in class)
			 */
			if ( ! $reset ) {
				return self::$_profile;
			}

			$profile = self::$_profile;
		}

		return $profile;
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

	/**
	 * @param string $object_type
	 * @param string $feature
	 * @param string $type
	 *
	 * @return mixed
	 * @since 3.3.0
	 *
	 */
	public static function add_object_feature( $object_type, $feature, $type = 'yes' ) {
		$namespace   = explode( '.', $object_type );
		$object_type = $namespace[0];
		$namespace   = isset( $namespace[1] ) ? $namespace[1] : false;

		if ( ! isset( self::$object_support_features[ $object_type ] ) ) {
			self::$object_support_features[ $object_type ] = array();
		}

		if ( $namespace ) {
			if ( ! isset( self::$object_support_features[ $object_type ][ $namespace ] ) ) {
				self::$object_support_features[ $object_type ][ $namespace ] = array();
			}

			return self::$object_support_features[ $object_type ][ $namespace ][ $feature ] = $type === null ? 'yes' : $type;
		}

		return self::$object_support_features[ $object_type ][ $feature ] = $type === null ? 'yes' : $type;
	}

	/**
	 * Checks if an object is support a feature.
	 *
	 * @param string $object_type
	 * @param string $feature
	 * @param mixed $type
	 *
	 * @return bool
	 * @since 3.3.0
	 *
	 */
	public static function object_is_support_feature( $object_type, $feature, $type = null ) {
		$objects     = self::$object_support_features;
		$namespace   = explode( '.', $object_type );
		$object_type = $namespace[0];
		$namespace   = isset( $namespace[1] ) ? $namespace[1] : false;

		if ( empty( $objects[ $object_type ] ) ) {
			return false;
		}

		if ( $namespace && empty( $objects[ $object_type ][ $namespace ] ) ) {
			return false;
		}

		if ( $namespace ) {
			$is_support = array_key_exists( $feature, $objects[ $object_type ][ $namespace ] ) ? true : false;

			if ( $type && $is_support ) {
				return $objects[ $object_type ][ $namespace ][ $feature ] === $type;
			}

			return $is_support;
		}

		$is_support = array_key_exists( $feature, $objects[ $object_type ] ) ? true : false;

		if ( $type && $is_support ) {
			return $objects[ $object_type ][ $feature ] === $type;
		}

		return $is_support;
	}

	/**
	 * Get all features that an object support.
	 *
	 * @param string $object_type
	 *
	 * @return array|mixed
	 * @since 3.3.0
	 *
	 */
	public static function get_object_supports( $object_type ) {
		$namespace   = explode( '.', $object_type );
		$object_type = $namespace[0];
		$namespace   = isset( $namespace[1] ) ? $namespace[1] : false;

		if ( empty( self::$object_support_features[ $object_type ] ) ) {
			return array();
		}

		if ( $namespace ) {
			if ( empty( self::$object_support_features[ $object_type ][ $namespace ] ) ) {
				return array();
			}
		}

		return self::$object_support_features[ $object_type ];
	}

	/**
	 * @param $user
	 *
	 * @return false
	 * @deprecated 4.0.0
	 */
	public function set_user( $user ) {
		return false;
	}
}
