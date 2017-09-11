<?php

defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Course
 */
abstract class LP_Abstract_Course extends LP_Abstract_Post_Data {
	/**
	 * The course (post) ID.
	 *
	 * @var int
	 */
	public $idx = 0;

	/**
	 * $post Stores post data
	 *
	 * @var $post WP_Post
	 */
	public $postx = null;

	/**
	 *
	 * @var string
	 */
	public $course_type = null;


	/**
	 * @var null
	 */
	protected $_count_users = null;

	/**
	 * @var null
	 */
	protected $_students_list = null;

	/**
	 * Course item is viewing in single course.
	 *
	 * @var LP_Course_Item
	 */
	protected $_viewing_item = null;

	/**
	 * @var LP_Course_CURD|null
	 */
	protected $_curd = null;

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $_post_type = LP_COURSE_CPT;

	/**
	 * @var array
	 */
	protected static $_lessons = array();

	/*protected $_data = array(
		'status'               => '',
		'require_enrollment'   => '',
		'price'                => '',
		'sale_price'           => '',
		'sale_start'           => '',
		'sale_end'             => '',
		'duration'             => 0,
		'max_students'         => 0,
		'students'             => 0,
		'retake_count'         => 0,
		'featured'             => '',
		'block_lesson_content' => '',
		'course_result'        => '',
		'passing_conditional'  => '',
		'payment'              => ''
	);*/

	/**
	 * Constructor gets the post object and sets the ID for the loaded course.
	 *
	 * @param mixed $the_course Course ID, post object, or course object
	 * @param mixed $deprecated Deprecated
	 */
	public function __construct( $the_course, $deprecated = '' ) {

		$this->_curd = new LP_Course_CURD();

		if ( is_numeric( $the_course ) && $the_course > 0 ) {
			$this->set_id( $the_course );
		} elseif ( $the_course instanceof self ) {
			$this->set_id( absint( $the_course->get_id() ) );
		} elseif ( ! empty( $the_course->ID ) ) {
			$this->set_id( absint( $the_course->ID ) );
		}

		if ( $this->get_id() > 0 ) {
			$this->load();
		}
	}

	/**
	 * Read course data.
	 * - Curriculum: sections, items, etc...
	 */
	public function load() {
		$this->_curd->load( $this );
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->get_id(), '_' . $key );
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
		if ( $key == 'id' ) {
			_deprecated_argument( __CLASS__ . '::id', '3.x.x' );
		}
		if ( empty( $this->{$key} ) ) {
			$value = false;
			switch ( $key ) {
				case 'current_item':
					if ( ! empty( LP()->global['course-item'] ) ) {
						$value = LP()->global['course-item'];
					}

					break;
				case 'current_lesson':
					$lesson_id = ( ( $lesson_id = learn_press_get_request( "lesson_id" ) ) && $this->has( 'item', $lesson_id ) ) ? $lesson_id : null;
					if ( $lesson_id ) {
						$value = LP_Lesson::get_lesson( $lesson_id );
					}
					break;
				case 'permalink':
					$value = get_the_permalink( $this->get_id() );
					break;
				case 'duration':
					$value         = get_post_meta( $this->get_id(), '_lp_' . $key, true );
					$duration      = learn_press_get_course_duration_support();
					$duration_keys = array_keys( $duration );
					if ( ! preg_match_all( '!([0-9]+)\s(' . join( '|', $duration_keys ) . ')!', $value, $matches ) ) {
						$a1    = absint( $value );
						$a2    = end( $duration_keys );
						$value = $a1 . ' ' . $a2;
						update_post_meta( $this->get_id(), '_lp_' . $key, $value );
					}
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
					$value = get_post_meta( $this->get_id(), '_lp_' . $key, $single );
					if ( ( $key == 'price' || $key == 'total' ) && get_post_meta( $this->get_id(), '_lp_payment', true ) != 'yes' ) {
						$value = 0;
					}

			}
			if ( ! empty( $value ) ) {
				$this->$key = $value;
			}
		}

