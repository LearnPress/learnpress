<?php

/**
 * Class LP_Course
 *
 * @extend LP_Abstract_Course
 * @since 0.9.15
 */
class LP_Course extends LP_Abstract_Course{

    /**
     * @param bool $the_course
     * @param array $args
     * @return bool
     */
    public static function get_course( $the_course = false, $args = array() ) {
        $the_course = self::get_course_object( $the_course );
        if ( ! $the_course ) {
            return false;
        }

        $class_name = self::get_course_class( $the_course, $args );
        if ( ! class_exists( $class_name ) ) {
            $class_name = 'LP_Course';
        }
        return new $class_name( $the_course, $args );
    }

    /**
     * @param  string $course_type
     * @return string|false
     */
    private static function get_class_name_from_course_type( $course_type ) {
        return $course_type ? 'LP_Course_' . implode( '_', array_map( 'ucfirst', explode( '-', $course_type ) ) ) : false;
    }

    /**
     * Get the product class name
     *
     * @param  WP_Post $the_course
     * @param  array $args (default: array())
     * @return string
     */
    private static function get_course_class( $the_course, $args = array() ) {
        $course_id = absint( $the_course->ID );
        $post_type  = $the_course->post_type;

        if ( 'lpr_course' === $post_type ) {
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
        return apply_filters( 'learn_press_course_class', $class_name, $course_type, $post_type, $course_id );
    }

    /**
     * Get the course object
     *
     * @param  mixed $the_course
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
        } elseif ( ! ( $the_course instanceof WP_Post ) ) {
            $the_course = false;
        }

        return apply_filters( 'learn_press_course_object', $the_course );
    }
}