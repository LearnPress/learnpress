<?php
/**
 * Class LP_Abstract_Course.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.0
 */

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

defined( 'ABSPATH' ) || exit();

if ( ! function_exists( 'LP_Abstract_Course' ) ) {

	/**
	 * Class LP_Abstract_Course.
	 */
	abstract class LP_Abstract_Course extends LP_Abstract_Post_Data {
		/**
		 * Course type
		 *
		 * @var string .
		 */
		public $course_type = null;

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
			'status'              => '',
			'require_enrollment'  => '',
			'price'               => '',
			'regular_price'       => '',
			'sale_price'          => '',
			'sale_start'          => '',
			'sale_end'            => '',
			'duration'            => 0,
			'max_students'        => 0,
			'students'            => 0,
			'retake_count'        => 0,
			'featured'            => '',
			'course_result'       => '',
			'passing_conditional' => '',
			'external_link'       => '',
			'payment'             => '',
		);

		protected $_loaded = false;

		/**
		 * @var int
		 */
		protected $user_id = 0;

		public function set_user( $user ) {
			if ( is_numeric( $user ) ) {
				$this->user_id = absint( $user );
			} elseif ( $user instanceof LP_User ) {
				$this->user_id = $user->get_id();
			}
		}

		public function get_user( $return = 'id' ) {
			if ( $return === 'id' ) {
				return $this->user_id;
			}

			if ( $this->user_id ) {
				return learn_press_get_user( $this->user_id );
			}

			return false;
		}

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
		 *
		 * Todo: optimize this function
		 *
		 * @since 3.0.0
		 * @editor tungnx
		 * @version 1.0.1
		 */
		public function load() {
			if ( $this->_loaded ) {
				return;
			}

			$this->load_data();

			/*$can_load_curriculum = false;
			// Check if edit course, single course, single item can be load
			if ( in_array( LP_Page_Controller::page_current(), array( LP_PAGE_SINGLE_COURSE, LP_PAGE_SINGLE_COURSE_CURRICULUM ) ) ) {
				$can_load_curriculum = true;
			} elseif ( is_admin() && is_callable( 'get_current_screen' ) ) {
				$current_screen = get_current_screen();
				if ( $current_screen && LP_COURSE_CPT === $current_screen->id ) {
					$can_load_curriculum = true;
				}
			} elseif ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				if ( isset( $_REQUEST['sectionID'] ) || isset( $_REQUEST['sectionId'] ) ) {
					$can_load_curriculum = true;
				}
			}

			if ( $can_load_curriculum ) {
				$this->load_curriculum();
			}*/

			$this->_loaded = true;
		}

		public function load_data() {
			$id          = $this->get_id();
			$post_object = get_post( $id );

			// Regular price
			$regular_price = get_post_meta( $id, '_lp_price', true ); // For LP version < 1.4.1.2
			if ( metadata_exists( 'post', $this->get_id(), '_lp_regular_price' ) ) {
				$regular_price = get_post_meta( $id, '_lp_regular_price', true );
			}

			$block_course_finished = get_post_meta(
				$id,
				'_lp_block_finished',
				true
			);

			$this->_set_data(
				array(
					'status'                         => $post_object->post_status,
					'no_required_enroll'             => get_post_meta( $id, '_lp_no_required_enroll', true ),
					'price'                          => get_post_meta( $id, '_lp_price', true ),
					'regular_price'                  => $regular_price,
					'sale_price'                     => get_post_meta( $id, '_lp_sale_price', true ),
					'sale_start'                     => get_post_meta( $id, '_lp_sale_start', true ),
					'sale_end'                       => get_post_meta( $id, '_lp_sale_end', true ),
					'duration'                       => get_post_meta( $id, '_lp_duration', true ),
					'max_students'                   => get_post_meta( $id, '_lp_max_students', true ),
					'students'                       => false,
					'fake_students'                  => get_post_meta( $id, '_lp_students', true ),
					'retake_count'                   => get_post_meta( $id, '_lp_retake_count', true ),
					'featured'                       => get_post_meta( $id, '_lp_featured', true ),
					'course_result'                  => get_post_meta( $id, '_lp_course_result', true ),
					'passing_condition'              => get_post_meta( $id, '_lp_passing_condition', true ),
					'final_quiz'                     => get_post_meta( $id, '_lp_final_quiz', true ),
					'external_link'                  => get_post_meta( $id, '_lp_external_link_buy_course', true ),
					'block_course_duration_expire'   => get_post_meta(
						$id,
						'_lp_block_expire_duration',
						true
					),
					'block_course_finished'          => $block_course_finished ? $block_course_finished : 'yes',
					'allow_repurchase'               => get_post_meta( $id, '_lp_allow_course_repurchase', true ),
					'allow_repurchase_course_option' => get_post_meta( $id, '_lp_course_repurchase_option', true ),
					'excerpt'                        => $post_object->post_excerpt,
				)
			);
		}

		/**
		 * Load course curriculum.
		 *
		 * @deprecated 4.1.6.9
		 */
		public function load_curriculum() {
			_deprecated_function( __FUNCTION__, '4.1.6.9' );
			/*$item_ids      = array();
			$item_types    = array();
			$item_by_types = array();
			$section_items = array();

			$items = $this->_curd->read_course_curriculum( $this->get_id() );
			if ( $items ) {
				foreach ( $items as $item ) {
					$item_ids[] = $item->id;

					// Group items by it type
					if ( empty( $item_types[ $item->type ] ) ) {
						$item_types[ $item->type ] = array();
					}

					$item_types[ $item->type ][] = $item->id;

					// Group items by it section
					if ( empty( $section_items[ $item->section_id ] ) ) {
						$section_items[ $item->section_id ] = array();
					}

					$section_items[ $item->section_id ][] = $item->id;
					$item_by_types[ $item->id ]           = $item->type;

				}
			}

			LP_Course_Utils::set_course_items( $this->get_id(), $item_ids );
			LP_Course_Utils::set_course_item_types( $this->get_id(), $item_by_types );
			LP_Course_Utils::set_course_items_group_types( $this->get_id(), $item_types );

			foreach ( $section_items as $section_id => $its ) {
				LP_Course_Utils::set_section_items( $section_id, $its );
			}*/
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
		public function get_image( string $size = 'course_thumbnail', array $attr = array() ): string {
			$image = LP_Thumbnail_Helper::instance()->get_course_image( $this->get_id(), $size, $attr );

			return apply_filters( 'learn-press/course/image', $image, $this->get_id(), $size, $attr );
		}

		/**
		 * Get course thumbnail, return placeholder if it does not exists
		 *
		 * @param string $size
		 *
		 * @return string
		 */
		public function get_image_url( $size = 'course_thumbnail' ) {
			$course_id = $this->get_id();
			$url       = '';
			$parent_id = wp_get_post_parent_id( $course_id );

			if ( has_post_thumbnail( $course_id ) ) {
				$url = get_the_post_thumbnail_url( $course_id, $size );
			} elseif ( $parent_id && has_post_thumbnail( $parent_id ) ) {
				$url = get_the_post_thumbnail_url( $parent_id, $size );
			}

			if ( ! $url ) {
				$url = LearnPress::instance()->image( 'no-image.png' );
			}

			return apply_filters( 'learn-press/course-thumbnail-url', $url, $this->get_id(), $size );
		}

		/**
		 * @return false|string
		 */
		public function get_permalink() {
			return get_the_permalink( $this->get_id() );
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
		 * Check this course is required enroll.
		 *
		 * @return bool
		 */
		public function is_required_enroll(): bool {
			return ! $this->is_no_required_enroll();
		}

		/**
		 * @deprecated 4.2.0
		 */
		public function is_require_enrollment() {
			_deprecated_function( __METHOD__, '4.2.0' );
			return ! $this->is_no_required_enroll();
		}

		/**
		 * Check if this course is required enroll or not.
		 *
		 * @author hungkv
		 * @since 4.0.5
		 * @return bool
		 * @version 1.0.1
		 */
		public function is_no_required_enroll(): bool {
			return $this->get_data( 'no_required_enroll', 'no' ) === 'yes' && ! is_user_logged_in();
		}

		/**
		 * @deprecated 4.1.6.9
		 */
		/*public function get_item_types( $group = false ) {
			$cache_key = $group ? 'course-item-group-types' : 'course-item-types';
			$items     = LP_Object_Cache::get( 'course-' . $this->get_id(), "learn-press/{$cache_key}" );
			$items     = false;

			if ( false === $items ) {
				$item_types = array();
				$items      = array();
				$sections   = array();
				$all_items  = $this->_curd->read_course_items( $this->get_id() );

				if ( $all_items ) {
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

				$items = $group ? $item_types : $items;
			}

			return apply_filters( "learn-press/{$cache_key}", $items, $this->get_id() );
		}*/

		/**
		 * Get all item's ids in a course.
		 *
		 * @param int $section_id
		 *
		 * @return array
		 * @version 4.0.1
		 * @modify 4.1.6.9 tungnx
		 */
		public function get_item_ids( int $section_id = 0 ): array {
			$item_ids = array();

			$sections_items = $this->get_full_sections_and_items_course();
			foreach ( $sections_items as $section_items ) {
				foreach ( $section_items->items as $item ) {
					if ( $section_id ) {
						if ( $section_id == $section_items->id ) {
							$item_ids[] = $item->id;
						}

						continue;
					}

					$item_ids[] = $item->id;
				}
			}

			return apply_filters( 'learn-press/course-item-ids', $item_ids, $this->get_id() );
		}

		/**
		 * Get item is viewing in single course.
		 *
		 * @return LP_Course_Item
		 * @deprecated 4.1.6.9
		 */
		public function get_viewing_item() {
			_deprecated_function( __FUNCTION__, '4.1.6.9' );
			//return apply_filters( 'learn-press/single-course-viewing-item', $this->_viewing_item, $this->get_id() );
		}

		/**
		 * Get total students fake.
		 *
		 * @return int
		 */
		public function get_fake_students() : int {
			return absint( $this->get_data( 'fake_students', 0 ) );
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

			return apply_filters( 'learn-press/course/users-enrolled', $enrolled, $this );
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
		 * Get instructor html of course.
		 *
		 * @param int|bool $with_avatar
		 * @param string   $link_class
		 *
		 * @return string
		 */
		public function get_instructor_html( $with_avatar = false, $link_class = '' ): string {
			$html = '';

			try {
				$instructor = $this->get_author();
				if ( ! $instructor ) {
					return '';
				}

				$singleInstructorTemplate = SingleInstructorTemplate::instance();

				$html = apply_filters(
					'learn-press/course/instructor-html',
					sprintf(
						'<a href="%s"%s>%s %s</a>',
						$instructor->get_url_instructor(),
						$link_class ? sprintf( 'class="%s"', $link_class ) : '',
						$with_avatar ? $instructor->get_profile_picture() : '',
						$singleInstructorTemplate->html_display_name( $instructor )
					),
					$instructor,
					$singleInstructorTemplate
				);
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $html;
		}

		/**
		 * Check if a course is Free
		 *
		 * @return bool
		 */
		public function is_free(): bool {
			return apply_filters( 'learn-press/course/is-free', $this->get_price() == 0, $this->get_id() );
		}

		/**
		 * Get the sale price of course. Check if sale price is set
		 * and the dates are valid.
		 *
		 * @return string|float
		 */
		public function get_sale_price() {
			$sale_price_value = $this->get_data( 'sale_price', '' );

			if ( '' !== $sale_price_value ) {
				return apply_filters( 'learn-press/course/sale-price', floatval( $sale_price_value ), $this->get_id() );
			}

			return $sale_price_value;
		}

		/**
		 * Check course has 'sale price'
		 *
		 * @return mixed
		 */
		public function has_sale_price() {
			$has_sale_price = false;
			$regular_price  = $this->get_regular_price();
			$sale_price     = $this->get_sale_price();
			$start_date     = $this->get_data( 'sale_start', '' );
			$end_date       = $this->get_data( 'sale_end', '' );

			if ( $regular_price > $sale_price && is_float( $sale_price ) ) {
				$has_sale_price = true;
			}

			// Check in days sale
			if ( $has_sale_price && '' !== $start_date && '' !== $end_date ) {
				$nowObj = new LP_Datetime();
				// Compare via timezone WP
				$nowStr = $nowObj->toSql( true );
				$now    = strtotime( $nowStr );
				$end    = strtotime( $end_date );
				$start  = strtotime( $start_date );

				$has_sale_price = $now >= $start && $now <= $end;
			}

			return apply_filters( 'learn-press/course/has-sale-price', $has_sale_price, $this->get_id() );
		}

		/**
		 * Get the regular price of course.
		 *
		 * @return float
		 */
		public function get_regular_price(): float {
			$price = floatval( $this->get_data( 'regular_price', 0 ) );

			return apply_filters( 'learn-press/course/regular-price', $price, $this->get_id() );
		}

		/**
		 * Get the regular price format of course.
		 *
		 * @since 4.1.5
		 * @version 1.0.0
		 * @author tungnx
		 * @return mixed
		 */
		public function get_regular_price_html() {
			$price = learn_press_format_price( $this->get_regular_price(), true );

			return apply_filters( 'learn-press/course/regular-price-html', $price, $this->get_id() );
		}

		/**
		 * Get the price of course.
		 *
		 * @return mixed
		 */
		public function get_price() {
			$key_cache = "{$this->get_id()}/price";
			$price     = LP_Course_Cache::cache_load_first( 'get', $key_cache );

			if ( false === $price ) {
				if ( $this->has_sale_price() ) {
					$price = $this->get_sale_price();
					// Add key _lp_course_is_sale for query - Todo: Check performance, need write function get all courses, and set on Admin, on background
					//update_post_meta( $this->get_id(), '_lp_course_is_sale', 1 );
				} else {
					// Delete key _lp_course_is_sale
					//delete_post_meta( $this->get_id(), '_lp_course_is_sale' );
					$price = $this->get_regular_price();
				}

				// Save price only on page Single Course
				/*if ( LP_PAGE_SINGLE_COURSE === LP_Page_Controller::page_current() ) {
					update_post_meta( $this->get_id(), '_lp_price', $price );
				}*/

				LP_Course_Cache::cache_load_first( 'set', $key_cache, $price );
			}

			return apply_filters( 'learn-press/course/price', $price, $this->get_id() );
		}

		/**
		 * Get html course price
		 *
		 * @author tungnx
		 * @since 4.1.5
		 * @version 1.0.1
		 * @return string
		 */
		public function get_course_price_html(): string {
			$price_html = '';

			if ( $this->is_free() ) {
				if ( is_float( $this->get_sale_price() ) ) {
					$price_html .= sprintf( '<span class="origin-price">%s</span>', $this->get_regular_price_html() );
				}

				$price_html .= sprintf( '<span class="free">%s</span>', esc_html__( 'Free', 'learnpress' ) );
				$price_html  = apply_filters( 'learn_press_course_price_html_free', $price_html, $this );
			} elseif ( $this->get_data( 'no_required_enroll', 'no' ) === 'yes' ) {
				$price_html .= '';
			} else {
				if ( $this->has_sale_price() ) {
					$price_html .= sprintf( '<span class="origin-price">%s</span>', $this->get_regular_price_html() );
				}

				$price_html .= sprintf( '<span class="price">%s</span>', learn_press_format_price( $this->get_price(), true ) );
				$price_html  = apply_filters( 'learn_press_course_price_html', $price_html, $this->has_sale_price(), $this->get_id() );
			}

			return sprintf( '<span class="course-item-price">%s</span>', $price_html );
		}

		/**
		 * Get the price of course with html
		 *
		 * @editor tungnx
		 * @modify 4.1.5
		 * @version 1.0.1
		 * @return mixed
		 * @deprecated 4.1.5
		 */
		public function get_price_html() {
			$price_html = '';

			if ( $this->is_free() ) {
				$price_html = apply_filters(
					'learn_press_course_price_html_free',
					esc_html__( 'Free', 'learnpress' ),
					$this
				);
			} else {
				$price_html .= learn_press_format_price( $this->get_price() );
				$price_html  = apply_filters( 'learn_press_course_price_html', $price_html, $this->has_sale_price(), $this->get_id() );
			}

			return $price_html;
		}

		/**
		 * Get the origin price of course
		 *
		 * @return float
		 * @deprecated 4.1.5
		 */
		public function get_origin_price() {
			return $this->get_regular_price();
		}

		/**
		 * Get the price of course with html
		 *
		 * @return mixed
		 * @deprecated 4.1.5
		 */
		public function get_origin_price_html() {
			return $this->get_regular_price_html();
		}

		/**
		 * @param bool $item_id
		 *
		 * @return bool|mixed
		 */
		public function is_viewing_item( $item_id = false ) {
			$item = LP_Global::course_item();

			if ( empty( $item ) ) {
				return false;
			}

			return apply_filters(
				'learn-press/is-viewing-item',
				false !== $item_id ? $item_id == $item->get_id() : $item->get_id(),
				$item_id,
				$this->get_id()
			);
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
		 * Required enroll.
		 * Status is publish.
		 *
		 * @return mixed
		 */
		public function is_purchasable() {
			$is_purchasable = $this->exists() && ! $this->is_no_required_enroll() && get_post_status( $this->get_id() ) == 'publish';

			return apply_filters( 'learn-press/is-purchasable', $is_purchasable, $this->get_id() );
		}

		/**
		 * Check if students have purchased course is reached.
		 * For case check course can purchase.
		 *
		 * @return mixed
		 * @since 3.0.0
		 * @version 1.0.1
		 */
		public function is_in_stock() {
			$in_stock    = true;
			$max_allowed = $this->get_max_students();

			if ( $max_allowed ) {
				$in_stock = $max_allowed > $this->get_total_user_enrolled_or_purchased();
			}

			return apply_filters( 'learn-press/is-in-stock', $in_stock, $this->get_id() );
		}

		/**
		 * Check max student can enroll.
		 * For case check course can enroll.
		 *
		 * @return mixed
		 * @since 4.2.5.7
		 * @version 1.0.0
		 */
		public function is_in_stock_enroll() {
			$in_stock    = true;
			$max_allowed = $this->get_max_students();

			if ( $max_allowed ) {
				$in_stock = $max_allowed > $this->get_total_user_enrolled_or_purchased();
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
			return apply_filters(
				'learn-press/max-students',
				absint( $this->get_data( 'max_students' ) ),
				$this->get_id()
			);
		}

		/**
		 * Count number of students enrolled course.
		 * Check global settings `enrolled_students_number`
		 * and add the fake value if both are set.
		 *
		 * @return int
		 * @editor tungnx
		 * @version 1.0.1
		 * @since 3.0.0
		 * @Todo: view and rewrite this function
		 */
		public function count_students(): int {
			$total  = $this->get_total_user_enrolled_or_purchased();
			$total += $this->get_fake_students();

			return $total;
		}

		/**
		 * Get total user enrolled
		 *
		 * @since 4.1.4
		 * @version 1.0.1
		 * @return int
		 */
		public function get_total_user_enrolled(): int {
			$total           = 0;
			$lp_course_cache = new LP_Course_Cache( true );

			try {
				$total = $lp_course_cache->get_total_students_enrolled( $this->get_id() );
				if ( false !== $total ) {
					return $total;
				}

				$lp_course_db = LP_Course_DB::getInstance();
				$total        = $lp_course_db->get_total_user_enrolled( $this->get_id() );
				$lp_course_cache->set_total_students_enrolled( $this->get_id(), $total );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $total;
		}

		/**
		 * Get total user enrolled include fake
		 * @since 4.2.2
		 * @return int
		 */
		/*public function get_total_user_enrolled_include_fake() {
			$total_user_enrolled = 0;
			$key_cache           = "{$this->get_id()}/total-students-include-fake";

			try {
				$total_user_enrolled = LP_Course_Cache::cache_load_first( 'get', $key_cache );

				if ( false === $total_user_enrolled ) {
					$lp_course_db         = LP_Course_DB::getInstance();
					$total_user_enrolled  = $lp_course_db->get_total_user_enrolled( $this->get_id() );
					$total_user_enrolled += $this->get_fake_students();

					LP_Course_Cache::cache_load_first( 'set', $key_cache, $total_user_enrolled );
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $total_user_enrolled;
		}*/

		/**
		 * Get total user enrolled, purchased or finished
		 *
		 * @since 4.1.4
		 * @version 1.0.1
		 * @return int
		 */
		public function get_total_user_enrolled_or_purchased(): int {
			$total           = 0;
			$lp_course_cache = new LP_Course_Cache( true );

			try {
				$total = $lp_course_cache->get_total_students_enrolled_or_purchased( $this->get_id() );
				if ( false !== $total ) {
					return $total;
				}

				$lp_course_db = LP_Course_DB::getInstance();
				$total        = $lp_course_db->get_total_user_enrolled_or_purchased( $this->get_id() );
				$lp_course_cache->set_total_students_enrolled_or_purchased( $this->get_id(), $total );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return $total;
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
				$orders = get_post_meta( $this->get_id(), 'order-' . $status, true );

				if ( $orders ) {
					$count += sizeof( $orders );
				}
			}

			return $count;
		}

		/**
		 * @editor tungnx
		 * @modify 4.1.4 - comment - not use
		 */
		/*public function count_completed_orders() {
			$orders = $this->get_meta( 'order-completed' );

			if ( $orders ) {
				$count = sizeof( $orders );
			} else {
				$count = 0;
			}

			return $count;
		}*/

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
			$items = $this->get_item_ids();

			if ( $items ) {
				$found = in_array( $item_id, $items );
			}

			return apply_filters( 'learn-press/course-has-item', $found, $item_id, $this->get_id() );
		}

		/**
		 * Get course's item (lesson/quiz/etc...).
		 *
		 * @param int $item_id Course's item Id.
		 *
		 * @return LP_Lesson|LP_Quiz|boolean
		 */
		public function get_item( $item_id ) {
			$item = LP_Course_Item::get_item( $item_id );
			if ( $item instanceof LP_Course_Item ) {
				$item->set_course( $this->get_id() );
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
		public function get_passing_condition( $format = false ) {
			$value = absint( $this->get_data( 'passing_condition' ) );

			if ( $format ) {
				$value = "{$value}%";
			}

			return apply_filters(
				'learn-press/course-passing-condition',
				$value,
				$format,
				$this->get_id()
			);
		}

		/**
		 * Get item's link
		 *
		 * @param int $item_id
		 *
		 * @editor tungnx
		 * @since 3.0.0
		 * @version 1.0.1
		 * @return string
		 */
		public function get_item_link( int $item_id ): string {
			$item_link = '';
			$item_type = get_post_type( $item_id );

			$course_permalink = trailingslashit( $this->get_permalink() );
			$item_slug        = get_post_field( 'post_name', $item_id );

			$slug_prefixes = apply_filters(
				'learn-press/course/custom-item-prefixes',
				array(
					LP_QUIZ_CPT   => sanitize_title_with_dashes( LP_Settings::get_option( 'quiz_slug', 'quizzes' ) ),
					LP_LESSON_CPT => sanitize_title_with_dashes( LP_Settings::get_option( 'lesson_slug', 'lessons' ) ),
				),
				$this->get_id()
			);

			$slug_prefix = trailingslashit( $slug_prefixes[ $item_type ] ?? '' );
			$item_link   = trailingslashit( $course_permalink . $slug_prefix . $item_slug );

			return apply_filters( 'learn-press/course/item-link', $item_link, $item_id, $this );
		}

		/**
		 * Get course's item at a position.
		 *
		 * @param int $at
		 *
		 * @return bool|mixed
		 * @editor tungnx
		 * @modify 4.1.3 - comment - not use
		 */
		/*public function get_item_at( $at ) {
			$items = $this->get_items();

			if ( ! $items ) {
				return false;
			}

			return ! empty( $items[ $at ] ) ? $items[ $at ] : false;
		}*/

		/**
		 * Get position of an item in course curriculum.
		 *
		 * @param LP_Course_Item|LP_User_Item|int $item
		 *
		 * @return mixed
		 * @deprecated 4.1.6.9
		 */
		/*public function get_item_position( $item ) {
			$items = $this->get_items();

			if ( ! $items ) {
				return false;
			}

			$item_id = is_a( $item, 'LP_User_Item' ) || is_a(
				$item,
				'LP_Course_Item'
			) ? $item->get_id() : absint( $item );

			return array_search( $item_id, $items );
		}*/

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

			return apply_filters( 'learn-press/course/next-item', $item_nav ? $item_nav[2] : 0, $this->get_id(), $args );
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
			if ( ! is_array( $item_nav ) || empty( $item_nav ) ) {
				return 0;
			}

			return apply_filters( 'learn-press/course/prev-item', $item_nav[0], $this->get_id(), $args );
		}

		/**
		 * Get item standing before and after an item.
		 * If the item is not passed consider it is item viewing.
		 *
		 * @param bool $current_item
		 * @param bool $viewable - Optional. TRUE will get next item is viewable.
		 *
		 * @return array|bool
		 * @since 3.1.0
		 */
		public function get_item_nav( $current_item = false, $viewable = false ) {
			if ( false === $current_item ) {
				$current_item = $this->get_current_item();
			}

			if ( false === $current_item ) {
				return false;
			}

			$prev_id  = $next_id = 0;
			$item_ids = $this->get_item_ids();

			if ( $item_ids ) {
				$pos = array_search( $current_item, $item_ids );

				if ( false !== $pos ) {
					$max     = sizeof( $item_ids ) - 1;
					$user    = learn_press_get_current_user();
					$pos_tmp = $pos;

					while ( $pos_tmp < $max ) {
						$pos_tmp ++;

						if ( ! $viewable ) {
							$next_id = $item_ids[ $pos_tmp ];

							break;
						}
					}

					$pos_tmp = $pos;

					while ( $pos_tmp > 0 ) {
						$pos_tmp --;

						if ( ! $viewable ) {
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
					'dir'          => 'next',
				)
			);

			$next_item = $this->get_next_item( $args );

			if ( $next_item ) {
				ob_start();

				learn_press_get_template(
					'content-lesson/next-button.php',
					array(
						'item'   => $next_item,
						'course' => $this,
					)
				);

				return ob_get_clean();
			}

			return false;
		}

		public function get_prev_item_html( $args = null ) {

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
		 * Get course duration in seconds
		 *
		 * @return int
		 */
		public function get_duration() {
			/**
			 * Duration is in string such as 10 week, 4 hour, etc...
			 * So we can use strtotime('+10 week') to convert it to seconds
			 */
			return strtotime( '+' . $this->get_data( 'duration' ), 0 );
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
					$html   = sprintf( __( 'This course will end within the next %s', 'learnpress' ), $remain );
				}
			}

			return $html;
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
			return apply_filters( 'learn-press/course-external-link', $this->get_data( 'external_link', '' ), $this->get_id() );
		}

		public function get_external_link_text() {
			return apply_filters( 'learn-press/course-external-link-text', _x( 'More Info', 'External Link button text', 'learnpress' ), $this->get_id() );
		}

		/**
		 * Get main author of course.
		 *
		 * @param string $field
		 *
		 * @return LP_User|int
		 */
		public function get_author( string $field = '' ) {
			$author_id = absint( get_post_field( 'post_author', $this->get_id() ) );

			return strtolower( $field ) === 'id' ? $author_id : learn_press_get_user( $author_id );
		}

		/**
		 * Get author's display name
		 *
		 * @return string
		 * @since 3.0.9
		 */
		public function get_author_display_name() {
			$display_name = '';
			$user         = $this->get_author();

			if ( $user ) {
				$display_name = $user->get_display_name();
			}

			return $display_name;
		}

		/**
		 * @return mixed
		 */
		public function get_tags() {
			return apply_filters( 'learn-press/course-tags', get_the_term_list( $this->get_id(), 'course_tag', __( 'Tags: ', 'learnpress' ), ' ', '' ) );
		}

		/**
		 * Get extra info of course.
		 * Target Audience, Key Features, Requirements, etc...
		 *
		 * @param string $type [target_audience, key_features, requirements]
		 *
		 * @return string|array
		 * @since 3.x.x
		 */
		public function get_extra_info( $type ) {
			$extra_info_meta = get_post_meta( $this->get_id(), '_lp_' . $type, true );

			return apply_filters( 'learn-press/course-extra-info', $extra_info_meta, $type, $this->get_id() );
		}

		/**
		 * Get FAQs in Tab metabox.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		public function get_faqs(): array {
			$faqs = array();
			$data = get_post_meta( $this->get_id(), '_lp_faqs', true );

			if ( $data ) {
				foreach ( $data as $faq ) {
					$faqs[] = array(
						'question' => $faq[0],
						'answer'   => $faq[1],
					);
				}
			}

			return apply_filters( 'learn-press/course-faqs', $faqs, $this->get_id() );
		}

		/**
		 * Get course is set featured
		 *
		 * @return bool
		 */
		public function is_featured(): bool {
			return apply_filters(
				'learn-press/course-is-featured',
				get_post_meta( $this->get_id(), '_lp_featured', true ) === 'yes',
				$this->get_id(),
				$this
			);
		}

		/**
		 * [get_downloadable_material get all material files of this course and lesson of this course]
		 * @return [array] [array of material files or empty array]
		 * @deprecated 4.2.6.4
		 */
		/*public function get_downloadable_material(): array {
			$material  = LP_Material_Files_DB::getInstance();
			$materials = $material->get_material_by_item_id( $this->get_id(), 1 );
			return apply_filters( 'learn-press/course-materials', $materials, $this->get_id() );
		}*/
	}
}
