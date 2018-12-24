<?php
/**
 * Class LP_Abstract_Course.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! function_exists( 'LP_Abstract_Course' ) ) {

	/**
	 * Class LP_Abstract_Course.
	 */
	abstract class LP_Abstract_Course extends LP_Abstract_Post_Data {
		/**
		 *
		 * @var string
		 */
		public $course_type = null;

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
		protected $_data = array(
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
			'external_link'        => '',
			'payment'              => ''
		);

		protected $_loaded = false;

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

			}
		}

		public function refresh() {
			$this->_loaded = false;
			if ( $this->get_id() > 0 ) {
				$this->load();
			}
		}

		/**
		 * Read course data.
		 * - Curriculum: sections, items, etc...
		 */
		public function load() {

			if ( $this->_loaded ) {
				return;
			}

			$this->load_data();
			$this->load_curriculum();
			$this->_loaded = true;
		}

		public function load_data() {
			$id          = $this->get_id();
			$post_object = get_post( $id );
			$this->_set_data(
				array(
					'status'               => $post_object->post_status,
					'required_enroll'      => get_post_meta( $id, '_lp_required_enroll', true ),
					'price'                => get_post_meta( $id, '_lp_price', true ),
					'sale_price'           => get_post_meta( $id, '_lp_sale_price', true ),
					'sale_start'           => get_post_meta( $id, '_lp_sale_start', true ),
					'sale_end'             => get_post_meta( $id, '_lp_sale_end', true ),
					'duration'             => get_post_meta( $id, '_lp_duration', true ),
					'max_students'         => get_post_meta( $id, '_lp_max_students', true ),
					'students'             => false,
					'fake_students'        => get_post_meta( $id, '_lp_students', true ),
					'retake_count'         => get_post_meta( $id, '_lp_retake_count', true ),
					'featured'             => get_post_meta( $id, '_lp_featured', true ),
					'block_lesson_content' => get_post_meta( $id, '_lp_block_lesson_content', true ),
					'course_result'        => get_post_meta( $id, '_lp_course_result', true ),
					'passing_condition'    => get_post_meta( $id, '_lp_passing_condition', true ),
					'payment'              => get_post_meta( $id, '_lp_payment', true ),
					'final_quiz'           => get_post_meta( $id, '_lp_final_quiz', true ),
					'external_link'        => get_post_meta( $id, '_lp_external_link_buy_course', true ),
					'external_link_text'   => get_post_meta( $id, '_lp_external_link_text', true ),
				)
			);
		}

		public function load_curriculum() {
			$this->_curd->load( $this );

			if ( $items = LP_Object_Cache::get( $this->get_id(), 'learn-press/course-curriculum' ) ) {
				LP_Helper_CURD::cache_posts( $items );
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
			_deprecated_argument( __CLASS__ . '::' . $key, '3.0.11' );

			return false;
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
			$image = LP_Thumbnail_Helper::instance()->get_course_image( $this->get_id(), $size, $attr );

			return apply_filters( 'learn-press/course/image', $image, $this->get_id(), $size, $attr );
		}

		/**
		 * @return false|string
		 */
		public function get_permalink() {
			return get_the_permalink( $this->get_id() );
		}

		/**
		 * @return bool
		 */
		public function is_visible() {
			return true;
		}

		/**
		 * @deprecated
		 *
		 * @param string $field
		 *
		 * @return bool|int
		 */
		public function get_request_item( $field = 'id' ) {

			_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '3.0.11' );

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
		 * Course is exists if the post is not empty
		 *
		 * @return bool
		 */
		public function exists() {
			return LP_COURSE_CPT === learn_press_get_post_type( $this->get_id() );
		}

		/**
		 * @return bool
		 */
		public function is_publish() {
			return 'publish' === get_post_status( $this->get_id() );
		}

		/**
		 * Check if this course is required enroll or not.
		 *
		 * @param mixed
		 *
		 * @return bool
		 */
		public function is_required_enroll() {
			$return = $this->get_data( 'required_enroll' ) == 'yes';
			// @deprecated
			$return = apply_filters( 'learn_press_course_required_enroll', $return, $this );

			return apply_filters( 'learn-press/course-require-enrollment', $return, $this->get_id() );
		}

		/**
		 * @deprecated
		 *
		 * @return mixed
		 */
		public function is_require_enrollment() {
			return $this->is_required_enroll();
		}

		/**
		 * @deprecated
		 */
		public function get_description() {
			_deprecated_function( __FUNCTION__, '3.0.0', 'LP_Course::get_content' );

			return $this->get_content();
		}

		/**
		 * Get all curriculum of this course.
		 *
		 * @param int  $section_id
		 * @param bool $force
		 *
		 * @return bool|LP_Course_Section[]
		 */
		public function get_curriculum( $section_id = 0, $force = false ) {
			//_deprecated_function( __CLASS__ . '->get_curriculum()', '3.0.12', __CLASS__ . '->get_sections()' );

			return $this->get_sections( 'object', $section_id );
		}

		/**
		 * Return list of item's ids in course's curriculum.
		 *
		 * @since 3.0.0
		 *
		 * @param string|array $type
		 * @param bool         $preview - True for including 'Preview' item
		 *
		 * @return array
		 */
		public function get_items( $type = '', $preview = true ) {

			$this->load();

			// get course items from cache

			if ( ! $type && $preview ) {
				$items = apply_filters( 'learn-press/course-items', LP_Object_Cache::get( $this->get_id(), 'learn-press/course-curriculum' /*'learn-press/course-items'*/ ) );
			} else {

				if ( ! $type ) {
					$type = learn_press_course_get_support_item_types( true );
				} else {
					settype( $type, 'array' );
				}

				$key = $this->get_id() . '-' . md5( serialize( func_get_args() ) );
				if ( false === ( $items = LP_Object_Cache::get( 'course-' . $key, 'learn-press/course-items' ) ) ) {
					
					$items = array();
					$item_types = $this->get_item_types(true);
					foreach ( $type as $t ) {
						if( isset($item_types[$t]) && !empty($item_types[$t]) ) {
							$items = array_merge( $items, $item_types[$t] );
						}
					}

					if ( ! $preview && $preview_items = $this->get_preview_items() ) {
						$items = array_diff( $items, $preview_items );
					}

					LP_Object_Cache::set( 'course-' . $key, $items, 'learn-press/course-items' );// this line not correct when $type != ''

				}
			}

			return $items;
		}

		public function get_item_types( $group = false ) {
			$cache_key = $group ? 'course-item-group-types' : 'course-item-types';

			if ( false === ( $items = LP_Object_Cache::get( 'course-' . $this->get_id(), "learn-press/{$cache_key}" ) ) ) {
				$item_types = array();
				$items      = array();
				$sections   = array();

				if ( $all_items = $this->_curd->read_course_items( $this->get_id() ) ) {
					foreach ( $all_items as $item ) {
						if ( empty( $item_types[ $item->type ] ) ) {
							$item_types[ $item->type ] = array();
						}
						$item_types[ $item->type ][] = $item->id;
						$items[ $item->id ]          = $item->type;

						if ( empty( $sections[ $item->section_id ] ) ) {
							$sections[ $item->section_id ] = array();
						}
						$sections[ $item->section_id ][] = $item->id;
					}
				}

				LP_Object_Cache::set( 'course-' . $this->get_id(), $item_types, 'learn-press/course-item-group-types' );
				LP_Object_Cache::set( 'course-' . $this->get_id(), $items, 'learn-press/course-item-types' );

				foreach ( $sections as $section_id => $section_items ) {
					LP_Object_Cache::set( 'section-' . $section_id, $section_items, 'learn-press/section-items' );
				}

				learn_press_cache_add_post_type( $items );

				$items = $group ? $item_types : $items;
			}

			return apply_filters( "learn-press/{$cache_key}", $items, $this->get_id() );
		}

		/**
		 * Get all items in a course.
		 *
		 * @return array
		 */
		public function get_item_ids() {
			if ( $items = $this->get_item_types() ) {
				$item_ids = array_keys( $items );
			} else {
				$item_ids = array();
			}

			return apply_filters( 'learn-press/course-item-ids', $item_ids, $this->get_id() );
		}

		/**
		 * Set item is viewing in single course.
		 *
		 * @param LP_Course_Item $item
		 *
		 * @return int
		 */
		public function set_viewing_item( $item ) {
			die( __FUNCTION__ );
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
			$sections      = $this->get_sections( 'object' );
			$sections_data = array();
			if ( is_array( $sections ) ) {
				foreach ( $sections as $section ) {
					$sections_data[] = $section->to_array();
				}
			}

			return $sections_data;
		}

		/**
		 * @return int
		 */
		public function get_fake_students() {
			$count = $this->get_data( 'fake_students' );

			return is_numeric( $count ) ? absint( $count ) : 0;
		}

		/**
		 * Count the real users has enrolled
		 *
		 * @return int
		 */
		public function get_users_enrolled() {

			$enrolled = $this->get_data( 'students' );

			if ( false === $enrolled ) {
				$enrolled = $this->count_students();

				$this->_set_data( 'students', $enrolled );
			}

			$enrolled = absint( $enrolled );

			// @deprecated
			$enrolled = apply_filters( 'learn_press_count_users_enrolled', $enrolled, $this );

			return apply_filters( 'learn-press/course/users-enrolled', $enrolled, $this );
		}

		/**
		 * Output html for students enrolled counter
		 *
		 * @return string
		 */
		public function get_students_html() {
			$output = '';
			if ( $count = $this->get_users_enrolled() ):
				$user = learn_press_get_current_user();
				if ( $user->has_enrolled_course( $this->get_id() ) ):
					if ( $count == 1 ):
						$output .= __( 'You enrolled', 'learnpress' );
					else:
						$output .= sprintf(
							_nx(
								'You and one student enrolled',
								'You and <span class="course-students-number">%1$s</span> students enrolled',
								intval( $count - 1 ),
								'students-html',
								'learnpress'
							)
							, $count - 1
						);
					endif;
				else:
					$output = sprintf( _nx( 'One student enrolled', '<span class="course-students-number">%1$s</span> students enrolled', $count, 'students-html', 'learnpress' ), $count );
				endif;
			else:
				$output = __( 'No student enrolled', 'learnpress' );
			endif;

			return apply_filters( 'learn-press/students-enrolled-html', $output, $this->get_id() );
		}

		/**
		 * @param string $field
		 *
		 * @return LP_User|mixed
		 */
		public function get_instructor( $field = '' ) {
			$user = learn_press_get_user( get_post_field( 'post_author', $this->get_id() ) );

			return $field ? $user->get_data( $field ) : $user;
		}

		/**
		 * @return mixed
		 */
		public function get_instructor_name() {
			$instructor = $this->get_instructor();
			$name       = '';

			if ( $instructor ) {
				if ( $instructor->get_data( 'display_name' ) ) {
					$name = $instructor->get_data( 'display_name' );
				} elseif ( $instructor->get_data( 'user_nicename' ) ) {
					$name = $instructor->get_data( 'user_nicename' );
				} elseif ( $instructor->get_data( 'user_login' ) ) {
					$name = $instructor->get_data( 'user_login' );
				}
			}

			return apply_filters( 'learn-press/course/instructor-name', $name, $this->get_id() );
		}

		/**
		 * @return string
		 */
		public function get_instructor_html() {
			$instructor = $this->get_instructor_name();
			$html       = sprintf(
				'<a href="%s">%s</a>',
				learn_press_user_profile_link( get_post_field( 'post_author', $this->get_id() ) ),
				$instructor
			);

			return apply_filters( 'learn_press_course_instructor_html', $html, get_post_field( 'post_author', $this->get_id() ), $this->get_id() );
		}

		/**
		 * @deprecated
		 *
		 * @param null $user_id
		 *
		 * @return bool|mixed
		 */
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
			$price = $this->get_data( 'price' );

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

			if ( $origin_price = $this->get_origin_price() ) {
				$origin_price      = learn_press_format_price( $origin_price, true );
				$origin_price_html = apply_filters( 'learn_press_course_origin_price_html', $origin_price, $this );
			}

			return $origin_price_html;
		}

		/**
		 * Get all items in a course.
		 *
		 * @deprecated
		 *
		 * @param string $type . Type of items, eg: lp_lesson, lp_quiz...
		 *
		 * @return array
		 */
		public function get_curriculum_items( $type = '' ) {
			return $this->get_items( $type );
		}

		/**
		 * @param bool $item_id
		 *
		 * @return bool|mixed
		 */
		public function is_viewing_item( $item_id = false ) {
			if ( false === ( $item = LP_Global::course_item() ) ) {
				return false;
			}

			return apply_filters( 'learn-press/is-viewing-item', false !== $item_id ? $item_id == $item->get_id() : $item->get_id(), $item_id, $this->get_id() );
		}

		/**
		 * @param $item_id
		 *
		 * @return bool|mixed
		 */
		public function is_current_item( $item_id ) {
			return $this->is_viewing_item( $item_id );
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
			_deprecated_function( __FUNCTION__, '3.0.8' );

			$args = func_get_args();
			unset( $args[0] );
			$method   = 'has_' . preg_replace( '!-!', '_', $tag );
			$callback = array( $this, $method );
			if ( is_callable( $callback ) ) {
				return call_user_func_array( $callback, $args );
			} else {
				throw new Exception( sprintf( __( 'The function %s doesn\'t exist', 'learnpress' ), $tag ) );
			}
		}

		/**
		 * @param string
		 *
		 * @return mixed
		 * @throws Exception
		 */
		public function is( $tag ) {
			_deprecated_function( __FUNCTION__, '3.0.8' );
			$args = func_get_args();
			unset( $args[0] );
			$method   = 'is_' . preg_replace( '!-!', '_', $tag );
			$callback = array( $this, $method );
			if ( is_callable( $callback ) ) {
				return call_user_func_array( $callback, $args );
			} else {
				throw new Exception( sprintf( __( 'The function %s doesn\'t exist', 'learnpress' ), $tag ) );
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
			$is_purchasable = $this->exists() && $this->is_required_enroll() && get_post_status( $this->get_id() ) == 'publish';

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
				$in_stock = $max_allowed > $this->count_completed_orders();
			}

			return apply_filters( 'learn-press/is-in-stock', $in_stock, $this->get_id() );
		}

		/**
		 * Get max students can enroll to course.
		 *
		 * @return int
		 *
		 * @since 3.0.0
		 */
		public function get_max_students() {
			return apply_filters( 'learn-press/max-students', absint( $this->get_data( 'max_students' ) ), $this->get_id() );
		}

		/**
		 * Count number of students enrolled course.
		 * Check global settings `enrolled_students_number`
		 * and add the fake value if both are set.
		 *
		 * @return int
		 */
		public function count_students() {

			if ( metadata_exists( 'post', $this->get_id(), 'count_enrolled_users' ) ) {
				$count_in_order = get_post_meta( $this->get_id(), 'count_enrolled_users', true );
			} else {
				$count_in_order = $this->count_in_order( array( 'completed', 'processing' ) );
			}

			$append_students = LP()->settings()->get( 'enrolled_students_number' );

			if ( ( 'yes' == $append_students ) || ! in_array( $append_students, array( 'yes', 'no' ) ) ) {
				$count_in_order += $this->get_fake_students();
			}

			return $count_in_order;
		}

		/**
		 * @param string|array $statuses
		 *
		 * @return mixed
		 */
		public function count_in_order( $statuses = 'completed' ) {
			settype( $statuses, 'array' );
			$count = 0;
			foreach ( $statuses as $status ) {
				if ( $orders = get_post_meta( $this->get_id(), 'order-' . $status, true ) ) {
					$count += sizeof( $orders );
				}
			}

			return $count;
		}

		public function count_completed_orders() {
			LP_Debug::logTime( __FUNCTION__ );

			if ( $orders = $this->get_meta( 'order-completed' ) ) {
				$count = sizeof( $orders );
			} else {
				$count = 0;
			}

			LP_Debug::logTime( __FUNCTION__ );

			return $count;
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

			if ( $items = $this->get_item_ids() ) {
				$found = in_array( $item_id, $items );
			}

			return apply_filters( 'learn-press/course-has-item', $found, $item_id, $this->get_id() );
		}

		/**
		 * Get course's item (lesson/quiz/etc...).
		 *
		 * @param int $item_id
		 *
		 * @return LP_Lesson|LP_Quiz
		 */
		public function get_item( $item_id ) {
			$item = false;

			if ( $this->has_item( $item_id ) ) {
				if ( false === wp_cache_get( $item_id, 'posts' ) ) {
					LP_Helper_CURD::cache_posts( $this->get_item_ids() );
				}
				$item = LP_Course_Item::get_item( $item_id, $this );
			}

			return apply_filters( 'learn-press/course-item', $item, $item_id, $this->get_id() );
		}

		/**
		 * Get course passing condition value.
		 *
		 * @param bool   $format
		 * @param string $context
		 *
		 * @return array|mixed|string
		 */
		public function get_passing_condition( $format = false, $context = '' ) {
			$value = absint( $this->get_data( 'passing_condition' ) );

			if ( $format ) {
				$value = "{$value}%";
			}

			return 'edit' === $context ? $value : apply_filters( 'learn-press/course-passing-condition', $value, $format, $this->get_id() );
		}

		/**
		 * Fetch all links of course's items into cache.
		 * Item Link = Course Permalink + SLUG + Item Slug
		 *
		 * @since 3.0.0
		 */
		public function get_item_links() {

			if ( false === ( $item_links = LP_Object_Cache::get( 'course-' . $this->get_id(), 'learn-press/course-item-links' ) ) ) {
				if ( $items = $this->get_item_ids() ) {

					if ( false === wp_cache_get( $items[0], 'posts' ) ) {
						LP_Helper_CURD::cache_posts( $items );
					}

					$permalink    = trailingslashit( $this->get_permalink() );
					$post_types   = get_post_types( null, 'objects' );
					$has_query    = strpos( $permalink, '?' ) !== false;
					$parts        = explode( '?', $permalink );
					$is_permalink = '' !== get_option( 'permalink_structure' );
					$is_draft     = 'draft' === get_post_status( $this->get_id() );

					$custom_prefixes = array(
						LP_QUIZ_CPT   => LP()->settings->get( 'quiz_slug' ),
						LP_LESSON_CPT => LP()->settings->get( 'lesson_slug' )
					);

					if ( empty( $custom_prefixes[ LP_QUIZ_CPT ] ) ) {
						$custom_prefixes[ LP_QUIZ_CPT ] = $post_types[ LP_QUIZ_CPT ]->rewrite['slug'];
					}

					if ( empty( $custom_prefixes[ LP_LESSON_CPT ] ) ) {
						$custom_prefixes[ LP_LESSON_CPT ] = $post_types[ LP_LESSON_CPT ]->rewrite['slug'];
					}

					$custom_prefixes = array_map( 'sanitize_title_with_dashes', $custom_prefixes );
					foreach ( $custom_prefixes as $type => $custom_prefix ) {
						$custom_prefix            = sanitize_title_with_dashes( $custom_prefix );
						$custom_prefixes[ $type ] = preg_replace( '!^/!', '', trailingslashit( $custom_prefix ) );
					}

					$custom_prefixes = apply_filters( 'learn-press/course/custom-item-prefixes', $custom_prefixes, $this->get_id() );

					$slugs = apply_filters( 'learn-press/course/custom-item-slugs',
						array(
							LP_LESSON_CPT => 'lesson',
							LP_QUIZ_CPT   => 'quiz'
						)
					);

					foreach ( $items as $item_id ) {
						$item_permalink = $permalink;
						$item_type      = learn_press_get_post_type( $item_id );
						if ( ! empty( $slugs[ $item_type ] ) ) {
							$post_name = get_post_field( 'post_name', $item_id );
							$prefix    = $custom_prefixes[ $item_type ];

							if ( $is_permalink && ! $is_draft ) {
								if ( $has_query ) {
									$item_permalink = $parts[0] . $prefix . $post_name . '?' . $parts[1];
								} else {
									$item_permalink .= $prefix . $post_name;
								}

							} else {
								$item_permalink = add_query_arg( array( $slugs[ $item_type ] => $post_name ), $permalink );
							}

							$item_permalink = $has_query ? untrailingslashit( $item_permalink ) : trailingslashit( $item_permalink );
						}
						$item_links[ $item_id ] = $item_permalink;
					}
				}
				LP_Object_Cache::set( 'course-' . $this->get_id(), $item_links, 'learn-press/course-item-links' );
			}

			return $item_links;
		}

		/**
		 * @param int $item_id
		 *
		 * @return string
		 */
		public function get_item_link( $item_id ) {
			$item_link = '';

			if ( false !== ( $item_links = $this->get_item_links() ) ) {
				if ( ! empty( $item_links[ $item_id ] ) ) {
					$item_link = $item_links[ $item_id ];
				}
			}

			return apply_filters( 'learn-press/course/item-link', $item_link, $item_id, $this );
		}

		/**
		 * Get course's item at a position.
		 *
		 * @param int $at
		 *
		 * @return bool|mixed
		 */
		public function get_item_at( $at ) {
			if ( ! $items = $this->get_items() ) {
				return false;
			}

			return ! empty( $items[ $at ] ) ? $items[ $at ] : false;
		}

		/**
		 * Get position of an item in course curriculum.
		 *
		 * @param LP_Course_Item|LP_User_Item|int $item
		 *
		 * @return mixed
		 */
		public function get_item_position( $item ) {
			if ( ! $items = $this->get_items() ) {
				return false;
			}
			$item_id = is_a( $item, 'LP_User_Item' ) || is_a( $item, 'LP_Course_Item' ) ? $item->get_id() : absint( $item );

			return array_search( $item_id, $items );
		}

		/**
		 * @return bool|mixed
		 */
		public function get_current_item() {
			return $this->is_viewing_item();
		}

		/**
		 * Get item standing after the item is viewing.
		 *
		 * @param array $args
		 *
		 * @return int
		 */
		public function get_next_item( $args = null ) {
			$item_nav = $this->get_item_nav();

			return apply_filters( 'learn-press/course/next-item', $item_nav[2], $this->get_id(), $args );
		}

		/**
		 * Get item standing before the item is viewing.
		 *
		 * @param array $args
		 *
		 * @return int
		 */
		public function get_prev_item( $args = null ) {
			$item_nav = $this->get_item_nav();

			return apply_filters( 'learn-press/course/prev-item', $item_nav[0], $this->get_id(), $args );
		}

		/**
		 * Get item standing before and after an item.
		 * If the item is not passed consider it is item viewing.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $current_item
		 * @param bool $viewable - Optional. TRUE will get next item is viewable.
		 *
		 * @return array|bool
		 */
		public function get_item_nav( $current_item = false, $viewable = false ) {
			if ( false === $current_item ) {
				$current_item = $this->get_current_item();
			}

			if ( false === $current_item ) {
				return false;
			}

			$prev_id = $next_id = 0;

			if ( $item_ids = $this->get_item_ids() ) {
				if ( false !== ( $pos = array_search( $current_item, $item_ids ) ) ) {
					$max     = sizeof( $item_ids ) - 1;
					$user    = learn_press_get_current_user();
					$pos_tmp = $pos;

					while ( $pos_tmp < $max ) {
						$pos_tmp ++;

						if ( ! $viewable || $user->can_view_item( $item_ids[ $pos_tmp ], $this->get_id() ) ) {
							$next_id = $item_ids[ $pos_tmp ];

							break;
						}
					}

					$pos_tmp = $pos;

					while ( $pos_tmp > 0 ) {
						$pos_tmp --;

						if ( ! $viewable || $user->can_view_item( $item_ids[ $pos_tmp ], $this->get_id() ) ) {
							$prev_id = $item_ids[ $pos_tmp ];

							break;
						}
					}

				}
			}

			return array( $prev_id, $current_item, $next_id );
		}

		/**
		 * Get link of item is standing after the item is viewing.
		 *
		 * @param array $args
		 *
		 * @return string
		 */
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
			$lessons = $this->get_items( LP_LESSON_CPT );
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

			_deprecated_function( __CLASS__ . '::' . __FUNCTION__ . '()', '3.1.0', 'LP_User::evaluate_course_results()' );

			LP_Debug::logTime( __FUNCTION__ );
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( $user = learn_press_get_user( $user_id ) ) {
				$user_course = $user->get_course_data( $this->get_id() );
			}

			$result = isset( $user_course ) ? $user_course->get_results( 'result' ) : 0;
			LP_Debug::logTime( __FUNCTION__ );

			return $result;
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
			$quizzes        = $this->get_items( LP_QUIZ_CPT );
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
			$quizzes        = $this->get_items( LP_QUIZ_CPT );
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
				$course_lessons = $this->get_items( LP_LESSON_CPT );
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
		 * Get number of lessons user has completed
		 *
		 * @param        $user_id
		 * @param bool   $force
		 * @param string $type
		 *
		 * @return int|bool
		 */
		public function get_completed_items( $user_id = 0, $force = false, $type = '' ) {


			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$user  = learn_press_get_user( $user_id );
			$items = $user ? $user->get_completed_items( $this->get_id() ) : false;

			return apply_filters( 'learn-press/user-completed-items', $items, $user_id, $this->get_id() );
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
		 * Count all items in a course.
		 *
		 * @param string|array $type            - Optional. Filter item by it's post-type, e.g: lp_lesson
		 * @param bool         $include_preview - Optional. False to exclude if item is preview
		 *
		 * @return int
		 */
		public function count_items( $type = '', $include_preview = true ) {

			if ( $type === '' && $include_preview === true ) {
				if ( false === ( $count_items = $this->get_meta( 'count_items' ) ) ) {
					$items       = $this->get_item_ids();
					$count_items = sizeof( $items );
					$this->update_meta( 'count_items', $count_items );
				}
			} else {
				$key = md5( serialize( array(
					'course'  => $this->get_id(),
					'type'    => $type,
					'preview' => $include_preview
				) ) );

				if ( false === ( $count_items = LP_Object_Cache::get( $key, 'learn-press/count-items' ) ) ) {
					$count_items = 0;

					if ( $items = $this->get_items( $type, $include_preview ) ) {
						$count_items = sizeof( $items );
					}

					LP_Object_Cache::set( $key, $count_items, 'learn-press/count-items' );
				}
			}

			return apply_filters( 'learn-press/count-items', $count_items, $type, $include_preview, $this->get_id() );
		}

		/**
		 * Count all items are 'Preview' in a course.
		 *
		 * @return int
		 */
		public function count_preview_items() {


			if ( false === ( $count_preview = $this->get_preview_items() ) ) {

				$count_preview = 0;

				if ( $items = $this->get_items() ) {
					$count_all        = sizeof( $items );
					$count_no_preview = 0;

					if ( $no_preview_items = $this->get_items( '', false ) ) {
						$count_no_preview = sizeof( $no_preview_items );
					}

					$count_preview = $count_all - $count_no_preview;
				}

			} else {
				$count_preview = sizeof( $count_preview );
			}

			return apply_filters( 'learn-press/count-preview-items', $count_preview, $this->get_id() );
		}

		public function get_preview_items() {
			return LP_Object_Cache::get( 'course-' . $this->get_id(), 'learn-press/course-preview-items' );
		}

		/**
		 * Check a quiz is a final quiz in this course
		 *
		 * @param $quiz_id
		 *
		 * @return mixed
		 */
		public function is_final_quiz( $quiz_id ) {
			return apply_filters( 'learn_press_is_final_quiz', $this->get_final_quiz() == $quiz_id, $quiz_id, $this->get_id() );
		}

		public function get_final_quiz() {
			$final_quiz = $this->get_data( 'final_quiz' );

			return apply_filters( 'learn-press/course-final-quiz', $final_quiz, $this->get_id() );
		}

		public function set_final_quiz( $id ) {
			$this->_set_data( 'final_quiz', $id );
		}

		/**
		 * Return TRUE if option to block course's items after course is exceeded turn on.
		 *
		 * @return bool
		 */
		public function is_block_item_content() {
			return $this->get_data( 'block_lesson_content' ) === 'yes';
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
			$user        = learn_press_get_user( $user_id );
			$user_course = $user->get_course_data( $this->get_id() );

			return $user_course ? $user_course->get_results( '' ) : 0;
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
			$quizzes = $this->get_items( LP_QUIZ_CPT );
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
		 * Get course duration in seconds
		 *
		 * @return int
		 */
		public function get_duration() {
			/**
			 * Duration is in string such as 10 week, 4 hour, etc...
			 * So we can use strtotime('+10 week') to convert it to seconds
			 */
			return strtotime( "+" . $this->get_data( 'duration' ), 0 );
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
					$html   = sprintf( __( 'This course will end within next %s', 'learnpress' ), $remain );
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
		 * @return mixed
		 */
		public function get_user_expired_time( $user_id = 0, $args = array() ) {

			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$duration    = $this->get_duration();
			$user        = learn_press_get_user( $user_id );
			$course_info = $user->get_course_info( $this->get_id() );
			$start_time  = array_key_exists( 'start_time', $args ) ? $args['start_time'] : ( is_array( $course_info ) && array_key_exists( 'start', $course_info ) ? intval( strtotime( $course_info['start'] ) ) : 0 );

			if ( $duration == 0 ) {
				$expired = false;
			} else {
				$expired = $start_time + $duration;
			}

			return apply_filters( 'learn_press_user_course_expired_time', $expired, $user_id, $this->get_id() );
		}

		/**
		 * Checks if this course has expired
		 *
		 * @param int $user_id
		 * @param     mixed
		 *
		 * @return mixed
		 */
		public function is_expired( $user_id = 0, $args = array() ) {
			settype( $args, 'array' );

			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}

			$expired = $this->get_user_expired_time( $user_id, $args );

			return apply_filters( 'learn_press_user_course_expired', $expired !== false ? ( $expired - current_time( 'timestamp' ) ) : false );
		}

		/**
		 * Output params for single course page
		 *
		 * @param null $args
		 *
		 * @return mixed
		 */
		public function output_args( $args = null ) {
			return array();
		}

		/**
		 * Get external link of "Buy this course" button
		 *
		 * @return mixed
		 */
		public function get_external_link() {
			return apply_filters( 'learn-press/course-external-link', $this->get_data( 'external_link' ), $this->get_id() );
		}

		public function get_external_link_text() {
			return apply_filters( 'learn-press/course-external-link-text', _x( 'Buy this course', 'Text of Buy this course with external link', 'learnpress' ), $this->get_id() );
		}

		/**
		 * @return bool|string
		 */
		public function get_video_embed() {
			return LP_Thumbnail_Helper::instance()->get_video_embed( $this );
		}

		/**
		 * @return int
		 */
		public function get_retake_count() {
			return absint( $this->get_data( 'retake_count' ) );
		}

		/**
		 * Get main author of course.
		 *
		 * @param string $field
		 *
		 * @return LP_User|int
		 */
		public function get_author( $field = '' ) {
			$author_id = absint( get_post_field( 'post_author', $this->get_id() ) );

			return strtolower( $field ) === 'id' ? $author_id : learn_press_get_user( $author_id );
		}

		/**
		 * Get author's display name
		 *
		 * @since 3.0.9
		 *
		 * @return string
		 */
		public function get_author_display_name() {
			$display_name = '';

			if ( $user = $this->get_author() ) {
				$display_name = $user->get_display_name();
			}

			return $display_name;
		}

		/**
		 * @return mixed
		 */
		public function get_tags() {
			return apply_filters( 'learn-press/course-tags', get_the_term_list( $this->get_id(), 'course_tag', __( 'Tags: ', 'learnpress' ), ', ', '' ) );
		}

		/**
		 * Get sections of course.
		 *
		 * @param string $return     - Optional.
		 * @param int    $section_id - Optional.
		 *
		 * @return array|LP_Course_Section[]|LP_Course_Section
		 */
		public function get_sections( $return = 'object', $section_id = 0 ) {

			if ( false === ( $sections = LP_Object_Cache::get( 'course-' . $this->get_id(), 'learn-press/course-sections' ) ) ) {
				$sections = $this->_curd->read_course_sections( $this->get_id() );
				LP_Object_Cache::set( 'course-' . $this->get_id(), $sections, 'learn-press/course-sections' );
			}

			if ( $return == 'object' && $sections ) {
				if ( empty( $this->sections ) ) {

					$position        = 0;
					$object_sections = array();

					foreach ( $sections as $k => $section ) {
						$sid     = $section->section_id;
						$section = new LP_Course_Section( $section );
						$section->set_position( ++ $position );

						$object_sections[ $sid ] = $section;
					}
					$sections       = $object_sections;
					$this->sections = $sections;
				} else {
					$sections = $this->sections;
				}
			}

			if ( $section_id ) {
				$sections = ! empty( $sections[ $section_id ] ) ? $sections[ $section_id ] : false;
			}

			return apply_filters( 'learn-press/course-sections', $sections, $this->get_id(), $return, $section_id );
		}

		/**
		 * Enable item link in case user can not view content of them
		 *
		 * @since 3.1.0
		 *
		 * @return bool
		 */
		public function is_enable_item_link() {
			return get_post_meta( $this->get_id(), '_lp_submission', true ) === 'yes';
		}

		/**
		 * Prepare course items.
		 *
		 * @since 3.2.0
		 *
		 * @return array
		 */
		public function prepare() {
			global $wpdb;
			$id        = $this->get_id();
			$all_items = LP_Object_Cache::get( $id, 'course-raw-items' );

			$type_items      = LP_Object_Cache::get( 'course-' . $id, 'learn-press/course-item-group-types' );
			$section_items   = LP_Object_Cache::get( $id, 'learn-press/section-items' );
			$course_sections = LP_Object_Cache::get( $id, 'learn-press/course-sections-ids' );

			if ( false === $type_items || false === $section_items || false === $course_sections ) {
				$types           = learn_press_get_course_item_types();
				$type_items      = array_fill_keys( $types, array() );
				$section_items   = array();
				$course_sections = array();

				if ( $all_items === false ) {
					$query = $wpdb->prepare( "
                    SELECT section_items.item_id AS id, course_sections.section_id AS section, item.post_type AS type
                    FROM {$wpdb->posts} course
                    INNER JOIN {$wpdb->learnpress_sections} course_sections ON course.ID  = course_sections.section_course_id
                    INNER JOIN {$wpdb->learnpress_section_items} section_items ON course_sections.section_id = section_items.section_id
                    INNER JOIN {$wpdb->posts} item ON item.ID = section_items.item_id
                    WHERE course.ID = %d
                    ORDER BY course_sections.section_order, course_sections.section_id, section_items.item_order,section_items.section_item_id ASC
                ", $id );

					$all_items = $wpdb->get_results( $query );

					LP_Object_Cache::set( $id, $all_items, 'course-raw-items' );
				}

				if ( $all_items ) {
					foreach ( $all_items as $item ) {
						if ( ! array_key_exists( $item->type, $type_items ) ) {
							continue;
						}
						$type_items[ $item->type ][]                    = $item->id;
						$section_items[ 'section-' . $item->section ][] = $item->id;
						$course_sections[ $item->section ]              = $item->section;
					}
				}

				LP_Object_Cache::set( 'course-' . $id, $type_items, 'learn-press/course-item-group-types' );
				foreach ( $section_items as $k => $v ) {
					LP_Object_Cache::set( $k, $v, 'learn-press/section-items' );
				}
				LP_Object_Cache::set( $id, $course_sections, 'learn-press/course-sections-ids' );

				$all_ids = call_user_func_array( 'array_merge', $type_items );
				LP_Helper_CURD::cache_posts( $all_ids );
			}

			return compact( 'type_items', 'section_items', 'course_sections' );
		}
	}
}