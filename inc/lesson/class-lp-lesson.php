<?php

/**
 * Class LP_Lesson
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

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
	protected $_item_type = 'lp_lesson';

	protected static $_loaded = 0;

	/**
	 * LP_Lesson constructor.
	 *
	 * @param int $lesson
	 * @param array|string $args
	 */
	public function __construct( $lesson, $args = '' ) {
		parent::__construct( $lesson, $args );
		static $count = 0;
		self::$_loaded ++;
		if ( self::$_loaded == 1 ) {
			add_filter( 'debug_data', array( __CLASS__, 'log' ) );
		}
	}

	public static function log( $data ) {
		$data[] = __CLASS__ . '( ' . self::$_loaded . ' )';

		return $data;
	}

	public function is( $tag ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'is_' . preg_replace( '!-!', '_', $tag );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The function %s doesn\'t exists', 'learnpress' ), $tag ) );
		}
	}

	/**
	 * @param bool $the_lesson
	 * @param array $args
	 *
	 * @return LP_Lesson|bool
	 */
	public static function get_lesson( $the_lesson = false, $args = array() ) {
		$the_lesson = self::get_lesson_object( $the_lesson );
		if ( ! $the_lesson ) {
			return false;
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
			LP_Global::$lessons[ $key ] = false;
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
			LP_Global::$lessons[ $key ] = $lesson;
		}

		return LP_Global::$lessons[ $key ];
	}

	/**
	 * Set default data for lesson.
	 *
	 * @param $id
	 */
	public static function set_default_meta( $id ) {
		$meta = apply_filters( 'learn_press/lesson/default_meta',
			array(
				'_lp_duration' => 10,
				'_lp_preview'  => 'no'
			)
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
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
	 * @param  array $args (default: array())
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
		} elseif ( $the_lesson instanceof LP_Abstract_Course ) {
			$the_lesson = get_post( $the_lesson->get_id() );
		} elseif ( ! ( $the_lesson instanceof WP_Post ) ) {
			$the_lesson = false;
		}

		return apply_filters( 'learn-press/lesson/post-object', $the_lesson );
	}
}