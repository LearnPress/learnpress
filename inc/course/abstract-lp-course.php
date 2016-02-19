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
				case 'current_item':
					$item_type = !empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
					if( $item_type ) {
						$item_id = ( ( $item_id = learn_press_get_request( "{$item_type}_id" ) ) && $this->has( 'item', $item_id ) ) ? $item_id : null;
						if ( $item_id ) {
							$value = $item_type == 'lesson' ? LP_Lesson::get_lesson( $item_id ) : LP_Quiz::get_quiz( $item_id );
						}
					}

					break;
				case 'current_lesson':
					$lesson_id = ( ( $lesson_id = learn_press_get_request( "lesson_id" ) ) && $this->has( 'item', $lesson_id ) ) ? $lesson_id : null;
					if( $lesson_id ){
						$value = LP_Lesson::get_lesson( $lesson_id );
					}
					break;
				case 'permalink':
					$value = get_the_permalink( $this->id );
					break;

				default: // default is get course meta key
					if( func_num_args() > 1 ) {
						$single = func_get_arg( 1 );
						if ( $single !== false && $single !== true ) {
							$single = true;
						}
					}else{
						$single = true;
					}
					$value = get_post_meta( $this->id, '_lp_' . $key, $single );
			}
			if ( !empty( $value ) ) {
				$this->$key = $value;
			}
		}
		return ! empty( $this->$key ) ? $this->$key : null;
	}

	function get_image( $size = 'course_thumbnail', $attr = array() ){
		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		} else {
			$image = null;
		}

		return $image;
	}

	function get_permalink(){
		return $this->permalink;
	}

	function is_visible(){
		return true;
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
				INNER JOIN {$wpdb->learnpress_sections} cc ON p.ID = cc.section_course_id
				WHERE p.ID = %d
				ORDER BY `section_order` ASC
			", $this->id );
			if ( $rows = $wpdb->get_results( $query ) ) {
				foreach ( $rows as $row ) {
					$section = $row;

					$section->items = array();
					$query          = $wpdb->prepare("
						SELECT si.*, p.*
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->learnpress_section_items} si ON si.item_id = p.ID
						INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
						WHERE s.section_id = %s
						ORDER BY si.item_order ASC
					", $row->section_id );
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
					$output .= sprintf( _nx( 'You and one student enrolled', 'You and <span class="course-students-number">%1$s</span> students enrolled', intval( $count - 1 ), '', 'learn_press' ), $count - 1 );
				endif;
				$output = apply_filters( 'learn_press_students_enrolled_html', $output, $this );
			else:
				$output = sprintf( _nx( 'One student enrolled', '<span class="course-students-number">%1$s</span> students enrolled', $count, '', 'learn_press' ), $count );
				$output = apply_filters( 'learn_press_one_student_enrolled_html', $output, $this );
			endif;
		else:
			$output = apply_filters( 'learn_press_no_student_enrolled_html', __( 'No student enrolled', 'learn_press' ), $this );
		endif;
		return $output;
	}

	function get_instructor(){
		$user_data = get_userdata( $this->post->post_author );
		return apply_filters( 'learn_press_course_instructor', $user_data->display_name, $this->id );
	}

	function get_instructor_html(){
		$instructor = $this->get_instructor();
		$html = sprintf(
			'<a href="%s">%s</a>',
			apply_filters( 'learn_press_instructor_profile_link', '#', null, $this->id ),
			$instructor
		);
		return apply_filters( 'learn_press_course_instructor_html', $html, $this->post->post_author, $this->id );
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
	 * Check if this course is required to enroll
	 *
	 * @param mixed
	 *
	 * @return bool
	 */
	function is_required_enroll(){
		if( func_get_args() ){
			$required = $this->required_enroll == func_get_arg(0);
		}else{
			$required = $this->required_enroll != 'no';
		}
		return apply_filters( 'learn_press_course_required_enroll', $required, $this );
	}

	/**
	 * Get the price of course
	 *
	 * @return mixed
	 */
	public function get_price() {
		$price = $this->price;
		if ( !$price ) {
			$price = 0;
		} else {
			$price = floatval( $price );
		}
		return apply_filters( 'learn_press_course_price', $price, $this );
	}

	/**
	 * Get the price of course with html
	 *
	 * @return mixed
	 */
	public function get_price_html() {

		if( $this->is_free() ){
			$price_html = apply_filters( 'learn_press_course_price_html_free', __( 'Free', 'learn_press' ), $this );
		}else {
			$price = $this->get_price();
			$price = learn_press_format_price( $price, true );
			$price_html = apply_filters( 'learn_press_course_price_html', $price, $this );
		}
		return $price_html;
	}

	/**
	 * Get all quizzes in a course
	 *
	 * @return array
	 */
	function get_quizzes() {
		$items = $this->get_curriculum_items(
			array(
				'force' => false,
				'group' => true,
			)
		);
		$quizzes = !empty( $items['quizzes'] ) ? $items['quizzes'] : false;
		return apply_filters( 'learn_press_course_quizzes', $quizzes, $this );
	}

	/**
	 * Get all lessons in a course
	 *
	 * @return array
	 */
	function get_lessons() {
		$items = $this->get_curriculum_items(
			array(
				'force' => false,
				'group' => true,
			)
		);
		$lessons = !empty( $items['lessons'] ) ? $items['lessons'] : false;
		return apply_filters( 'learn_press_course_lessons', $lessons, $this );
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

		$item_type = !empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
		$viewing = apply_filters( 'learn_press_course_is_viewing', $item_type ? $item_type : 'course' );
		if( $content ){
			return $content == $viewing;
		}
		return $viewing;
	}

	function is_current_item( $item_id ){
		$item_type = !empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
		$view_id = 0;
		if( $item_type ){
			if( !empty( $_REQUEST[ $item_type . '_id'] ) ){
				$view_id = $_REQUEST[ $item_type . '_id'];
			}
		}
		return apply_filters( 'learn_press_is_current_course_item', $view_id == $item_id, $item_id, $view_id, $this->id );
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
	function is_purchasable(){
		// TODO: needs to check more criteria, currently only check if this course is required enrollment
		return $this->required_enroll == 'yes';
	}

	function need_payment(){
		return $this->payment == 'yes';
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
		$prefix = "{$item_id}-";

		if( '' != get_option( 'permalink_structure' ) && get_post_status( $this->id ) != 'draft' ) {
			$permalink .= $prefix . $post_name;
		}else{
			$key = preg_replace( '!lp_!', '', get_post_type( $item_id ) );
			$permalink = add_query_arg( array( $key => $prefix . $post_name ), $permalink );
		}

		return apply_filters( 'learn_press_course_item_link', $permalink, $item_id, $this );
	}

	function get_next_item( $current_item = false, $dir = 'next' ){
		$items = (array)$this->get_curriculum_items( array( 'field' => 'ID') );
		$items_len = sizeof( $items );
		if( $items_len < 2 ) return false;
		$current_item = $current_item ? $current_item : ( $this->current_item ? $this->current_item->id : 0 );
		if( ! $current_item ){
			$current_item = reset( $items );
		}
		if( ( $pos = array_search( $current_item, $items ) ) !== false ){
			if( $dir == 'next' ) {
				if ( $pos == sizeof( $items ) - 1 ) {
					$next_item = false;
				} else {
					$next_item = $items[$pos + 1];
				}
			}else{
				if( $pos == 0 ){
					$next_item = false;
				}else{
					$next_item = $items[ $pos - 1 ];
				}
			}
		}else{
			$next_item = $dir == 'next' ? $items[1] : $items[ $items_len - 1 ];
		}
		//print_r($items);
		//echo $current_item, ",", $next_item;
		return apply_filters( 'learn_press_course_' . $dir . '_item', $next_item, $current_item, $this );
	}

	function get_next_item_html( $current_item = false ){
		if( $next_item = $this->get_next_item( $current_item ) ){
			ob_start();
			learn_press_get_template( 'lesson/next-button.php', array( 'item' => $next_item, 'course' => $this ) );
			return ob_get_clean();
		}
		return false;
	}

	function get_prev_item_html( $current_item = false ){
		if( $next_item = $this->get_next_item( $current_item, 'prev' ) ){
			ob_start();
			learn_press_get_template( 'lesson/prev-button.php', array( 'item' => $next_item, 'course' => $this ) );
			return ob_get_clean();
		}
		return false;
	}

	function evaluate_course_results( $user_id = 0 ){
		if( !$user_id ){
			$user_id = get_current_user_id();
		}
		if( $this->course_result == 'evaluate_lesson' || !$this->final_quiz){
			$results = $this->_evaluate_course_by_lesson( $user_id );
		}else{
			$results = $this->_evaluate_course_by_quiz( $user_id );
		}
		return apply_filters( 'learn_press_evaluation_course_results', $results );
	}

	function _evaluate_course_by_lesson( $user_id ){
		global $wpdb;
		$course_lessons = $this->get_lessons();
		LP_Debug::instance()->add( $course_lessons );
		if( !$course_lessons ){
			return 1;
		}
		$query = $wpdb->prepare("
			SELECT count(user_id)
			FROM {$wpdb->prefix}learnpress_user_lessons ul
			INNER JOIN {$wpdb->posts} l ON l.ID = ul.lesson_id
			WHERE ul.user_id = %d
			AND status = %s
		", $user_id, 'completed' );
		$completed_lessons = $wpdb->get_var( $query );
		return apply_filters( 'learn_press_evaluation_course_lesson', $completed_lessons / sizeof( $course_lessons ), $this->id, $user_id );
	}
	function _evaluate_course_by_quiz( $user_id ){
		global $wpdb;
		$final_quiz = LP_Quiz::get_quiz( $this->final_quiz );
		$user = learn_press_get_user( $user_id );
		$results = $user->get_quiz_results( $this->final_quiz );
		if( !$results ){
			$result = 0;
		}else{
			if( !empty( $results->results['quiz_mark'] ) ) {
				$result = $results->results['mark'] / $results->results['quiz_mark'];
			}else{
				$result = 0;
			}
		}

		return apply_filters( 'learn_press_evaluation_course_quiz', $result, $this->id, $user_id );
	}
}