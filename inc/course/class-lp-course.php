<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Course
 *
 * @extend LP_Abstract_Course
 * @since  0.9.15
 */
class LP_Course extends LP_Abstract_Course {
	protected static $_loaded = 0;

	public function __construct( $course ) {
		parent::__construct( $course );
		self::$_loaded ++;
		if ( self::$_loaded == 1 ) {
			add_filter( 'debug_data', array( __CLASS__, 'log' ) );
		}
	}

	public static function log( $data ) {
		$data[] = __CLASS__ . '( ' . self::$_loaded . ' )';

		return $data;
	}

	/**
	 * @param bool  $the_course
	 * @param array $args
	 *
	 * @return LP_Course|bool
	 */
	public static function get_course( $the_course = false, $args = array() ) {
		$the_course = self::get_course_object( $the_course );
		if ( ! $the_course ) {
			return false;
		}

		if ( ! empty( $args['force'] ) ) {
			$force = ! ! $args['force'];
			unset( $args['force'] );
		} else {
			$force = false;
		}

		$key_args = wp_parse_args( $args, array( 'id' => $the_course->ID, 'type' => $the_course->post_type ) );

		$key = LP_Helper::array_to_md5( $key_args );

		if ( $force ) {
			LP_Global::$courses[ $key ] = false;
		}

		if ( empty( LP_Global::$courses[ $key ] ) ) {
			$class_name = self::get_course_class( $the_course, $args );
			if ( is_string( $class_name ) && class_exists( $class_name ) ) {
				$course = new $class_name( $the_course->ID, $args );
			} elseif ( $class_name instanceof LP_Abstract_Course ) {
				$course = $class_name;
			} else {
				$course = new self( $the_course->ID, $args );
			}
			LP_Global::$courses[ $key ] = $course;
		}

		return LP_Global::$courses[ $key ];
	}

	/**
	 * @param  string $course_type
	 *
	 * @return string|false
	 */
	private static function get_class_name_from_course_type( $course_type ) {
		return LP_COURSE_CPT === $course_type ? __CLASS__ : 'LP_Course_' . implode( '_', array_map( 'ucfirst', explode( '-', $course_type ) ) );
	}

	/**
	 * Get the course class name
	 *
	 * @param  WP_Post $the_course
	 * @param  array   $args (default: array())
	 *
	 * @return string
	 */
	private static function get_course_class( $the_course, $args = array() ) {
		$course_id = absint( $the_course->ID );
		$type      = $the_course->post_type;

		$class_name = self::get_class_name_from_course_type( $type );

		// Filter class name so that the class can be overridden if extended.
		return apply_filters( 'learn-press/course/object-class', $class_name, $type, $course_id );
	}

	/**
	 * Get the course object
	 *
	 * @param  mixed $the_course
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	private static function get_course_object( $the_course ) {
		if ( false === $the_course ) {
			$the_course = get_post_type() === LP_COURSE_CPT ? $GLOBALS['post'] : false;
		} elseif ( is_numeric( $the_course ) ) {
			$the_course = get_post( $the_course );
		} elseif ( $the_course instanceof LP_Abstract_Course ) {
			$the_course = get_post( $the_course->get_id() );
		} elseif ( ! ( $the_course instanceof WP_Post ) ) {
			$the_course = false;
		}

		return apply_filters( 'learn-press/course/post-object', $the_course );
	}
}