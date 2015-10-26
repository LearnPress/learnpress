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
		if( empty( $this->{$key} ) ) {
			$value = false;
			switch ( $key ) {
				case 'current_lesson':
					$lesson_id = !empty( $_REQUEST['lesson'] ) && $this->has( 'item', $_REQUEST['lesson'] ) ? $_REQUEST['lesson'] : null;
					if( $lesson_id ){
						$value = LP_Lesson::get_lesson( $lesson_id );
					}
					break;
				case 'permalink':
					$value = get_the_permalink( $this->id );
					break;
				default: // default is get course meta key
					$value = get_post_meta( $this->id, '_lp_' . $key, true );
			}
			if ( !empty( $value ) ) {
				$this->$key = $value;
			}
		}
		return ! empty( $this->$key ) ? $this->$key : null;
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

	private function _get_posts_by_id( $ids ) {
		global $wpdb;
		settype( $ids, 'array' );
		$posts = $wpdb->get_results( "
			SELECT *
			FROM {$wpdb->posts}
			WHERE ID IN(" . join( ',', $ids ) . ")
		" );
		return $posts;
	}

	function get_title(){
		return apply_filters( 'learn_press_course_title', $this->post ? $this->post->post_title : '', $this );
	}

	/**
	 * Get all curriculum of this course
	 *
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public function get_curriculum( $force = false ) {
		if ( !$this->_curriculum || $force ) {
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
					$section = $row;
					/*$query          = $wpdb->prepare( "
						SELECT item_id
						FROM {$wpdb->learnpress_section_items}
						WHERE section_id = %d
						ORDER BY `order` ASC
					", $row->ID );*/
					$section->items = array();
					//if ( $items = $wpdb->get_col( $query ) ) {
					$query          = "SELECT lp_si.ID as lp_si_ID, p.*
							FROM {$wpdb->posts} p
							INNER JOIN {$wpdb->learnpress_section_items} lp_si ON lp_si.item_id = p.ID
							INNER JOIN {$wpdb->learnpress_sections} lp_s ON lp_s.ID = lp_si.section_id
							WHERE lp_s.ID = " . $row->ID . "
							ORDER BY lp_si.order ASC
							";
					$section->items = $wpdb->get_results( $query );
					//}
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

		// Get students enrolled from settings of the course that owns course want to show
		// So, if this value is set that means the result is fake ... :)
		$enrolled = $this->students;

		// But, if it is not set then we count the real value from DB
		if( empty( $enrolled ) ){
			global $wpdb;
			$query = $wpdb->prepare("
				SELECT count(o.ID)
				FROM wp_posts o
				INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_id = o.ID
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id = oi.order_item_id
				AND oim.meta_key = %s AND oim.meta_value = %d
				WHERE o.post_status = %s
			", '_course_id', $this->id, 'lp-completed');
			$this->students = $wpdb->get_var( $query );
		}
		return apply_filters( 'learn_press_count_users_enrolled', $this->students , $this );
	}

	/**
	 * Output html for students enrolled counter
	 *
	 * @param int Optional - user ID
	 * @return string
	 */
	function get_students_html( $user_id = null ) {
		$output = '';
		if ( $count = $this->count_users_enrolled() ):
			$course_info = $this->get_course_info( $user_id );
			if ( $course_info['status'] ):
				if ( $count == 1 ):
					$output .= __( 'You enrolled', 'learn_press' );
				else:
					$output .= sprintf( _nx( 'You and one student enrolled', 'You and %1$s students enrolled', intval( $count - 1 ), '', 'learn_press' ), $count - 1 );
				endif;
				$output = apply_filters( 'learn_press_students_enrolled_html', $output, $this );
			else:
				$output = sprintf( _nx( 'One student enrolled', '%1$s students enrolled', $count, '', 'learn_press' ), $count );
				$output = apply_filters( 'learn_press_one_student_enrolled_html', $output, $this );
			endif;
		else:
			$output = apply_filters( 'learn_press_no_student_enrolled_html', __( 'No student enrolled', 'learn_press' ), $this );
		endif;
		return $output;
	}

	public function get_course_info( $user_id = null ){
		if( ! $user_id ){
			$user_id = get_current_user_id();
		}
		$user = learn_press_get_user( $user_id );
		return $user->get_course_info( $this->id );
	}

	/**
	 * Check if a course is FREE or need to pay or enroll
	 *
	 * @return bool
	 */
	public function is_free() {
		$is_free = ( 'free' == $this->_payment ) || ( 0 >= $this->get_price() );
		return apply_filters( 'learn_press_is_free_course', $is_free, $this );
	}

	/**
	 * Get the price of course
	 *
	 * @return mixed
	 */
	public function get_price() {
		$price = $this->_price;
		$price = get_post_meta($this->id, '_lp_price', true);
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
	 * @param array $args {
	 *                    force       bool    Force to get curriculum items that does not care for it is already existing in memory or not
	 *                    group        bool    Group the items returned by type of each item
	 *                    field        string  The fields want to get
	 *                    }
	 *
	 * @return array
	 */
	function get_curriculum_items( $args = array() ) {

		// default value for $args
		$force = false;
		$group = false;
		$field = '';

		$args    = wp_parse_args(
			$args,
			array(
				'force' => false,
				'group' => false,
				'field' => ''
			)
		);
		$lessons = array();
		$quizzes = array();

		// overwrite default values with params passed
		extract( $args );
		$items = array();
		if ( !$this->_curriculum_items || $force ) {
			$curriculum = $this->get_curriculum( $force );
			$index      = 0;
			if ( !empty( $field ) ) {
				if ( is_string( $field ) ) {
					$field = explode( '![\s]+!', $field );
				}
				settype( $field, 'array' );
			} else {
				$field = false;
			}
			//if( is_array( $field ) ) $field = array_map( $field, 'strtolower' );
			if ( $curriculum ) foreach ( $curriculum as $section ) {
				if ( empty( $section->items ) ) continue;
				foreach ( $section->items as $loop_item ) {
					if ( $field ) {
						$item       = null;
						$item_array = (array) $loop_item;
						foreach ( $field as $field_name ) {
							if ( array_key_exists( $field_name, $item_array ) ) {
								$item = $item_array[$field_name];
								break;
							}
						}
					} else {
						$item = $loop_item;
					}
					if ( $loop_item->post_type == LP()->lesson_post_type ) {
						$lessons[$index] = $item;
					} else {
						$quizzes[$index] = $item;
					}
					$index ++;
				}
			}
			// group
			if ( $group ) {
				$items = array(
					'quizzes' => $quizzes,
					'lessons' => $lessons
				);
			} else {
				// combine lessons and quizzes as it should be in curriculum
				for ( $i = 0, $n = sizeof( $quizzes ) + sizeof( $lessons ); $i < $n; $i ++ ) {
					if ( !empty( $quizzes[$i] ) ) {
						$items[] = $quizzes[$i];
					} elseif ( !empty( $lessons[$i] ) ) {
						$items[] = $lessons[$i];
					}
				}
			}
		}
		return apply_filters( 'learn_press_get_course_items', $items, $this );
	}

	function is_viewing( $content = '' ){
		$viewing = apply_filters( 'learn_press_course_is_viewing', !empty( $_REQUEST['lesson'] ) && $this->has( 'item', $_REQUEST['lesson'] ) ? 'lesson' : 'course' );
		if( $content ){
			return $content == $viewing;
		}
		return $viewing;
	}

	/**
	 * Check if the course has 'feature'
	 * This function call to a function with prefix 'has'
	 *
	 * @param string
	 * @return mixed
	 * @throws Exception
	 */
	function has( $tag ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'has_' . preg_replace( '!-!', '_', $tag );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The function %s doesn\'t exists', 'learn_press' ), $tag ) );
		}
	}

	/**
	 * @param string
	 * @return mixed
	 * @throws Exception
	 */
	function is( $tag ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'is_' . preg_replace( '!-!', '_', $tag );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The function %s doesn\'t exists', 'learn_press' ), $tag ) );
		}
	}

	/**
	 * Return true if this course can be purchaseable
	 *
	 * @return mixed
	 */
	function is_purchaseable(){
		// TODO: needs to check more criteria, currently only check if this course is required enrollment
		return $this->enroll_requirement;
	}

	function has_item( $item_id ){
		static $items = array();
		if( ! $items ){
			$items = $this->get_curriculum_items( array( 'field' => 'ID', 'force' => true ) );
		}
		return in_array( $item_id, $items );
	}

	function get_item_link( $item_id ){
		if( ! $this->has( 'item', $item_id ) ){
			return false;
		}
		$permalink = get_the_permalink( $this->ID );

		$post_name = get_post_field( 'post_name', $item_id );
		if( '' != get_option( 'permalink_structure' ) ) {
			$permalink .= $post_name;
		}else{
			$key = preg_replace( '!.*_!', '', get_post_type( $item_id ) );
			$permalink = add_query_arg( array( $key => $post_name ), $permalink );
		}

		return apply_filters( 'learn_press_course_item_link', $permalink, $item_id, $this );
	}
}