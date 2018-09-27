<?php
/**
 * Class LP_Course.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Course' ) ) {

	/**
	 * Class LP_Course
	 */
	class LP_Course extends LP_Abstract_Course {

		/**
		 * LP_Course constructor.
		 *
		 * @param mixed $course
		 */
		public function __construct( $course ) {
			parent::__construct( $course );
		}

		/**
		 * Debug log.
		 *
		 * @param $data
		 *
		 * @return array
		 */
		public static function log( $data ) {
			return $data;
		}

		/**
		 * Set item is viewing in single course.
		 *
		 * @param LP_Course_Item $item
		 *
		 * @return int|LP_Course_Item
		 */
		public function set_viewing_item( $item ) {

			if ( $this->_viewing_item && $this->_viewing_item->get_id() == $item->get_id() ) {
				return 0;
			}
			$user = learn_press_get_current_user();

			$this->_viewing_item = $item;
			$item->set_course( $this );

			$user->maybe_update_item( $item->get_id(), $this->get_id() );

			return $item;
		}

		/**
		 * Get default course meta.
		 *
		 * @since 3.0.0
		 *
		 * @return mixed
		 */
		public static function get_default_meta() {
			$meta = array(
				'duration'                 => '10 weeks',
				'max_students'             => 1000,
				'students'                 => 0,
				'retake_count'             => 0,
				'featured'                 => 'no',
				'block_lesson_content'     => 'no',
				'external_link_buy_course' => '',
				'course_result'            => 'evaluate_lesson',
				'passing_condition'        => 80,
				'price'                    => '',
				'sale_price'               => '',
				'sale_start'               => '',
				'sale_end'                 => '',
				'required_enroll'          => 'yes',
				'course_author'            => learn_press_get_current_user_id()
			);

			return apply_filters( 'learn-press/course/default-meta', $meta );
		}

		/**
		 * Get LP Course.
		 *
		 * @param bool  $the_course
		 * @param array $args
		 *
		 * @return bool|LP_Course
		 */
		public static function get_course( $the_course = false, $args = array() ) {

			if ( is_numeric( $the_course ) && isset( LP_Global::$courses[ $the_course ] ) ) {
				return LP_Global::$courses[ $the_course ];
			}

			LP_Debug::logTime(__FUNCTION__);
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
					$course = new self( $the_course->ID );
				}
				LP_Global::$courses[ $key ] = $course;
			} else {
				$course = LP_Global::$courses[ $key ];
			}

			/**
			 * Force to reload course data into cache if it is not
			 * loaded or has been deleted for some reasons.
			 */
			$course->load();
			LP_Debug::logTime(__FUNCTION__);

			return $course;
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

			if ( $the_course && $the_course->post_type !== LP_COURSE_CPT ) {
				$the_course = false;
			}

			return apply_filters( 'learn-press/course/post-object', $the_course );
		}
	}

}
