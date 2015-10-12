<?php

/**
 * Class LP_Abstract_Course
 */
abstract class LP_Abstract_Course {
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
	 * Course curriculum
	 *
	 * @var array
	 */
	protected $_curriculum = false;

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
		$value = get_post_meta( $this->id, '_lpr_' . $key, true );
		if ( !empty( $value ) ) {
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
	public function is_enrollable() {
		$enrollable = true;

		// Products must exist of course
		if ( !$this->exists() ) {
			$enrollable = false;
			// Check the product is published
		} elseif ( $this->post->post_status !== 'publish' && !current_user_can( 'edit_post', $this->id ) ) {
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

	private function _get_posts_by_id( $ids ){
		global $wpdb;
		settype( $ids, 'array' );
		$posts = $wpdb->get_results("
			SELECT *
			FROM {$wpdb->posts}
			WHERE ID IN(" . join( ',', $ids ) . ")
		");
		return $posts;
	}

	/**
	 * Get all curriculum of this course
	 *
	 * @param bool $force
	 * @return mixed
	 */
	public function get_curriculum( $force = false ) {
		if( ! $this->_curriculum || $force ) {
			global $wpdb;
			$this->_curriculum = array();
			$query             = $wpdb->prepare( "
				SELECT cc.*
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->learnpress_sections} cc ON p.ID = cc.course_id
				WHERE p.ID = %d
				ORDER BY `order` ASC
			", $this->id );
			if ( $rows = $wpdb->get_results( $query ) ) {
				foreach ( $rows as $row ) {
					$section        = $row;
					$query          = $wpdb->prepare( "
					SELECT item_id
					FROM {$wpdb->learnpress_section_items}
					WHERE section_id = %d
					ORDER BY `order` ASC
				", $row->ID );
					$section->items = array();
					if ( $items = $wpdb->get_col( $query ) ) {
						$section->items = $this->_get_posts_by_id( $items );
					}
					$this->_curriculum[] = $section;
				}
			}
		}
		return apply_filters( 'learn_press_course_curriculum', $this->_curriculum, $this );
	}

	/**
	 * Count the total of students has enrolled course
	 *
	 * @return mixed
	 */
	public function count_users_enrolled() {
		$count = 0;
		$users = $this->course_user;
		if ( is_array( $users ) ) {
			$users = array_unique( $users );
			$count = sizeof( $users );
		}
		return apply_filters( 'learn_press_count_users_enrolled', $count, $this );
	}

	/**
	 * Check if a course is FREE or need to pay or enroll
	 *
	 * @return bool
	 */
	public function is_free() {
		$is_free = ( 'free' == $this->course_payment ) || ( 0 >= $this->get_price() );
		return apply_filters( 'learn_press_is_free_course', $is_free, $this );
	}

	/**
	 * Get the price of course
	 *
	 * @return mixed
	 */
	public function get_price() {
		$price = $this->course_price;
		if ( !$price ) {
			$price = 0;
		} else {
			$price = floatval( $price );
		}
		return apply_filters( 'learn_press_course_price', $price, $this );
	}

	/**
	 * Get all quizzes in a course
	 *
	 * @return array
	 */
	function get_quizzes() {
		$quizzes = array();
		return apply_filters( 'learn_press_get_course_quizzes', $quizzes, $this );
	}

	/**
	 * Get all lessons in a course
	 *
	 * @return array
	 */
	function get_lessons() {
		$lessons = array();
		return apply_filters( 'learn_press_get_course_lesson', $lessons, $this );
	}

	/**
	 * Get all items in a course
	 *
	 * @param array $args{
	 *      force       bool 	Force to get curriculum items that does not care for it is already existing in memory or not
	 *		group		bool 	Group the items returned by type of each item
	 * 		field		string  The fields want to get
	 * }
	 *
	 * @return array
	 */
	function get_curriculum_items( $args = array() ) {

		// default value for $args
		$force 	= false;
		$group	= false;
		$field 	= '';

		$args = wp_parse_args(
			$args,
			array(
				'force'		=> false,
				'group'		=> false,
				'field'		=> ''
			)
		);
		$lessons = array();
		$quizzes = array();

		// overwrite default values with params passed
		extract( $args );
		$items = array();
		if( ! $this->_curriculum_items || $force ){
			$curriculum = $this->get_curriculum( $force );
			$index = 0;
			if( ! empty( $field ) ){
				if( is_string( $field ) ){
					$field = explode( '![\s]+!', $field );
				}
				settype( $field, 'array' );
			}else{
				$field = false;
			}
			//if( is_array( $field ) ) $field = array_map( $field, 'strtolower' );
			if( $curriculum ) foreach( $curriculum as $section ){
				if( empty( $section->items ) ) continue;
				foreach( $section->items as $loop_item ){
					if( $field ) {
						$item = null;
						$item_array = (array) $loop_item;
						foreach ( $field as $field_name ) {
							if ( array_key_exists( $field_name, $item_array ) ){
								$item = $item_array[ $field_name ];
								break;
							}
						}
					}else{
						$item = $loop_item;
					}
					if( $loop_item->post_type == LP()->lesson_post_type ){
						$lessons[ $index ] = $item;
					}else{
						$quizzes[ $index ] = $item;
					}
					$index++;
				}
			}
			// group
			if( $group ){
				$items = array(
					'quizzes'	=> $quizzes,
					'lessons'	=> $lessons
				);
			}else{
				// combine lessons and quizzes as it should be in curriculum
				for( $i = 0, $n = sizeof( $quizzes ) + sizeof( $lessons ); $i < $n; $i++ ){
					if( ! empty( $quizzes[ $i ] ) ){
						$items[] = $quizzes[ $i ];
					}elseif( ! empty( $lessons[ $i ] ) ){
						$items[] = $lessons[ $i ];
					}
				}
			}
		}
		return apply_filters( 'learn_press_get_course_items', $items, $this );
	}
}