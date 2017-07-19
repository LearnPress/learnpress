<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Course
 *
 * @extend LP_Abstract_Course
 * @since  0.9.15
 */
class LP_Course extends LP_Abstract_Course {

	public function __construct( $course ) {
		parent::__construct( $course );

		add_action( 'wp_head', array( $this, 'frontend_assets' ) );
	}

	public function frontend_assets() {
		if ( learn_press_is_course() ) {
			$translate = $this->_get_localize();
			LP_Assets::add_localize( $translate, false, 'learn-press-single-course' );
		}
	}

	private function _get_localize() {
		return apply_filters(
			'learn_press_single_course_js_params',
			array(
				'confirm_finish_course' => array(
					'message' => sprintf( __( 'Are you sure you want to finish course %s?', 'learnpress' ), get_the_title() ),
					'title'   => __( 'Finish course', 'learnpress' )
				),
				'confirm_retake_course' => array(
					'message' => sprintf( __( 'Are you sure you want to retake course %s', 'learnpress' ), get_the_title() ),
					'title'   => __( 'Retake course', 'learnpress' )
				),
                                'confirm_finish_quiz' => array(
                                    'title'   => __( 'Finish quiz', 'learnpress' ),
                                    'message' => __( 'Are you sure you want to finish this quiz?', 'learnpress' )
                                ),
                                'confirm_retake_quiz' => array(
                                        'title'   => __( 'Retake quiz', 'learnpress' ),
                                        'message' => __( 'Are you sure you want to retake this quiz?', 'learnpress' )
                                ),
                                'quiz_time_is_over'   => array(
                                        'title'   => __( 'Time out!' ),
                                        'message' => __( 'The time is over! Your quiz will automate come to finish', 'learnpress' )
                                ),
                                'finished_quiz'       => __( 'Congrats! You have finished this quiz', 'learnpress' ),
                                'retaken_quiz'        => __( 'Congrats! You have re-taken this quiz. Please wait a moment and the page will reload', 'learnpress' )
			)
		);
	}

	/**
	 * @param bool  $the_course
	 * @param array $args
	 *
	 * @return LP_Course|bool
	 */
	public static function get_course( $the_course = false, $args = array() ) {
		$the_course = self::get_course_object( $the_course );
		if ( !$the_course ) {
			return false;
		}

		$class_name = self::get_course_class( $the_course, $args );
		if ( !class_exists( $class_name ) ) {
			$class_name = 'LP_Course';
		}
		return new $class_name( $the_course, $args );
	}

	public static function get_course_by_item( $item_id ) {
		static $courses = array();
		$course = false;
		if ( empty( $courses[$item_id] ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT lsi.item_id, ls.section_course_id as course_id
					FROM {$wpdb->prefix}learnpress_section_items lsi
					INNER JOIN {$wpdb->prefix}learnpress_sections ls ON ls.section_id = lsi.section_id
					WHERE ls.section_course_id IN (SELECT c.ID
						FROM {$wpdb->prefix}posts c
						INNER JOIN {$wpdb->prefix}learnpress_sections ls ON ls.section_course_id = c.ID
						INNER JOIN {$wpdb->prefix}learnpress_section_items lsi ON lsi.section_id = ls.section_id
						WHERE lsi.item_id = %d
					)
			", $item_id );
			if ( $items = $wpdb->get_results( $query ) ) {
				foreach ( $items as $item ) {
					$courses[$item->item_id] = $item->course_id;
				}
			}
		}
		if ( !empty( $courses[$item_id] ) ) {
			$course = $courses[$item_id];
		}
		return $course;
	}

	/**
	 * @param  string $course_type
	 *
	 * @return string|false
	 */
	private static function get_class_name_from_course_type( $course_type ) {
		return $course_type ? 'LP_Course_' . implode( '_', array_map( 'ucfirst', explode( '-', $course_type ) ) ) : false;
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
		$post_type = $the_course->post_type;

		if ( LP_COURSE_CPT === $post_type ) {
			if ( isset( $args['course_type'] ) ) {
				$course_type = $args['course_type'];
			} else {
				/*$terms          = get_the_terms( $course_id, 'course_type' );
				$course_type    = ! empty( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';
				*/
				$course_type = 'simple';
			}
		} else {
			$course_type = false;
		}

		$class_name = self::get_class_name_from_course_type( $course_type );

		// Filter class name so that the class can be overridden if extended.
		return apply_filters( 'learn_press_course_object_class', $class_name, $course_type, $post_type, $course_id );
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
			$the_course = $GLOBALS['post'];
		} elseif ( is_numeric( $the_course ) ) {
			$the_course = get_post( $the_course );
		} elseif ( $the_course instanceof LP_Course ) {
			$the_course = get_post( $the_course->id );
		} elseif ( !( $the_course instanceof WP_Post ) ) {
			$the_course = false;
		}

		return apply_filters( 'learn_press_course_object', $the_course );
	}

}