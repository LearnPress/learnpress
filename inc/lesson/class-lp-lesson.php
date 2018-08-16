<?php
/**
 * Class LP_Lesson.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! function_exists( 'LP_Lesson' ) ) {

	/**
	 * Class LP_Lesson.
	 */
	class LP_Lesson extends LP_Course_Item {

		/**
		 * @var mixed|string|void
		 */
		public $content = '';

		/**
		 *
		 * @var string
		 */
		public $lesson_type = null;

		/**
		 * @var string
		 */
		protected $_item_type = LP_LESSON_CPT;

		/**
		 * @var int
		 */
		protected static $_loaded = 0;

		/**
		 * LP_Lesson constructor.
		 *
		 * @param        $lesson
		 * @param string $args
		 *
		 * @throws Exception
		 */
		public function __construct( $lesson, $args = '' ) {
			parent::__construct( $lesson, $args );
			$this->_curd = new LP_Lesson_CURD();

			if ( $this->get_id() > 0 ) {
				$this->load();
			}

			self::$_loaded ++;
			if ( self::$_loaded == 1 ) {
				add_filter( 'debug_data', array( __CLASS__, 'log' ) );
			}
		}

		/**
		 * Read course data, curriculum: sections, items, etc...
		 *
		 * @since 3.0.0
		 *
		 * @throws Exception
		 */
		public function load() {
			$this->_curd->load( $this );
		}

		/**
		 * Debug log.
		 *
		 * @param $data
		 *
		 * @return array
		 */
		public static function log( $data ) {
			$data[] = __CLASS__ . '( ' . self::$_loaded . ' )';

			return $data;
		}

		/**
		 * @param $tag
		 *
		 * @return mixed
		 * @throws Exception
		 */
		public function is( $tag ) {
			_deprecated_function( __FUNCTION__, '3.0.8' );
			$args = func_get_args();
			unset( $args[0] );
			$method   = 'is_' . preg_replace( '!-!', '_', $tag );
			$callback = array( $this, $method );
			if ( is_callable( $callback ) ) {
				return call_user_func_array( $callback, $args );
			} else {
				throw new Exception( sprintf( __( 'The function %s doesn\'t exist', 'learnpress' ), $tag ) );
			}
		}

		/**
		 * Get LP Lesson.
		 *
		 * @param mixed $the_lesson
		 * @param array $args
		 *
		 * @return LP_Lesson|bool
		 */
		public static function get_lesson( $the_lesson = false, $args = array() ) {

			if ( is_numeric( $the_lesson ) && isset( LP_Global::$lessons[ $the_lesson ] ) ) {
				return LP_Global::$lessons[ $the_lesson ];
			}

			$the_lesson = self::get_lesson_object( $the_lesson );

			if ( ! $the_lesson ) {
				return false;
			}

			if ( isset( LP_Global::$lessons[ $the_lesson->ID ] ) ) {
				return LP_Global::$lessons[ $the_lesson->ID ];
			}

			if ( ! empty( $args['force'] ) ) {
				$force = ! ! $args['force'];
				unset( $args['force'] );
			} else {
				$force = false;
			}

			$key_args = wp_parse_args( $args, array( 'id' => $the_lesson->ID, 'type' => $the_lesson->post_type ) );

			$key = LP_Helper::array_to_md5( $key_args );

			if ( $force ) {
				LP_Global::$lessons[ $key ]            = false;
				LP_Global::$lessons[ $the_lesson->ID ] = false;
			}

			if ( empty( LP_Global::$lessons[ $key ] ) ) {
				$class_name = self::get_lesson_class( $the_lesson, $args );
				if ( is_string( $class_name ) && class_exists( $class_name ) ) {
					$lesson = new $class_name( $the_lesson->ID, $args );
				} elseif ( $class_name instanceof LP_Course_Item ) {
					$lesson = $class_name;
				} else {
					$lesson = new self( $the_lesson->ID, $args );
				}
				LP_Global::$lessons[ $key ]            = $lesson;
				LP_Global::$lessons[ $the_lesson->ID ] = $lesson;
			}

			return LP_Global::$lessons[ $key ];
		}

		/**
		 * Get default meta.
		 *
		 * @since 3.0.0
		 *
		 * @return mixed
		 */
		public static function get_default_meta() {
			$meta = array(
				'duration' => '0 minute',
				'preview'  => 'no'
			);

			return apply_filters( 'learn-press/course/lesson/default-meta', $meta );
		}

		/**
		 * Get duration of lesson
		 *
		 * @return LP_Duration
		 */
		public function get_duration() {
			$duration = parent::get_duration();

			return apply_filters( 'learn-press/lesson-duration', $duration, $this->get_id() );
		}

		/**
		 * @param  string $lesson_type
		 *
		 * @return string|false
		 */
		private static function get_class_name_from_lesson_type( $lesson_type ) {
			return LP_LESSON_CPT === $lesson_type ? __CLASS__ : 'LP_Lesson_' . implode( '_', array_map( 'ucfirst', explode( '-', $lesson_type ) ) );
		}

		/**
		 * Get the lesson class name
		 *
		 * @param  WP_Post $the_lesson
		 * @param  array   $args (default: array())
		 *
		 * @return string
		 */
		private static function get_lesson_class( $the_lesson, $args = array() ) {
			$lesson_id = absint( $the_lesson->ID );
			$type      = $the_lesson->post_type;

			$class_name = self::get_class_name_from_lesson_type( $type );

			// Filter class name so that the class can be overridden if extended.
			return apply_filters( 'learn-press/lesson/object-class', $class_name, $type, $lesson_id );
		}

		/**
		 * Get the lesson object
		 *
		 * @param  mixed $the_lesson
		 *
		 * @uses   WP_Post
		 * @return WP_Post|bool false on failure
		 */
		private static function get_lesson_object( $the_lesson ) {
			if ( false === $the_lesson ) {
				$the_lesson = get_post_type() === LP_LESSON_CPT ? $GLOBALS['post'] : false;
			} elseif ( is_numeric( $the_lesson ) ) {
				$the_lesson = get_post( $the_lesson );
			} elseif ( $the_lesson instanceof LP_Course_Item ) {
				$the_lesson = get_post( $the_lesson->get_id() );
			} elseif ( ! ( $the_lesson instanceof WP_Post ) ) {
				$the_lesson = false;
			}

			return apply_filters( 'learn-press/lesson/post-object', $the_lesson );
		}
	}
}