		return ! empty( $this->$key ) ? $this->$key : null;
	}

	/**
	 * Get course thumbnail, return placeholder if it does not exists
	 *
	 * @param string $size
	 * @param array  $attr
	 *
	 * @return string
	 */
	public function get_image( $size = 'course_thumbnail', $attr = array() ) {
		$attr  = wp_parse_args(
			$attr,
			array(
				'alt' => $this->get_title()
			)
		);
		$image = false;
		if ( has_post_thumbnail( $this->get_id() ) ) {
			$image = get_the_post_thumbnail( $this->get_id(), $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->get_id() ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		}
		if ( ! $image ) {
			if ( 'course_thumbnail' == $size ) {
				$image = LP()->image( 'placeholder-400x250' );
			} else {
				$image = LP()->image( 'placeholder-800x450' );
			}
			$image = sprintf( '<img src="%s" %s />', $image, '' );
		}

		return apply_filters( 'learn_press_course_image', $image, $this->get_id(), $size, $attr );
	}

	public function get_permalink() {
		return $this->permalink;
	}

	public function is_visible() {
		return true;
	}

	public function get_request_item( $field = 'id' ) {
		$return = LP()->global['course-item'];
		if ( ! empty( $_REQUEST['course-item'] ) ) {
			$type = $_REQUEST['course-item'];
			if ( $field == 'type' ) {
				$return = $type;
			} elseif ( $field == 'id' ) {
				$return = ! empty( $_REQUEST[ $type . '_id' ] ) ? $_REQUEST[ $type . '_id' ] : 0;
			} elseif ( $field == 'name' ) {
				$return = ! empty( $_REQUEST[ $type ] ) ? $_REQUEST[ $type ] : false;
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
	 * Course is exists if the post is not empty
	 *
	 * @return bool
	 */
	public function exists() {
		return LP_COURSE_CPT === get_post_type( $this->get_id() );
	}

	public function is_publish() {
		return 'publish' === get_post_status( $this->get_id() );
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
		$return = $this->get_data( 'require_enrollment' ) == 'yes';
		// @deprecated
		$return = apply_filters( 'learn_press_course_required_enroll', $return, $this );

		return apply_filters( 'learn-press/course-require-enrollment', $return, $this->get_id() );
	}

	public function get_title() {
		return get_the_title( $this->get_id() );
	}

	/**
	 * @deprecated
	 */
	public function get_description() {
		_deprecated_function( __FUNCTION__, '3.x.x', 'LP_Course::get_content' );

		return $this->get_content();
	}

	/**
	 * Get the content of the course.
	 *
	 * @return string
	 */
	public function get_content() {
		global $post;

		$post = get_post( $this->get_id() );

		setup_postdata( $post );
		$the_content = get_the_content();
		wp_reset_postdata();

		return $the_content;
	}

	/**
	 * Get all curriculum of this course
	 *
	 * @param int  $section_id
	 * @param bool $force
	 *
	 * @return bool|LP_Course_Section
	 */
	public function get_curriculum( $section_id = 0, $force = false ) {
		if ( ! $this->get_id() ) {
			return false;
		}
		$curriculum = array();
		if ( $sections = wp_cache_get( 'course-' . $this->get_id(), 'lp-course-sections' ) ) {
			foreach ( $sections as $k => $section ) {
				$curriculum[ $section->section_id ] = new LP_Course_Section( $section );
			}
		}
		$return = false;
		if ( $section_id ) {
			if ( ! empty( $curriculum[ $section_id ] ) ) {
				$return = $curriculum[ $section_id ];
			}
		} else {
			$return = $curriculum;
		}

		return apply_filters( 'learn_press_course_curriculum', $return, $this->get_id(), $section_id );
	}

	/**
	 * Return list of item's ids in course's curriculum.
	 *
	 * @return array
	 */
	public function get_items() {
		return apply_filters( 'learn-press/course-items', wp_cache_get( 'course-' . $this->get_id(), 'lp-course-items' ) );
	}

	/**
	 * Set item is viewing in single course.
	 *
	 * @param LP_Course_Item $item
	 *
	 * @return int
	 */
	public function set_viewing_item( $item ) {
		if ( $this->_viewing_item && $this->_viewing_item->get_id() == $item->get_id() ) {
			return 0;
		}
		$user = learn_press_get_current_user();

		$this->_viewing_item = $item;
		$item->set_course( $this );

		return $user->maybe_update_item( $item->get_id(), $this->get_id() );
	}

	/**
	 * Get item is viewing in single course.
	 *
	 * @return LP_Course_Item
	 */
	public function get_viewing_item() {
		return apply_filters( 'learn-press/single-course-viewing-item', $this->_viewing_item, $this->get_id() );
	}

	/**
	 * Get raw data curriculum.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_curriculum_raw() {
		$sections = $this->get_curriculum();

		$sections_data = array();
		foreach ( $sections as $section ) {
			$sections_data[] = $section->to_array();
		}

		return $sections_data;
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
		$this->_count_users = LP_Cache::get_enrolled_courses( $this->get_id() );

		return $this->_count_users;
		/*
		if ( ( $this->_count_users === null && !array_key_exists( $this->get_id(), self::$course_users ) ) || $force ) {
			self::$course_users = _learn_press_count_users_enrolled_courses( array( $this->get_id() ) );
		}
		if ( !array_key_exists( $this->get_id(), self::$course_users ) ) {
			$this->_count_users = 0;
		} else {
			$this->_count_users = absint( self::$course_users[$this->get_id()] );
		}
		return $this->_count_users;*/
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
		$user_data   = get_userdata( get_post_field( 'post_author', $this->get_id() ) );
		$author_name = '';
		if ( $user_data ) {
			$author_name = $user_data->display_name;
		}

		return apply_filters( 'learn_press_course_instructor', $author_name, $this->get_id() );
	}

	public function get_instructor_html() {
		$instructor = $this->get_instructor();
		$html       = sprintf(
			'<a href="%s">%s</a>',
			learn_press_user_profile_link( get_post_field( 'post_author', $this->get_id() ) ),
			$instructor
		);

		return apply_filters( 'learn_press_course_instructor_html', $html, get_post_field( 'post_author', $this->get_id() ), $this->get_id() );
	}

	public function get_course_info( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$user = learn_press_get_user( $user_id );

		return $user ? $user->get_course_info( $this->get_id() ) : false;
	}

	/**
	 * Check if a course is FREE or need to pay or enroll
	 *
	 * @return bool
	 */
	public function is_free() {

		// @deprecated
		$is_free = apply_filters( 'learn_press_is_free_course', $this->get_price() == 0, $this );

		return apply_filters( 'learn-press/course-is-free', $is_free, $this->get_id() );
	}

	/**
	 * Get the origin price of course
	 * @return mixed
	 */
	public function get_origin_price() {
		$price = $this->price;
		if ( ! $price || 'yes' != $this->payment ) {
			$price = 0;
		} else {
			$price = floatval( $price );
		}

		return $price;
	}

	/**
	 * Get the sale price of course. Check if sale price is set
	 * and the dates are valid.
	 *
	 * @return mixed
	 */
	public function get_sale_price() {
		return $this->has_sale_price() ? floatval( $this->get_data( 'sale_price' ) ) : false;
	}

	/**
	 * Check if course has 'sale price'
	 *
	 * @return mixed
	 */
	public function has_sale_price() {
		// Check has post meta
		$has_sale_price = metadata_exists( 'post', $this->get_id(), '_lp_sale_price' );
		$sale_price     = $this->get_data( 'sale_price' );

		// Ensure sale price is a number
		if ( $has_sale_price ) {
			$has_sale_price = is_numeric( $sale_price );
		}

		// Ensure sale price is greater than 0
		if ( $has_sale_price ) {
			$has_sale_price = ( $sale_price = floatval( $sale_price ) ) >= 0;
		}

		// Ensure the dates are valid
		if ( $has_sale_price ) {
			$start_date = $this->get_data( 'sale_start' );
			$end_date   = $this->get_data( 'sale_end' );
			$now        = current_time( 'timestamp' );
			$end        = strtotime( $end_date );
			$start      = strtotime( $start_date );

			$has_sale_price = ( ( $now >= $start || ! $start_date ) && ( $now <= $end || ! $end_date ) );
		}

		// Ensure sale price is less than origin price
		if ( $has_sale_price ) {
			$has_sale_price = is_numeric( $this->get_data( 'price' ) ) && $sale_price < $this->get_data( 'price' );
		}

		return apply_filters( 'learn-press/course-has-sale-price', $has_sale_price, $this->get_id() );
	}

	/**
	 * Get the price of course. If sale price is set and the dates is valid then
	 * return the sale price.
	 *
	 * @return mixed
	 */
	public function get_price() {
		$price = floatval( $this->get_data( 'price' ) );

		// Price is not set
		if ( ! $price /* || 'yes' != $this->get_data('payment') */ ) {
			$price = 0;
		} else {
			if ( false !== ( $sale_price = $this->get_sale_price() ) ) {
				$price = $sale_price;
			}
		}

		// @deprecated
		$price = apply_filters( 'learn_press_course_price', $price, $this );

		return apply_filters( 'learn-press/course-price', $price, $this->get_id() );
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
	 * Get the price of course with html
	 *
	 * @return mixed
	 */
	public function get_origin_price_html() {
		$origin_price_html = '';
		//if ( 'yes' == $this->payment && 0 < $this->price ) {
		if ( $origin_price = $this->get_origin_price() ) {
			$origin_price      = learn_press_format_price( $origin_price, true );
			$origin_price_html = apply_filters( 'learn_press_course_origin_price_html', $origin_price, $this );
		}

		//}
		return $origin_price_html;
	}

	/**
	 * @param $limit int limit of records
	 * @param $force boolean force to query database
	 *
	 * @return array list enrolled students
	 */
	public function get_students_list( $force = false, $limit = - 1 ) {
		if ( ! $this->exists() ) {
			return null;
		}

		global $wpdb;
		if ( $limit < 0 ) {
			$limit = PHP_INT_MAX;
		}
		if ( $this->_students_list === null || $force ) {
			$query                = $wpdb->prepare( "
				SELECT DISTINCT u.*
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->prefix}learnpress_user_items ui ON ui.user_id = u.ID
				WHERE ui.item_id = %d
				AND ui.item_type = %s
				LIMIT %d
			", $this->get_id(), LP_COURSE_CPT, $limit );
			$this->_students_list = $wpdb->get_results( $query );
		}

		return $this->_students_list;
	}


	/**
	 * Get all quizzes in a course
	 *
	 * @param string
	 *
	 * @return array
	 */
	public function get_quizzes( $field = null ) {
		$items   = $this->get_curriculum_items(
			array(
				'force' => false,
				'group' => true,
				'field' => $field
			)
		);
		$quizzes = ! empty( $items['quizzes'] ) ? $items['quizzes'] : false;

		return apply_filters( 'learn_press_course_quizzes', $quizzes, $this );
	}

	/**
	 * Get all lessons in a course
	 *
	 * @param mixed
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
		$lessons         = ! empty( $items['lessons'] ) ? $items['lessons'] : false;

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
		$force        = false;
		$group        = false;
		$field        = '';
		$field_map    = '';
		$field_format = '';

		$args    = wp_parse_args(
			$args,
			array(
				'force'        => false,
				'group'        => false,
				'field'        => '',
				'field_map'    => '',
				'field_format' => ''
			)
		);
		$lessons = array();
		$quizzes = array();

		// overwrite default values with params passed
		extract( $args );
		$items = array();
		if ( ! $this->_curriculum_items || $force ) {
			$curriculum = $this->get_curriculum( null, $force );
			$index      = 0;
			if ( ! empty( $field ) ) {
				if ( is_string( $field ) ) {
					$field = explode( '![\s]+!', $field );
				}
				settype( $field, 'array' );
			} else {
				$field = false;
			}
			if ( ! empty( $field_map ) ) {
				if ( is_string( $field_map ) ) {
					$field_map = explode( '![\s]+!', $field_map );
				}
				settype( $field_map, 'array' );
			} else {
				$field_map = false;
			}
			if ( ! empty( $field_format ) ) {
				if ( is_string( $field_format ) ) {
					$field_format = explode( '![\s]+!', $field_format );
				}
				settype( $field_format, 'array' );
			} else {
				$field_format = false;
			}
			//if( is_array( $field ) ) $field = array_map( $field, 'strtolower' );
			if ( $curriculum ) {
				foreach ( $curriculum as $section ) {
					if ( empty( $section->items ) ) {
						continue;
					}
					foreach ( $section->items as $loop_item ) {
						if ( empty( $section->section_id ) ) {
							continue;
						}
						$loop_item->section_id = $section->section_id;
						if ( $field ) {
							$item       = array();
							$item_array = (array) $loop_item;
							foreach ( $field as $k => $field_name ) {
								if ( array_key_exists( $field_name, $item_array ) ) {
									if ( $field_map && ! empty( $field_map[ $k ] ) ) {
										$map_key = $field_map[ $k ];
									} else {
										$map_key = $field_name;
									}
									if ( $field_format && ! empty( $field_format[ $k ] ) ) {
										if ( $field_format[ $k ] == '%d' ) {
											$item[ $map_key ] = strpos( '.', $item_array[ $field_name ] ) !== false ? floatval( $item_array[ $field_name ] ) : intval( $item_array[ $field_name ] );
										} elseif ( $field_format[ $k ] == '%s' ) {
											$item[ $map_key ] = (string) $item_array[ $field_name ];
										}
									} else {
										$item[ $map_key ] = $item_array[ $field_name ];
									}
									//break;
								}
							}
							if ( sizeof( $item ) == 1 ) {
								$item = reset( $item );
							}
						} else {
							$item = $loop_item;
						}
						if ( $loop_item->post_type == LP_LESSON_CPT ) {
							$lessons[ $index ] = $item;
						} else {
							$quizzes[ $index ] = $item;
						}
						$index ++;
					}
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
					if ( ! empty( $quizzes[ $i ] ) ) {
						$items[] = $quizzes[ $i ];
					} elseif ( ! empty( $lessons[ $i ] ) ) {
						$items[] = $lessons[ $i ];
					}
				}
			}
		}

		return apply_filters( 'learn_press_get_course_items', $items, $this );
	}

	public function is_viewing( $content = '' ) {

		$item_type = ! empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
		$viewing   = apply_filters( 'learn_press_course_is_viewing', $item_type ? $item_type : 'course' );
		if ( $content ) {
			return $content == $viewing;
		}

		return $viewing;
	}

	public function is_viewing_item( $item_id = false ) {
		$item   = LP()->global['course-item'];//$this->get_request_item();
		$return = false;
		if ( $item ) {
			if ( $item_id ) {
				$return = $item_id == $item->ID;
			} else {
				$return = $item->ID;
			}
		}

		return apply_filters( 'learn_press_viewing_course_item', $return, $item_id, $this->get_id() );
	}

	public function is_current_item( $item_id ) {
		/*$item_type = !empty( $_REQUEST['course-item'] ) ? $_REQUEST['course-item'] : '';
		$view_id   = 0;
		if ( $item_type ) {
			if ( !empty( $_REQUEST[$item_type . '_id'] ) ) {
				$view_id = $_REQUEST[$item_type . '_id'];
			}
		}*/
		$current_item = $this->current_item;
		$view_id      = $current_item ? $current_item->ID : 0;

		return apply_filters( 'learn_press_is_current_course_item', $view_id == $item_id, $item_id, $view_id, $this->get_id() );
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
	 * Return true if this course can be purchasable.
	 * - Required enroll
	 * - Status is publish
	 *
	 *
	 * @return mixed
	 */
	public function is_purchasable() {
		$is_purchasable = $this->exists() && $this->is_require_enrollment() && get_post_status( $this->get_id() ) == 'publish';

		// @deprecated
		$is_purchasable = apply_filters( 'learn_press_item_is_purchasable', $is_purchasable, $this->get_id() );

		return apply_filters( 'learn-press/is-purchasable', $is_purchasable, $this->get_id() );
	}

	/**
	 * Check if students have enrolled course is reached.
	 *
	 * @return mixed
	 */
	public function is_in_stock() {
		$in_stock = true;
		if ( $max_allowed = $this->get_max_students() ) {
			$in_stock = $max_allowed > $this->count_students();
		}

		return apply_filters( 'learn-press/is-in-stock', $in_stock, $this->get_id() );
	}

	/**
	 * Get max students can enroll to course.
	 *
	 * @return int
	 *
	 * @since 3.x.x
	 */
	public function get_max_students() {
		return apply_filters( 'learn-press/max-students', absint( $this->get_data( 'max_students' ) ), $this->get_id() );
	}

	public function count_students() {
		$count_in_order = $this->count_in_order( array( 'completed', 'processing' ) );

		return $count_in_order;
	}

	public function count_in_order( $statuses = 'completed' ) {
		global $wpdb;
		static $data = array();
		settype( $statuses, 'array' );
		foreach ( $statuses as $k => $v ) {
			if ( ! preg_match( '/^lp-/', $v ) ) {
				$statuses[ $k ] = 'lp-' . $v;
			}
		}
		sort( $statuses );
		$key = md5( serialize( $statuses ) );
		if ( ! array_key_exists( $key, $data ) ) {
			$in_clause    = join( ',', array_fill( 0, sizeof( $statuses ), '%s' ) );
			$query        = $wpdb->prepare( "
				SELECT count(oim.meta_id)
				FROM {$wpdb->learnpress_order_itemmeta} oim
				INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id
					AND oim.meta_key = %s
					AND oim.meta_value = %d
				INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id
				WHERE o.post_type = %s
				AND o.post_status IN ($in_clause)
			", array_merge( array( '_course_id', $this->get_id(), 'lp_order' ), $statuses ) );
			$data[ $key ] = $wpdb->get_var( $query );
		}

		return $data[ $key ];
	}

	public function need_payment() {
		return $this->payment == 'yes';
	}

	/**
	 * Check if course contain an item in curriculum.
	 * Actually, find the item in each section inside curriculum.
	 *
	 * @param $item_id
	 *
	 * @return bool
	 */
	public function has_item( $item_id ) {
		$found = false;

		if ( $items = $this->get_items() ) {
			$found = in_array( $item_id, $items );
		}

		return apply_filters( 'learn-press/course-has-item', $found, $item_id, $this->get_id() );
	}

	/**
	 * Get course's item (less/quiz/etc...).
	 *
	 * @param int $item_id
	 *
	 * @return LP_Lesson|LP_Quiz
	 */
	public function get_item( $item_id ) {
		$item = false;
		if ( $this->has_item( $item_id ) ) {
			$item = LP_Course_Item::get_item( $item_id );
		}

		return apply_filters( 'learn-press/course-item', $item, $item_id, $this->get_id() );
	}

	public function can_view_item( $item_id ) {
		switch ( get_post_type() ) {
			case LP_QUIZ_CPT:
		}
	}

	public function get_item_link( $item_id ) {

		static $item_links = array();
		$key = $this->get_id() . '-' . $item_id;
		if ( empty( $item_links[ $key ] ) ) {
			if ( ! $this->has_item( $item_id ) ) {
				return false;
			}
			$permalink  = '';//get_the_permalink( $item_id );
			$post_types = get_post_types( null, 'objects' );
			$item_type  = get_post_type( $item_id );
			switch ( $item_type ) {
				case 'lp_lesson':
				case 'lp_quiz':
					$permalink = trailingslashit( get_the_permalink( $this->get_id() ) );
					$post_name = get_post_field( 'post_name', $item_id );
					$slug      = '';
					if ( $item_type == 'lp_quiz' ) {
						if ( $custom_prefix = LP()->settings->get( 'quiz_slug' ) ) {
							$slug = $custom_prefix;
						}
					} elseif ( $item_type == 'lp_lesson' ) {
						if ( $custom_prefix = LP()->settings->get( 'lesson_slug' ) ) {
							$slug = $custom_prefix;
						}
					}
					if ( empty( $slug ) ) {
						$slug = $post_types[ $item_type ]->rewrite['slug'];
					}
					$slug   = sanitize_title_with_dashes( $slug );
					$prefix = preg_replace( '!^/!', '', trailingslashit( $slug ) );

					$has_query = strpos( $permalink, '?' ) !== false;
					if ( '' != get_option( 'permalink_structure' ) && get_post_status( $this->get_id() ) != 'draft' ) {
						if ( $has_query ) {
							$parts     = explode( '?', $permalink );
							$permalink = $parts[0] . $prefix . $post_name . '?' . $parts[1];
						} else {
							$permalink .= $prefix . $post_name;
						}

					} else {
						$key       = preg_replace( '!lp_!', '', get_post_type( $item_id ) );
						$permalink = add_query_arg( array( $key => $post_name ), $permalink );
					}
					$permalink = $has_query ? untrailingslashit( $permalink ) : trailingslashit( $permalink );
					break;
			}
			$item_links[ $key ] = $permalink;
		}

		return apply_filters( 'learn_press_course_item_link', $item_links[ $key ], $item_id, $this );
	}

	public function get_next_item( $args = null ) {

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
			learn_press_get_template( 'content-lesson/next-button.php', array(
				'item'   => $next_item,
				'course' => $this
			) );

			return ob_get_clean();
		}

		return false;
	}

	public function get_prev_item_html( $args = null ) {

	}

	/**
	 * @param int  $user_id
	 * @param bool $force
	 *
	 * @return mixed|null|void
	 */
	public function get_course_result_html( $user_id = 0, $force = false ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$html    = '';
		$quizzes = $this->get_quizzes();
		if ( ( $this->course_result == 'evaluate_lesson' ) || ! $quizzes ) {

			$lessons     = $this->get_lessons();
			$total_items = sizeof( $quizzes ) + sizeof( $lessons );


			$html = sprintf( __( '%d of %d items completed', 'learnpress' ), $this->count_completed_items( $user_id, $force ), $total_items );
		} else {
			if ( $this->course_result == 'evaluate_final_quiz' ) {
				$html = sprintf( __( '%d%% completed', 'learnpress' ), $this->_evaluate_course_by_quiz( $user_id, $force ) * 100 );
			} else {
				$html = sprintf( __( '%d%% completed', 'learnpress' ), $this->_evaluate_course_by_quizzes( $user_id, $force ) * 100 );
			}
		}

		return apply_filters( 'learn_press_course_result_html', $html, $this->get_id(), $user_id );
	}

	protected function _evaluate_course_by_items( $user_id = 0, $force = false, $type = '' ) {
		$items  = $this->get_curriculum_items();
		$result = 0;
		if ( $items ) {
			$completed_items = $this->count_completed_items( $user_id, $force, $type );
			$result          = round( $completed_items / sizeof( $items ) * 100 );
		}

		return apply_filters( 'learn_press_course_results_by_items', $result, $this->get_id(), $user_id );
	}

	protected function _evaluate_course_by_lessons( $user_id = 0, $force = false, $type = '' ) {
		$lessons = $this->get_lessons();
		$result  = 0;
		if ( $lessons ) {
			$completed_items = $this->count_completed_items( $user_id, $force, 'lp_lesson' );
			$result          = round( $completed_items / sizeof( $lessons ) * 100 );
		}

		return apply_filters( 'learn_press_course_results_by_items', $result, $this->get_id(), $user_id );
	}

	/**
	 * Calculate course results for user by course results settings
	 *
	 * @param int     $user_id
	 * @param boolean $force
	 *
	 * @return mixed
	 */
	public function evaluate_course_results( $user_id = 0, $force = false ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$quizzes = $this->get_quizzes();

		if ( ( 'evaluate_lesson' === $this->get_data( 'course_result' ) ) || ! $quizzes ) {
			$results = $this->_evaluate_course_by_lesson( $user_id );
		} elseif ( 'evaluate_final_quiz' === $this->get_data( 'course_result' ) ) {
			$results = $this->_evaluate_course_by_quiz( $user_id );
		} elseif ( 'evaluate_quiz' === $this->get_data( 'course_result' ) ) {
			$results = $this->_evaluate_course_by_quizzes( $user_id );
		} elseif ( 'evaluate_quizzes' === $this->get_data( 'course_result' ) ) {
			$results = $this->_evaluate_course_by_quizzes_results( $user_id );
		} elseif ( 'evaluate_passed_quizzes' === $this->get_data( 'course_result' ) ) {
			$results = $this->_evaluate_course_by_passed_quizzes_results( $user_id );
		}

		return apply_filters( 'learn_press_evaluation_course_results', $results );
	}

	/**
	 * Get achieved point of all quizzes per total points of all quizzes
	 *
	 * @param      $user_id
	 * @param bool $force
	 *
	 * @return mixed|void
	 */
	public function _evaluate_course_by_quizzes_results( $user_id, $force = false ) {
		$quizzes        = $this->get_quizzes();
		$user           = learn_press_get_user( $user_id );
		$results        = array();
		$achieved_point = 0;
		$total_point    = 0;
		$quizzes_ids    = array();
		foreach ( $quizzes as $quiz ) {
			if ( ! $this->enable_evaluate_item( $quiz->ID, $user_id ) ) {
				continue;
			}
			$quizzes_ids[]        = $quiz->ID;
			$results[ $quiz->ID ] = $user->get_quiz_results( $quiz->ID, $this->get_id(), true );
			if ( $quiz = wp_cache_get( $quiz->ID, 'posts' ) ) {
				$total_point += isset( $quiz->mark ) ? absint( $quiz->mark ) : 0;
			}
			$achieved_point += is_object( $results[ $quiz->ID ] ) ? $results[ $quiz->ID ]->mark : 0;
		}
		if ( $total_point > 0 ) {
			$result = ( $achieved_point / $total_point ) * 100;
		} else {
			$result = 0;
		}

		return apply_filters( 'learn_press_evaluate_course_by_quizzes_results', $result, $this->get_id(), $user_id );
	}

	public function enable_evaluate_item( $item_id, $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return apply_filters( 'learn_press_enable_evaluate_course_item', true, $item_id, $user_id, $this->get_id() );
	}

	public function _evaluate_course_by_passed_quizzes_results( $user_id, $force = false ) {
		$quizzes        = $this->get_quizzes();
		$user           = learn_press_get_user( $user_id );
		$results        = array();
		$achieved_point = 0;
		$total_point    = 0;
		foreach ( $quizzes as $_quiz ) {
			if ( ! $this->enable_evaluate_item( $_quiz->ID, $user_id ) ) {
				continue;
			}
			$quiz = LP_Quiz::get_quiz( $_quiz->ID );
			if ( $_quiz = wp_cache_get( $quiz->id, 'posts' ) ) {
				$total_point += isset( $_quiz->mark ) ? absint( $_quiz->mark ) : 0;
			}
			$grade = $user->get_quiz_graduation( $quiz->id, $this->get_id() );
			/*$passing_grade      = get_post_meta( $quiz->ID, '_lp_passing_grade', true );
			$results[$quiz->ID] = $user->get_quiz_results( $quiz->ID, $this->get_id(), true );
			$quiz_passed        = false;
			$passing_grade_type = get_post_meta( $quiz->ID, '_lp_passing_grade_type', true );
			$passing_grade      = get_post_meta( $quiz->ID, '_lp_passing_grade', true );
			if ( $passing_grade_type = 'percentage' ) {
				$quiz_passed = ( $results[$quiz->ID]->correct_percent >= intval( $passing_grade ) );
			} elseif ( $passing_grade_type = 'point' ) {
				$quiz_passed = ( $results[$quiz->ID]->mark >= intval( $passing_grade ) );
			} else {
				$quiz_passed = true;
			}*/
			if ( $grade == 'passed' ) {
				$quiz_results   = $user->get_quiz_results( $quiz->ID, $this->get_id(), true );
				$achieved_point += is_object( $quiz_results ) ? $quiz_results->mark : 0;
			}
		}
		if ( $total_point > 0 ) {
			$result = ( $achieved_point / $total_point ) * 100;
		} else {
			$result = 0;
		}

		return apply_filters( 'learn_press_evaluate_course_by_passed_quizzes_results', $result, $this->get_id(), $user_id );
	}

	public function _get_total_question( $quizzes_ids = array() ) {
		global $wpdb;
		if ( ! empty( $quizzes_ids ) ) {
			$format = array_fill( 0, sizeof( $quizzes_ids ), '%d' );
			$args   = array_merge( $quizzes_ids, array( 'publish', LP_QUESTION_CPT ) );
			echo $sql = $wpdb->prepare( "
				SELECT COUNT(*)
				FROM {$wpdb->prefix}learnpress_quiz_questions lqq
				INNER JOIN {$wpdb->posts} p ON lqq.question_id = p.ID
				WHERE
					quiz_id IN (" . join( ',', $format ) . ")
					AND p.post_status = %s
					AND p.post_type = %s",
				$args
			);

			return $wpdb->get_var( $sql );
		}

		return 0;
	}

	public function is_evaluation( $thing ) {
		return $this->get_data( 'course_result' ) == $thing;
	}

	/**
	 * Get number of lessons user has completed
	 *
	 * @param      $user_id
	 * @param bool $force
	 *
	 * @return int|mixed|null|void
	 */
	public function get_completed_lessons( $user_id, $force = false ) {
		//static $completed_lessons = array();
		$key = $user_id . '-' . $this->get_id();

		$completed_lessons = LP_Cache::get_completed_lessons( false, array() );

		if ( ! array_key_exists( $key, $completed_lessons ) || $force ) {
			global $wpdb;
			$course_lessons = $this->get_lessons( array( 'field' => 'ID' ) );
			if ( ! $course_lessons ) {
				return 0;
			}
			$query = $wpdb->prepare( "
					SELECT count(ul.item_id)
					FROM {$wpdb->prefix}learnpress_user_items ul
					INNER JOIN {$wpdb->posts} l ON l.ID = ul.item_id
					WHERE ul.user_id = %d
					AND status = %s
					AND ul.ref_id = %d
					AND ul.item_id IN(" . join( ",", $course_lessons ) . ")
				", $user_id, 'completed', $this->get_id() );

			$completed_lessons[ $key ] = $wpdb->get_var( $query );

			LP_Cache::set_completed_lessons( $key, $completed_lessons[ $key ] );
		}

		return apply_filters( 'learn_press_user_completed_lessons', $completed_lessons[ $key ], $this->get_id(), $user_id );
	}

	/**
	 * Calculate results of course by lessons user completed.
	 *
	 * @param int     $user_id
	 * @param boolean $force
	 *
	 * @return int|mixed|null|void
	 */
	public function _evaluate_course_by_lesson( $user_id, $force = false ) {
		if ( func_num_args() > 1 ) {
			_deprecated_argument( '$force', '3.x.x' );
		}
		//static $evaluate_course_by_lesson = array();
		$evaluate_course_by_lesson = LP_Cache::get_evaluate_course_by_lesson( false, array() );
		$key                       = $user_id . '-' . $this->get_id();
		if ( ! array_key_exists( $key, $evaluate_course_by_lesson ) || $force ) {
			$course_lessons    = $this->get_lessons( array( 'field' => 'ID' ) );
			$completed_lessons = $this->get_completed_lessons( $user_id );
			if ( $size = sizeof( $course_lessons ) ) {
				$evaluate_course_by_lesson[ $key ] = min( $completed_lessons / sizeof( $course_lessons ), 1 ) * 100;
			} else {
				$evaluate_course_by_lesson[ $key ] = 0;
			}
			LP_Cache::set_evaluate_course_by_lesson( $key, $evaluate_course_by_lesson[ $key ] );
		}

		return apply_filters( 'learn_press_evaluation_course_lesson', $evaluate_course_by_lesson[ $key ], $this->get_id(), $user_id );
	}

	/**
	 * Get number of lessons user has completed
	 *
	 * @param        $user_id
	 * @param bool   $force
	 * @param string $type
	 *
	 * @return int|mixed|null
	 */
	public function get_completed_items( $user_id = 0, $force = false, $type = '' ) {


		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return learn_press_get_user( $user_id )->get_completed_items( $this->get_id() );


		_learn_press_parse_user_item_statuses( $user_id, $this->get_id() );
		$item_statuses   = LP_Cache::get_item_statuses( false, array() );
		$completed_items = array();
		if ( $item_statuses ) {
			if ( $curriculum_items = $this->post->curriculum_items ) {
				$curriculum_items = maybe_unserialize( $curriculum_items );
				foreach ( $curriculum_items as $item_id ) {
					if ( $type && $type !== get_post_type( $item_id ) ) {
						continue;
					}
					$k = sprintf( '%d-%d-%d', $user_id, $this->get_id(), $item_id );
					if ( ! empty( $item_statuses[ $k ] ) && $item_statuses[ $k ] == 'completed' ) {
						if ( $this->enable_evaluate_item( $item_id, $user_id ) ) {
							$completed_items[] = $item_id;
						}
					}
				}
			}
		}

		return apply_filters( 'learn_press_user_completed_items', $completed_items, $this->get_id(), $user_id );
	}

	/**
	 * @param int  $user_id
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public function count_completed_items( $user_id = 0, $force = false, $type = '' ) {
		$items = $this->get_completed_items( $user_id, $force, $type );
		$count = 0;
		if ( $items ) {
			$count = sizeof( $items );
		}

		return apply_filters( 'learn_press_count_user_completed_items', $count, $this->get_id(), $user_id );
	}

	/**
	 * Check a quiz is a final quiz in this course
	 *
	 * @param $quiz_id
	 *
	 * @return mixed
	 */
	public function is_final_quiz( $quiz_id ) {
		return apply_filters( 'learn_press_is_final_quiz', $this->final_quiz == $quiz_id, $quiz_id, $this->get_id() );
	}

	/**
	 * Calculate results of course by final quiz
	 *
	 * @param int     $user_id
	 * @param boolean $force
	 *
	 * @return mixed|null
	 */
	public function _evaluate_course_by_quiz( $user_id, $force = false ) {
		global $wpdb;
		$result = $this->evaluate_quiz( $this->final_quiz, $user_id );

		return apply_filters( 'learn_press_evaluation_course_quiz', $result, $this->get_id(), $user_id );
	}

	public function evaluate_quiz( $quiz_id, $user_id, $force = false ) {
		$user    = learn_press_get_user( $user_id );
		$results = $user->get_quiz_results( $quiz_id, $this->get_id() );
		if ( ! $results ) {
			$result = 0;
		} else {
			if ( $user->get_quiz_graduation( $quiz_id, $this->get_id() ) == 'passed' && ! empty( $results->mark_percent ) ) {
				$result = absint( $results->mark_percent );
			} else {
				$result = 0;
			}
		}

		return $result;
	}


	/**
	 * Calculate results of course by avg of all quizzes
	 *
	 * @param int     $user_id
	 * @param boolean $force
	 *
	 * @return mixed
	 */
	public function _evaluate_course_by_quizzes( $user_id, $force = false ) {
		$quizzes = $this->get_quizzes();
		$result  = 0;
		if ( $quizzes ) {
			$count = 0;
			foreach ( $quizzes as $quiz ) {
				if ( ! $this->enable_evaluate_item( $quiz->ID, $user_id ) ) {
					continue;
				}
				$result += $this->evaluate_quiz( $quiz->ID, $user_id, $force );
				$count ++;
			}
			if ( $count ) {
				$result = round( $result / $count );
			}
		}

		return apply_filters( 'learn_press_evaluation_course_quizzes', $result, $this->get_id(), $user_id );
	}

	/**
	 * Get content of course item
	 *
	 * @param $item_id
	 *
	 * @return string
	 */
	public function get_item_content( $item_id ) {
		global $post;
		$post = get_post( $item_id );

		// setup global post to apply all filters hook to content
		setup_postdata( $post );

		// do shortcode
		$content = do_shortcode( get_the_content() );

		// restore post content
		wp_reset_postdata();

		return $content;
	}

	/**
	 * Get course duration in seconds
	 *
	 * @return int
	 */
	public function get_duration() {
		/**
		 * Duration is in string such as 10 week, 4 hour, etc...
		 * So we can use strtotime('+10 week') to convert it to seconds
		 */
		return strtotime( "+" . $this->duration, 0 );
	}


	/**
	 * Get course remaining time message
	 *
	 * @param $user_id
	 *
	 * @return string
	 */
	public function get_user_duration_html( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$duration    = $this->get_duration();
		$user        = learn_press_get_user( $user_id );
		$course_info = $user->get_course_info( $this->get_id() );
		$html        = '';
		if ( $course_info ) {
			$now        = current_time( 'timestamp' );
			$start_time = intval( strtotime( $course_info['start'] ) );
			if ( $start_time + $duration > $now ) {
				$remain = $start_time + $duration - $now;
				$remain = learn_press_seconds_to_weeks( $remain );
				$html   = sprintf( __( 'This course will end within %s next', 'learnpress' ), $remain );
			}
		}

		return $html;
	}

	/**
	 * Get expired time of this course if user has enrolled
	 *
	 * @param int $user_id
	 * @param     mixed
	 *
	 * @return mixed|null|void
	 */
	public function get_user_expired_time( $user_id = 0, $args = array() ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$duration    = $this->get_duration();
		$user        = learn_press_get_user( $user_id );
		$course_info = $user->get_course_info( $this->get_id() );
		$start_time  = array_key_exists( 'start_time', $args ) ? $args['start_time'] : intval( strtotime( $course_info['start'] ) );
		if ( $duration == 0 ) {
			$duration = DAY_IN_SECONDS * 365 * 100;
		}
		$expired = $start_time + $duration;

		return apply_filters( 'learn_press_user_course_expired_time', $expired, $user_id, $this->get_id() );
	}

	/**
	 * Checks if this course has expired
	 *
	 * @param int $user_id
	 * @param     mixed
	 *
	 * @return mixed|null|void
	 */
	public function is_expired( $user_id = 0, $args = array() ) {
		settype( $args, 'array' );
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return apply_filters( 'learn_press_user_course_expired', $this->get_user_expired_time( $user_id, $args ) - current_time( 'timestamp' ) );
	}

	/**
	 * Output params for single course page
	 *
	 * @param null $args
	 *
	 * @return mixed
	 */
	public function output_args( $args = null ) {
		$args   = wp_parse_args( $args, array( 'echo' => true, 'user_id' => get_current_user_id() ) );
		$output = false;
		if ( $user = learn_press_get_user( $args['user_id'] ) ) {
			$course_info  = $user->get_course_info( $this->get_id() );
			$course_grade = $user->get_course_grade( $this->get_id() );
			if ( array_key_exists( 'items', $course_info ) ) {
				unset( $course_info['items'] );
			}
			$output = array(
				'root_url'     => trailingslashit( get_site_url() ),
				'id'           => $this->get_id(),
				'url'          => $this->get_permalink(),
				'results'      => $this->evaluate_course_results( $user->get_id() ),
				// $this->get_course_info( $args['user_id'] ),
				'grade'        => $course_grade,
				'grade_html'   => learn_press_course_grade_html( $course_grade, false ),
				'current_item' => $this->is_viewing_item(),
				'items'        => $this->get_items_params()
			);

			$output = apply_filters( 'learn_press_single_course_params', $output, $this->get_id() );
			LP_Assets::add_var( 'LP_Course_Params', wp_json_encode( $output ), 'learn-press-single-course' );
		}

		return $output;
	}

	/**
	 * Get items params for single course params
	 */
	public function get_items_params( $user_id = null ) {
		global $wpdb;
		$user     = learn_press_get_current_user( $user_id );
		$items    = $this->get_curriculum_items(
			array(
				'field'        => array( 'item_id', 'item_type', 'post_title', 'section_id' ),
				'field_map'    => array( 'id', 'type', 'title' ),
				'field_format' => array( '%d', '%s', '%s', '%d' )
			)
		);
		$root_url = trailingslashit( get_site_url() );
		if ( $items ) {
			foreach ( $items as $k => $item ) {
				if ( ( $view = $user->can( 'view-item', $item['id'], $this->get_id() ) ) !== false ) {
					$status                = $user->get_item_status( $item['id'], $this->get_id() );
					$items[ $k ]['url']    = str_replace( $root_url, '', $this->get_item_link( $item['id'] ) );
					$items[ $k ]['status'] = ( $status == 'completed' && $item['type'] == LP_QUIZ_CPT ) ? $user->get_quiz_graduation( $item['id'], $this->get_id() ) : $status;
					if ( $view == 'preview' ) {

					}
				} else {
					$items[ $k ]['url']    = '';
					$items[ $k ]['status'] = '';
				}
			}
		}

		return $items;
	}

	/**
	 * Get external link of "Buy this course" button
	 *
	 * @return mixed|void
	 */
	public function get_external_link() {
		return apply_filters( 'learn_press_external_link_buy_course', $this->external_link_buy_course, $this->get_id() );
	}

	public function get_video_embed() {
		$video_id   = $this->video_id;
		$video_type = $this->video_type;

		if ( ! $video_id || ! $video_type ) {
			return false;
		}

		$embed  = '';
		$height = $this->video_embed_height;
		$width  = $this->video_embed_width;

		if ( 'youtube' === $video_type ) {
			$embed = '<iframe width="' . $width . '" height="' . $height . '" '
			         . 'src="https://www.youtube.com/embed/' . $video_id . '" '
			         . 'frameborder="0" allowfullscreen></iframe>';

		} elseif ( 'vimeo' === $video_type ) {
			$embed = '<iframe width="' . $width . '" height="' . $height . '" '
			         . ' src="https://player.vimeo.com/video/' . $video_id . '" '
			         . 'frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		}

		return $embed;
	}

}