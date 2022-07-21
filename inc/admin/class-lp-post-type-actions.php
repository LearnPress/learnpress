<?php

/**
 * Class LP_Post_Type_Actions
 *
 * @since 3.1.0
 */
class LP_Post_Type_Actions {

	/**
	 * @var LP_Post_Type_Actions
	 */
	protected static $instance = null;

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * LP_Post_Type_Actions constructor.
	 */
	protected function __construct() {
		//add_action( 'save_post', array( $this, 'save_post' ) );
		//add_action( 'learn-press/added-item-to-section', array( $this, 'added_item_to_section' ), 10, 3 );
		//add_action( 'learn-press/removed-item-from-section', array( $this, 'removed_item_from_section' ), 10, 2 );

		add_filter( 'pre_trash_post', array( $this, 'pre_trash_post' ), 10, 2 );
		add_filter( 'trashed_post', array( $this, 'trashed_post' ), 1000, 1 );

		//add_filter( 'transition_post_status', array( $this, 'transition_post_status' ), 1000, 3 );
	}

	public function __get( $key ) {
		return $this->get( $key );
	}

	public function __set( $key, $value ) {
		$this->data[ $key ] = $value;
	}

	public function get( $key ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : false;
	}

	public function add( $key, $value, $single = true ) {
		if ( empty( $this->data[ $key ] ) ) {
			$this->data[ $key ] = array();
		}

		if ( ! $single || ! is_array( $value ) ) {
			$this->data[ $key ][] = $value;
		} else {
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					$this->add( $key, $v );
				}
			} else {
				$this->data[ $key ][] = $value;
			}

			return;
		}

		if ( is_array( $value ) ) {
			$this->data[ $key ][] = $value;
		} else {
			if ( array_search( $value, $this->data[ $key ] ) === false ) {
				$this->data[ $key ][] = $value;
			}
		}
	}

	public function delete( $key ) {
		if ( array_key_exists( $key, $this->data ) ) {
			unset( $this->data[ $key ] );
		}
	}

	/**
	 * @param mixed   $null
	 * @param WP_Post $post
	 *
	 * @return mixed
	 */
	public function pre_trash_post( $null, $post ) {
		if ( $this->is_course_item( $post ) ) {
			$curd       = new LP_Course_CURD();
			$course_ids = $curd->get_course_by_item( $post->ID );

			if ( $course_ids ) {
				$this->add( 'item-courses-' . $post->ID, $course_ids );
			}
		} elseif ( $this->is_course( $post ) ) {
			$this->add( 'trashed-course', $post->ID );
		}

		return $null;
	}

	public function trashed_post( $post_id ) {
		if ( $this->is_course_item( $post_id ) ) {
			$course_ids = $this->get( 'item-courses-' . $post_id );

			if ( $course_ids ) {
				$curd = new LP_Course_CURD();

				foreach ( $course_ids as $course_id ) {
					$curd->remove_item( $post_id, $course_id );
					//do_action( 'learn-press/removed-course-item', $course_id );
				}

				$this->delete( 'item-courses-' . $post_id );
			}
		} elseif ( $this->is_course( $post_id ) ) {
			do_action( 'learn-press/trashed-course', $post_id );
		} elseif ( get_post_type( $post_id ) == LP_QUESTION_CPT ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'learnpress_quiz_questions', array( 'question_id' => $post_id ), array( '%d' ) );
		}
	}

	/**
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	/*public function transition_post_status( $old, $new, $post ) {
		if ( $this->is_course( $post ) ) {

		} elseif ( $this->is_course_item( $post ) ) {
			$curd       = new LP_Course_CURD();
			$course_ids = $curd->get_course_by_item( $post->ID );

			if ( $course_ids ) {
				foreach ( $course_ids as $course_id ) {
					do_action( 'learn-press/transition-course-item-status', $post->ID, $course_id, $old, $new );
				}
			}
		}
	}*/

	/*public function added_item_to_section( $item, $section_id, $course_id ) {
		do_action( 'learn-press/added-course-item', $item['id'], $course_id );
	}*/

	/*public function removed_item_from_section( $item, $course_id ) {
		do_action( 'learn-press/removed-course-item', $course_id );
	}*/

	/**
	 * @editor tungnx
	 * @modify 4.1.4.1 comment - not use
	 */
	/*public function save_post( $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( LP_COURSE_CPT === $post_type ) {
			do_action( 'learn-press/save-course', $post_id );
		} elseif ( learn_press_is_support_course_item_type( $post_type ) ) {
			$this->add( 'update-post', $post_id );
		}
	}*/

	public function is_course( $post ) {
		if ( is_numeric( $post ) ) {
			$post_id = absint( $post );
		} else {
			$post_id = absint( $post->ID );
		}

		return LP_COURSE_CPT === get_post_type( $post_id );
	}

	public function is_course_item( $post ) {
		if ( is_numeric( $post ) ) {
			$post_id = absint( $post );
		} else {
			$post_id = absint( $post->ID );
		}

		$post_type = get_post_type( $post_id );

		return learn_press_is_support_course_item_type( $post_type );
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

return LP_Post_Type_Actions::instance();
