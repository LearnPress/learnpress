<?php

/**
 * Class LP_Abstract_Course
 */
abstract class LP_Abstract_Course{
    /**
     * The course (post) ID.
     *
     * @var int
     */
    public $id = 0;

    /**
     * $post Stores post data
     *
     * @var $post WP_Post
     */
    public $post = null;

    /**
     *
     * @var string
     */
    public $course_type = null;

    /**
     * Constructor gets the post object and sets the ID for the loaded course.
     *
     * @param int|LP_Course|object $course Course ID, post object, or course object
     */
    public function __construct( $course ) {
        if ( is_numeric( $course ) ) {
            $this->id   = absint( $course );
            $this->post = get_post( $this->id );
        } elseif ( $course instanceof LP_Course ) {
            $this->id   = absint( $course->id );
            $this->post = $course->post;
        } elseif ( isset( $course->ID ) ) {
            $this->id   = absint( $course->ID );
            $this->post = $course;
        }
    }

    /**
     * __isset function.
     *
     * @param mixed $key
     * @return bool
     */
    public function __isset( $key ) {
        return metadata_exists( 'post', $this->id, '_' . $key );
    }

    /**
     * __get function.
     *
     * @param string $key
     * @return mixed
     */
    public function __get( $key ) {
        $value = get_post_meta( $this->id, '_lpr_' . $key, true );
        if ( ! empty( $value ) ) {
            $this->$key = $value;
        }

        return $value;
    }

    /**
     * Get the course's post data.
     *
     * @return object
     */
    public function get_course_data() {
        return $this->post;
    }

    /**
     *
     * @return mixed
     */
    public function is_enrollable(){
        $enrollable = true;

        // Products must exist of course
        if ( ! $this->exists() ) {
            $enrollable = false;
            // Check the product is published
        } elseif ( $this->post->post_status !== 'publish' && ! current_user_can( 'edit_post', $this->id ) ) {
            $enrollable = false;
        }

        return apply_filters( 'learn_press_is_enrollable', $enrollable, $this );
    }

    /**
     * Course is exists if the post is not empty
     *
     * @return bool
     */
    public function exists() {
        return empty( $this->post ) ? false : true;
    }

    /**
     * The course is require enrollment or not
     *
     * @return bool
     */
    public function is_require_enrollment() {
        $is_require = $this->course_enrolled_require;
        $is_require = empty( $is_require ) || ( $is_require == 'yes' ) ? true : false;
        return apply_filters( 'learn_press_is_require_enrollment', $is_require, $this );
    }

    /**
     * Get all curriculum of this course
     *
     * @return mixed
     */
    public function get_curriculum(){
        return apply_filters( 'learn_press_course_curriculum', $this->course_lesson_quiz, $this );
    }

    /**
     * Count the total of students has enrolled course
     *
     * @return mixed
     */
    public function count_users_enrolled(){
        $count = 0;
        $users = $this->course_user;
        if( is_array( $users ) ){
            $users = array_unique( $users );
            $count = sizeof( $users );
        }
        return apply_filters( 'learn_press_count_users_enrolled', $count, $this );
    }

    public function is_free(){
        $is_free = ( 'free' == $this->course_payment ) || ( 0 >= $this->get_price() ) ;
        return apply_filters( 'learn_press_is_free_course', $is_free, $this );
    }

    public function get_price(){
        $price = $this->course_price;
        if( ! $price ){
            $price = 0;
        }else{
            $price = floatval( $price );
        }
        return apply_filters( 'learn_press_course_price', $price, $this );
    }
}