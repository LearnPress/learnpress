<?php

class LP_Quiz {
	/**
	 * The quiz (post) ID.
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
	 * WP_Course object
	 *
	 * @var null
	 */
	public $course = null;

	/**
	 * Constructor gets the post object and sets the ID for the loaded course.
	 *
	 * @param int|LP_Quiz|object $quiz Quiz ID, post object, or course object
	 */
	public function __construct( $quiz ) {
		if ( is_numeric( $quiz ) ) {
			$this->id   = absint( $quiz );
			$this->post = get_post( $this->id );
		} elseif ( $quiz instanceof LP_Quiz ) {
			$this->id   = absint( $quiz->id );
			$this->post = $quiz->post;
		} elseif ( isset( $quiz->ID ) ) {
			$this->id   = absint( $quiz->ID );
			$this->post = $quiz;
		}
		$this->course = LP_Course::get_course( $this->_lpr_course );
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->{$key} ) ) {
			return $this->{$key};
		}

		if ( strpos( $key, '_lpr_' ) === false ) {
			$key = '_lpr_' . $key;
		}
		$value = get_post_meta( $this->id, $key, true );
		if ( !empty( $value ) ) {
			$this->$key = $value;
		}

		return $value;
	}

	/**
	 * Get the quiz's post data.
	 *
	 * @return object
	 */
	public function get_quiz_data() {
		return $this->post;
	}

	function is_require_enrollment() {
		return $this->course && $this->course->is_require_enrollment();
	}

	function get_course() {
		if ( empty( $this->course ) ) {
			$course_id    = $this->_lpr_course;
			$this->course = LP_Course::get_course( $course_id );
		}
		return $this->course;
	}

	function get_user_id() {
		if ( empty( $this->user_id ) ) {
			$user_id = get_current_user_id();
			if ( !$user_id ) {
				if ( !$this->is_require_enrollment() ) {
					$user_id = $_SESSION['learn_press_temp_user_id'];
				}
			}
			$this->user_id = $user_id;
		}
		return $this->user_id;
	}

	/**
	 * @param bool  $the_quiz
	 * @param array $args
	 *
	 * @return bool
	 */
	public static function get_quiz( $the_quiz = false, $args = array() ) {
		//$the_quiz = self::get_quiz_object( $the_quiz );
		if ( !$the_quiz ) {
			return false;
		}

		return new LP_Quiz( $the_quiz, $args );
	}

	function get_quiz_result( $quiz_id = null ){
		return false;
	}


	/**
	 * Get the quiz object
	 *
	 * @param  mixed $the_quiz
	 *
	 * @uses   WP_Post
	 * @return WP_Post|bool false on failure
	 */
	private static function get_quiz_object( $the_quiz ) {
		if ( false === $the_quiz ) {
			$the_quiz = $GLOBALS['post'];
		} elseif ( is_numeric( $the_quiz ) ) {
			$the_quiz = get_post( $the_quiz );
		} elseif ( $the_quiz instanceof LP_Quiz ) {
			$the_quiz = get_post( $the_quiz->id );
		} elseif ( !( $the_quiz instanceof WP_Post ) ) {
			$the_quiz = false;
		}

		return apply_filters( 'learn_press_quiz_object', $the_quiz );
	}
}