<?php

defined( 'ABSPATH' ) || exit();

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
	protected static $_curriculum = array();

	/**
	 * @var null
	 */
	protected $_count_users = null;

	/**
	 * @var array
	 */
	protected static $_lessons = array();

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
		if ( empty( self::$_lessons[$this->id] ) ) {
			self::$_lessons[$this->id] = array();
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
		if ( strcasecmp( $key, 'ID' ) == 0 ) {
			$key = strtolower( $key );
		}
		if ( empty( $this->{$key} ) ) {
			$value = false;
			switch ( $key ) {
				case 'current_item':
					$item_type = !empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
					if ( $item_type ) {
						$item_id = ( ( $item_id = learn_press_get_request( "{$item_type}_id" ) ) && $this->has( 'item', $item_id ) ) ? $item_id : null;
						if ( $item_id ) {
							$value = $item_type == 'lesson' ? LP_Lesson::get_lesson( $item_id ) : LP_Quiz::get_quiz( $item_id );
						}
					}

					break;
				case 'current_lesson':
					$lesson_id = ( ( $lesson_id = learn_press_get_request( "lesson_id" ) ) && $this->has( 'item', $lesson_id ) ) ? $lesson_id : null;
					if ( $lesson_id ) {
						$value = LP_Lesson::get_lesson( $lesson_id );
					}
					break;
				case 'permalink':
					$value = get_the_permalink( $this->id );
					break;

				default: // default is get course meta key
					if ( func_num_args() > 1 ) {
						$single = func_get_arg( 1 );
						if ( $single !== false && $single !== true ) {
							$single = true;
						}
					} else {
						$single = true;
					}
					$value = get_post_meta( $this->id, '_lp_' . $key, $single );
					if ( ( $key == 'price' || $key == 'total' ) && get_post_meta( $this->id, '_lp_payment', true ) != 'yes' ) {
						$value = 0;
					}
			}
			if ( !empty( $value ) ) {
				$this->$key = $value;
			}
		}
		return !empty( $this->$key ) ? $this->$key : null;
	}

	public function get_image( $size = 'course_thumbnail', $attr = array() ) {
		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		} else {
			$image = null;
		}

		return $image;
	}

	public function get_permalink() {
		return $this->permalink;
	}

	public function is_visible() {
		return true;
	}

	public function get_request_item( $field = 'id' ) {
		$return = false;
		if ( !empty( $_REQUEST['course-item'] ) ) {
			$type = $_REQUEST['course-item'];
			if ( $field == 'type' ) {
				$return = $type;
			} elseif ( $field == 'id' ) {
				$return = !empty( $_REQUEST[$type . '_id'] ) ? $_REQUEST[$type . '_id'] : 0;
			} elseif ( $field == 'name' ) {
				$return = !empty( $_REQUEST[$type] ) ? $_REQUEST[$type] : false;
			}
		}
		return $return;
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
		return $this->is_required_enroll();
	}

	/**
	 * Check if this course is required to enroll
	 *
	 * @param mixed
	 *
	 * @return bool
	 */
	public function is_required_enroll() {
		if ( func_get_args() ) {
			$required = $this->required_enroll == func_get_arg( 0 );
		} else {
			$required = $this->required_enroll !== 'no';
		}
		$required = $required || ( $this->payment == 'yes' );
		//$is_require = empty( $is_require ) || ( $is_require == 'yes' ) ? true : false;
		///return apply_filters( 'learn_press_is_require_enrollment', $is_require, $this );

		return apply_filters( 'learn_press_course_required_enroll', $required, $this );
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

	public function get_title() {
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
		if ( !$this->id ) {
			return false;
		}
		if ( !array_key_exists( $this->id, self::$_curriculum ) ) {
			self::$_curriculum[$this->id] = array();
		}
		if ( !self::$_curriculum[$this->id] || $force ) {
			global $wpdb;
			self::$_curriculum[$this->id] = array();
			$query                        = $wpdb->prepare( "
				SELECT cc.*
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->learnpress_sections} cc ON p.ID = cc.section_course_id
				WHERE p.ID = %d
				ORDER BY `section_order` ASC
			", $this->id );
			if ( $rows = $wpdb->get_results( $query, OBJECT_K ) ) {
				$section_ids  = array_keys( $rows );
				$how_many     = count( $section_ids );
				$placeholders = array_fill( 0, $how_many, '%d' );
				$in           = implode( ', ', $placeholders );

				$query         = $wpdb->prepare( "
						SELECT si.*, p.*
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.item_id = p.ID
						INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
						WHERE s.section_id IN( $in )
						ORDER BY s.section_order, si.item_order ASC
					", $section_ids );
				$section_items = $wpdb->get_results( $query );

				foreach ( $rows as $row ) {
					$section        = $row;
					$section->items = array();
					if ( $section_items ) {
						$count = 0;
						foreach ( $section_items as $item ) {

							if ( $item->section_id == $row->section_id ) {
								$section->items[] = $item;
								$count ++;
							} else {
								if ( $count ) break;
							}
						}
					}
					self::$_curriculum[$this->id][] = $section;
				}
			}
		}

		return apply_filters( 'learn_press_course_curriculum', self::$_curriculum[$this->id], $this );
	}

	/**
	 * Count the total of students has enrolled course
	 *
	 * @param string (null|append|swaps)
	 *
	 * @return mixed
	 */
	public function count_users_enrolled( $count_db = '' ) {

		// Get students enrolled from settings of the course that owns course want to show
		// So, if this value is set that means the result is fake ... :)
		$enrolled = $this->students;


		// But, if it is not set then we count the real value from DB
		if ( $count_db ) {
			$real_enrolled = $this->get_users_enrolled();
			if ( $count_db == 'append' ) {
				$enrolled += $real_enrolled;
			} elseif ( $count_db == 'swaps' && $real_enrolled ) {
				$enrolled = $real_enrolled;
			}
		}
		return apply_filters( 'learn_press_count_users_enrolled', $enrolled, $this );
	}

	/**
	 * Count the real users has enrolled
	 *
	 * @param bool $force
	 *
	 * @return int
	 */
	public function get_users_enrolled( $force = false ) {

		global $wpdb;
		if ( $this->_count_users === null || $force ) {
			$query              = $wpdb->prepare( "
				SELECT count(o.ID)
				FROM {$wpdb->posts} o
				INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_id = o.ID
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id = oi.order_item_id
				AND oim.meta_key = %s AND oim.meta_value = %d
				WHERE o.post_status = %s
			", '_course_id', $this->id, 'lp-completed' );
			$this->_count_users = $wpdb->get_var( $query );
		}
		return $this->_count_users;
	}

	/**
	 * Output html for students enrolled counter
	 *
	 * @param int Optional - user ID
	 *
	 * @return string
	 */
	public function get_students_html( $user_id = null ) {
		$output = '';
		if ( $count = $this->count_users_enrolled( 'append' ) ):
			$course_info = $this->get_course_info( $user_id );
			if ( $course_info['status'] ):
				if ( $count == 1 ):
					$output .= __( 'You enrolled', 'learnpress' );
				else:
					$output .= sprintf( _nx( 'You and one student enrolled', 'You and <span class="course-students-number">%1$s</span> students enrolled', intval( $count - 1 ), '', 'learnpress' ), $count - 1 );
				endif;
				$output = apply_filters( 'learn_press_students_enrolled_html', $output, $this );
			else:
				$output = sprintf( _nx( 'One student enrolled', '<span class="course-students-number">%1$s</span> students enrolled', $count, '', 'learnpress' ), $count );
				$output = apply_filters( 'learn_press_one_student_enrolled_html', $output, $this );
			endif;
		else:
			$output = apply_filters( 'learn_press_no_student_enrolled_html', __( 'No student enrolled', 'learnpress' ), $this );
		endif;
		return $output;
	}

	public function get_instructor() {
		$user_data = get_userdata( $this->post->post_author );
		return apply_filters( 'learn_press_course_instructor', $user_data->display_name, $this->id );
	}

	public function get_instructor_html() {
		$instructor = $this->get_instructor();
		$html       = sprintf(
			'<a href="%s">%s</a>',
			learn_press_user_profile_link( $this->post->post_author ),
			$instructor
		);
		return apply_filters( 'learn_press_course_instructor_html', $html, $this->post->post_author, $this->id );
	}

	public function get_course_info( $user_id = null ) {
		if ( !$user_id ) {
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
		$is_free = ( ( 'no' == $this->payment ) || ( 0 >= $this->get_price() ) );
		return apply_filters( 'learn_press_is_free_course', $is_free, $this );
	}

	/**
	 * Get the price of course
	 *
	 * @return mixed
	 */
	public function get_price() {
		$price = $this->price;
		if ( !$price || 'yes' != $this->payment ) {
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

		if ( $this->is_free() ) {
			$price_html = apply_filters( 'learn_press_course_price_html_free', __( 'Free', 'learnpress' ), $this );
		} else {
			$price      = $this->get_price();
			$price      = learn_press_format_price( $price, true );
			$price_html = apply_filters( 'learn_press_course_price_html', $price, $this );
		}
		return $price_html;
	}

	/**
	 * Get all quizzes in a course
	 *
	 * @return array
	 */
	public function get_quizzes() {
		$items   = $this->get_curriculum_items(
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
	public function get_lessons( $args = null ) {
		$args            = wp_parse_args(
			$args,
			array(
				'field' => ''
			)
		);
		$curriculum_args = array_merge(
			array(
				'force' => false,
				'group' => true
			),
			$args
		);
		$items           = $this->get_curriculum_items( $curriculum_args );
		$lessons         = !empty( $items['lessons'] ) ? $items['lessons'] : false;
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
	public function get_curriculum_items( $args = array() ) {

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

	public function is_viewing( $content = '' ) {

		$item_type = !empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
		$viewing   = apply_filters( 'learn_press_course_is_viewing', $item_type ? $item_type : 'course' );
		if ( $content ) {
			return $content == $viewing;
		}
		return $viewing;
	}

	public function is_viewing_item( $item_id = false ) {
		$view_id = $this->get_request_item();

		if ( $item_id ) {
			return $item_id == $view_id;
		}
		return $view_id;
	}

	public function is_current_item( $item_id ) {
		$item_type = !empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
		$view_id   = 0;
		if ( $item_type ) {
			if ( !empty( $_REQUEST[$item_type . '_id'] ) ) {
				$view_id = $_REQUEST[$item_type . '_id'];
			}
		}
		return apply_filters( 'learn_press_is_current_course_item', $view_id == $item_id, $item_id, $view_id, $this->id );
	}

	/**
	 * Check if the course has 'feature'
	 * This function call to a function with prefix 'has'
	 *
	 * @param string
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function has( $tag ) {
		$args = func_get_args();
		unset( $args[0] );
		$method   = 'has_' . preg_replace( '!-!', '_', $tag );
		$callback = array( $this, $method );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		} else {
			throw new Exception( sprintf( __( 'The function %s doesn\'t exists', 'learnpress' ), $tag ) );
		}
	}

	/**
	 * @param string
	 *
	 * @return mixed
	 * @throws Exception
	 */
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
	 * Return true if this course can be purchaseable
	 *
	 * @return mixed
	 */
	public function is_purchasable() {
		// TODO: needs to check more criteria, currently only check if this course is required enrollment
		$is_purchasable = $this->is_required_enroll() && $this->post->post_status == 'publish';
		if ( $is_purchasable ) {
			$count_in_order = $this->count_in_order( array( 'completed', 'processing' ) );
			$is_purchasable = $is_purchasable && ( $count_in_order < $this->max_students );
		}
		return apply_filters( 'learn_press_item_is_purchasable', $is_purchasable, $this->id );
	}

	public function count_in_order( $statuses = 'completed' ) {
		global $wpdb;
		settype( $statuses, 'array' );
		foreach ( $statuses as $k => $v ) {
			if ( !preg_match( '/^lp-/', $v ) ) {
				$statuses[$k] = 'lp-' . $v;
			}
		}
		$in_clause = join( ',', array_fill( 0, sizeof( $statuses ), '%s' ) );
		$query     = $wpdb->prepare( "
			SELECT count(oim.meta_id)
			FROM {$wpdb->learnpress_order_itemmeta} oim
			INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id
				AND oim.meta_key = %s
				AND oim.meta_value = %d
			INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id
			WHERE o.post_type = %s
			AND o.post_status IN ($in_clause)
		", array_merge( array( '_course_id', $this->id, 'lp_order' ), $statuses ) );
		return $wpdb->get_var( $query );
	}

	public function need_payment() {
		return $this->payment == 'yes';
	}

	public function has_item( $item_id ) {
		static $items = array();
		if ( !$items ) {
			$items = $this->get_curriculum_items( array( 'field' => 'ID', 'force' => true ) );
		}
		return in_array( $item_id, $items );
	}

	public function can_view_item( $item_id ) {
		switch ( get_post_type() ) {
			case LP()->quiz_post_type:
		}
	}

	public function get_item_link( $item_id ) {
		if ( !$this->has( 'item', $item_id ) ) {
			return false;
		}
		$permalink = get_the_permalink( $item_id );
		switch ( get_post_type( $item_id ) ) {
			case 'lp_lesson':
				$permalink = trailingslashit( get_the_permalink( $this->id ) );

				$post_name = get_post_field( 'post_name', $item_id );
				$prefix    = "{$item_id}-";

				if ( '' != get_option( 'permalink_structure' ) && get_post_status( $this->id ) != 'draft' ) {
					$permalink .= $prefix . $post_name;
				} else {
					$key       = preg_replace( '!lp_!', '', get_post_type( $item_id ) );
					$permalink = add_query_arg( array( $key => $prefix . $post_name ), $permalink );
				}
				break;
			case 'lp_quiz':
				$permalink = get_the_permalink( $item_id );
		}
		$permalink = trailingslashit( $permalink );
		return apply_filters( 'learn_press_course_item_link', $permalink, $item_id, $this );
	}

	public function get_next_item( $args = null ) {
		$args        = wp_parse_args( $args, array(
			'current_item' => false,
			'dir'          => 'next',
			'viewable'     => null
		) );
		$items_types = apply_filters( 'learn_press_course_' . $args['dir'] . '_item_types', false, $this->id );
		if ( $items_types === false ) {
			$items = (array) $this->get_curriculum_items( array( 'field' => 'ID' ) );
		} else {
			$items = (array) $this->get_curriculum_items( array( 'field' => 'ID', 'group' => true ) );
			if ( !empty( $items[$items_types] ) ) {
				$items = array_values( $items[$items_types] );
			} else {
				$items = array();
			}
		}
		$items_len = sizeof( $items );
		if ( $items_len < 2 ) return false;
		$current_item = $args['current_item'] ? $args['current_item'] : ( $this->current_item ? $this->current_item->id : 0 );
		if ( !$current_item ) {
			$current_item = reset( $items );
		}
		$next_item = false;
		if ( ( $pos = array_search( $current_item, $items ) ) !== false ) {
			if ( $args['dir'] == 'next' ) {
				if ( $pos == sizeof( $items ) - 1 ) {
					$next_item = false;
				} else {
					if ( $args['viewable'] !== null ) {
						$user = learn_press_get_current_user();
						for ( $i = $pos + 1; $i < $items_len; $i ++ ) {
							if ( !!$user->can( 'view-item', $items[$i] ) === $args['viewable'] ) {
								$next_item = $items[$i];
								break;
							}
						}
					} else {
						$next_item = $items[$pos + 1];
					}
				}
			} else {
				if ( $pos == 0 ) {
					$next_item = false;
				} else {
					if ( $args['viewable'] !== null ) {
						$user = learn_press_get_current_user();
						for ( $i = $pos - 1; $i >= 0; $i -- ) {
							if ( !!$user->can( 'view-item', $items[$i] ) === $args['viewable'] ) {
								$next_item = $items[$i];
								break;
							}
						}
					} else {
						$next_item = $items[$pos - 1];
					}
				}
			}
		} else {
			$next_item = $args['dir'] == 'next' ? $items[1] : $items[$items_len - 1];
		}
		return apply_filters( 'learn_press_course_' . $args['dir'] . '_item', $next_item, $current_item, $this );
	}

	public function get_next_item_html( $args = null ) {
		$args = wp_parse_args(
			$args,
			array(
				'current_item' => false,
				'viewable'     => null,
				'dir'          => 'next'
			)
		);
		if ( $next_item = $this->get_next_item( $args ) ) {
			ob_start();
			learn_press_get_template( 'lesson/next-button.php', array( 'item' => $next_item, 'course' => $this ) );
			return ob_get_clean();
		}
		return false;
	}

	public function get_prev_item_html( $args = null ) {
		$args = wp_parse_args(
			$args,
			array(
				'current_item' => false,
				'viewable'     => null,
				'dir'          => 'prev'
			)
		);
		if ( $next_item = $this->get_next_item( $args ) ) {
			ob_start();
			learn_press_get_template( 'lesson/prev-button.php', array( 'item' => $next_item, 'course' => $this ) );
			return ob_get_clean();
		}
		return false;
	}

	public function evaluate_course_results( $user_id = 0 ) {
		if ( !$user_id ) {
			$user_id = get_current_user_id();
		}
		if ( $this->course_result == 'evaluate_lesson' || !$this->final_quiz ) {
			$results = $this->_evaluate_course_by_lesson( $user_id );
		} else {
			$results = $this->_evaluate_course_by_quiz( $user_id );
		}
		return apply_filters( 'learn_press_evaluation_course_results', $results );
	}

	public function _evaluate_course_by_lesson( $user_id ) {
		static $evaluate_course_by_lesson = array();
		$key = $this->id . '-' . $user_id;
		if ( !array_key_exists( $key, $evaluate_course_by_lesson ) ) {
			global $wpdb;
			$course_lessons = $this->get_lessons( array( 'field' => 'ID' ) );
			if ( !$course_lessons ) {
				return 1;
			}
			$query = $wpdb->prepare( "
					SELECT count(ul.lesson_id)
					FROM {$wpdb->prefix}learnpress_user_lessons ul
					INNER JOIN {$wpdb->posts} l ON l.ID = ul.lesson_id
					WHERE ul.user_id = %d
					AND status = %s
					AND ul.course_id = %d
					AND ul.lesson_id IN(" . join( ",", $course_lessons ) . ")
				", $user_id, 'completed', $this->id );

			$completed_lessons               = $wpdb->get_var( $query );
			$evaluate_course_by_lesson[$key] = min( $completed_lessons / sizeof( $course_lessons ), 1 );
		}
		return apply_filters( 'learn_press_evaluation_course_lesson', $evaluate_course_by_lesson[$key], $this->id, $user_id );
	}

	public function _evaluate_course_by_quiz( $user_id ) {
		global $wpdb;
		$final_quiz = LP_Quiz::get_quiz( $this->final_quiz );
		$user       = learn_press_get_user( $user_id );
		$results    = $user->get_quiz_results( $this->final_quiz );
		if ( !$results ) {
			$result = 0;
		} else {
			if ( !empty( $results->results['quiz_mark'] ) ) {
				$result = $results->results['mark'] / $results->results['quiz_mark'];
			} else {
				$result = 0;
			}
		}

		return apply_filters( 'learn_press_evaluation_course_quiz', $result, $this->id, $user_id );
	}
}