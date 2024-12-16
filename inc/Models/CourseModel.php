<?php

/**
 * Class Course Model
 * Purpose: Use to map property separate table learnpress_course
 * Field json for store all value of single course.
 * Another fields for query list courses faster
 *
 * @package LearnPress/Classes
 * @version 1.0.2
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LearnPress\Models\UserItems\UserCourseModel;
use LP_Course_Cache;
use LP_Course_DB;
use LP_Course_Item;
use LP_Course_JSON_DB;
use LP_Course_JSON_Filter;
use LP_Datetime;
use LP_Helper;
use LP_Settings;
use stdClass;
use Throwable;
use WP_Error;

class CourseModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	public $ID = 0;
	/**
	 * @var string author id, foreign key
	 */
	public $post_author = 0;
	/**
	 * @var string post date gmt
	 */
	public $post_date_gmt = null;
	/**
	 * @var string post content
	 */
	public $post_content = '';
	/**
	 * @var string Post title
	 */
	public $post_title = '';
	/**
	 * @var string Post Status (publish, draft, ...)
	 */
	public $post_status = '';
	/**
	 * @var string Post name (slug for link)
	 */
	public $post_name = '';
	/**
	 * @var float price only using for filter courses, don't use for course detail
	 * Because price can change by date if set schedule sale
	 */
	public $price_to_sort = 0;
	public $is_sale       = 0;
	/**
	 * @var string JSON Store all data a single course
	 */
	public $json = null; // Only set when save, don't set when get
	/**
	 * @var string lang of Course
	 */
	public $lang = null;
	/********** Field not on table **********/
	/**
	 * @var stdClass all meta data
	 */
	public $meta_data = null;
	public $image_url = '';
	public $permalink = '';
	public $categories;
	public $tags;
	private $price             = 0; // Not save in database, must auto reload calculate
	private $passing_condition = '';
	public $post_excerpt       = '';
	/**
	 * @var int ID of first item
	 */
	public $first_item_id;
	/**
	 * @var null|object info total items {'count_items': 20, 'lp_lesson': 10, 'lp_quiz': 10, ...}
	 */
	public $total_items;
	/**
	 * @var array list sections items
	 */
	public $sections_items;

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}

		if ( is_null( $this->meta_data ) ) {
			$this->meta_data = new stdClass();
		}
	}

	/**
	 * Map array, object data to CourseModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return CourseModel
	 */
	public function map_to_object( $data ): CourseModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get course id
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->ID;
	}

	public function get_title(): string {
		$course_post = new CoursePostModel( $this );

		return $course_post->get_the_title();
	}

	/**
	 * Get image url
	 * if not check get from Post
	 *
	 * @return string
	 * @throws Exception
	 */
	public function get_image_url(): string {
		$image_url = '';

		if ( ! empty( $this->image_url ) ) {
			return $this->image_url;
		}

		$post      = new CoursePostModel( $this );
		$image_url = $post->get_image_url();

		$this->image_url = $image_url;

		return $image_url;
	}

	/**
	 * Get author model
	 * Check has data on table learnpress_courses return
	 * if not check get from Post
	 *
	 * @return UserModel|false
	 */
	public function get_author_model() {
		$post = new CoursePostModel( $this );
		return $post->get_author_model();
	}

	/**
	 * Get status of course
	 *
	 * @return string
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function get_status(): string {
		return $this->post_status;
	}

	/**
	 * Get categories
	 * Check has data on table learnpress_courses return
	 * if not check get from Post
	 *
	 * @return array
	 */
	public function get_categories(): array {
		if ( isset( $this->categories ) ) {
			return $this->categories;
		}

		$post       = new PostModel( $this );
		$categories = $post->get_categories();

		$this->categories = $categories;

		return $this->categories;
	}

	/**
	 * Get tags of course.
	 *
	 * @return array
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function get_tags(): array {
		if ( isset( $this->tags ) ) {
			return $this->tags;
		}

		$post = new PostModel( $this );
		$tags = $post->get_tags();

		$this->tags = $tags;

		return $this->tags;
	}

	/**
	 * Get price
	 *
	 * @return float
	 */
	public function get_price(): float {
		if ( ! empty( $this->price ) ) {
			return $this->price;
		}

		if ( $this->has_sale_price() ) {
			$price = $this->get_sale_price();
		} else {
			$price = $this->get_regular_price();
		}

		$this->price = (float) $price;

		return apply_filters( 'learnPress/course/price', (float) $price, $this->get_id() );
	}

	/**
	 * Get regular price
	 * Check has data on table learnpress_courses return
	 * if not check get from Post
	 * Value can be string empty if not set
	 *
	 * @return float|string
	 */
	public function get_regular_price() {
		$key = CoursePostModel::META_KEY_REGULAR_PRICE;
		if ( $this->meta_data && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$coursePost              = new CoursePostModel( $this );
		$this->meta_data->{$key} = $coursePost->get_regular_price();

		return $this->meta_data->{$key};
	}

	/**
	 * Get sale price
	 * Sale price can is string empty if not set
	 * Sale price set if is number >= 0
	 * Check has data on table learnpress_courses return
	 * if not check get from Post
	 *
	 * @return float|string
	 */
	public function get_sale_price() {
		$key = CoursePostModel::META_KEY_SALE_PRICE;
		if ( $this->meta_data && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$coursePost              = new CoursePostModel( $this );
		$sale_price              = $coursePost->get_sale_price();
		$this->meta_data->{$key} = $sale_price;

		return $this->meta_data->{$key};
	}

	/**
	 * Check course has 'sale price'
	 *
	 * @return bool
	 */
	public function has_sale_price(): bool {
		$has_sale_price = false;
		$regular_price  = $this->get_regular_price();
		$sale_price     = $this->get_sale_price();
		$start_date     = $this->get_sale_start();
		$end_date       = $this->get_sale_end();

		if ( $sale_price !== '' && (float) $regular_price > (float) $sale_price ) {
			$has_sale_price = true;
		}

		// Check in days sale
		if ( $has_sale_price && ! empty( $start_date ) && ! empty( $end_date ) ) {
			$nowObj = new LP_Datetime();
			// Compare via timezone WP
			$nowStr = $nowObj->toSql( true );
			$now    = strtotime( $nowStr );
			$end    = strtotime( $end_date );
			$start  = strtotime( $start_date );

			$has_sale_price = $now >= $start && $now <= $end;
		}

		return apply_filters( 'learnPress/course/has-sale-price', $has_sale_price, $this );
	}

	/**
	 * Get date sale start
	 *
	 * @return mixed
	 */
	public function get_sale_start() {
		return $this->get_meta_value_by_key( CoursePostModel::META_KEY_SALE_START );
	}

	/**
	 * Get date sale end
	 *
	 * @return mixed
	 */
	public function get_sale_end() {
		return $this->get_meta_value_by_key( CoursePostModel::META_KEY_SALE_END );
	}

	/**
	 * Check if a course is Free
	 *
	 * @return bool
	 */
	public function is_free(): bool {
		return apply_filters( 'learnPress/course/is-free', $this->get_price() == 0, $this );
	}

	/**
	 * Check if a course is enabled Offline
	 *
	 * @return bool
	 */
	public function is_offline(): bool {
		return $this->get_meta_value_by_key( CoursePostModel::META_KEY_OFFLINE_COURSE, 'no' ) === 'yes';
	}

	/**
	 * Check option "Block course when expire" enable.
	 *
	 * @return bool
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function enable_block_when_expire(): bool {
		return $this->get_meta_value_by_key( CoursePostModel::META_KEY_BLOCK_EXPIRE_DURATION, 'no' ) === 'yes';
	}

	/**
	 * Get first item of course
	 *
	 * @return int
	 */
	public function get_first_item_id(): int {
		if ( isset( $this->first_item_id ) ) {
			return $this->first_item_id;
		}

		try {
			$this->first_item_id = LP_Course_DB::getInstance()->get_first_item_id( $this->get_id() );
		} catch ( Throwable $e ) {
			$this->first_item_id = 0;
		}

		return $this->first_item_id;
	}

	/**
	 * Get total items of course
	 *
	 * @return null|object
	 */
	public function get_total_items() {
		if ( isset( $this->total_items ) ) {
			return $this->total_items;
		}

		try {
			$this->total_items = LP_Course_DB::getInstance()->get_total_items( $this->get_id() );
		} catch ( Throwable $e ) {
			$this->total_items = null;
		}

		return $this->total_items;
	}

	/**
	 * Get total items of course
	 *
	 * @return array
	 */
	public function get_section_items(): array {
		if ( isset( $this->sections_items ) ) {
			return $this->sections_items;
		}

		try {
			$this->sections_items = $this->get_sections_and_items_course_from_db_and_sort();
		} catch ( Throwable $e ) {
			$this->sections_items = [];
		}

		return $this->sections_items;
	}

	/**
	 * Get course Evaluation type.
	 *
	 * @return string
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function get_evaluation_type(): string {
		return (float) $this->get_meta_value_by_key( CoursePostModel::META_KEY_EVALUATION_TYPE, 'evaluate_lesson' );
	}

	/**
	 * Get course passing condition value.
	 *
	 * @return float
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function get_passing_condition(): float {
		return (float) $this->get_meta_value_by_key( CoursePostModel::META_KEY_PASSING_CONDITION, 80 );
	}

	/**
	 * Get final quiz id
	 *
	 * @return int
	 */
	public function get_final_quiz(): int {
		$key = '_lp_final_quiz';
		if ( ! empty( $this->meta_data->{$key} ) ) {
			return $this->meta_data->$key;
		}

		$final_quiz = 0;

		// Not use array_reverse, it's make change object
		$section_items = $this->get_section_items();
		$found         = 0;
		for ( $i = count( $section_items ); $i > 0; $i-- ) {
			$section = $section_items[ $i - 1 ];
			for ( $j = count( $section->items ); $j > 0; $j-- ) {
				$item = $section->items[ $j - 1 ];
				if ( learn_press_get_post_type( $item->id ) === LP_QUIZ_CPT ) {
					$final_quiz = $item->id;
					$found      = 1;
					break;
				}
			}

			if ( $found ) {
				break;
			}
		}

		$evaluation_type = $this->get_evaluation_type();
		if ( $evaluation_type === 'evaluate_final_quiz' ) {
			if ( isset( $final_quiz ) ) {
				update_post_meta( $this->ID, $key, $final_quiz );
			} else {
				delete_post_meta( $this->ID, $key );
			}
		}

		$this->meta_data->{$key} = $final_quiz;

		return $final_quiz;
	}

	/**
	 * Get all sections and items from database, then handle sort
	 * Only call when data change or not set
	 *
	 * @return array
	 * @since 4.1.6.9
	 * @version 1.0.2
	 * @author tungnx
	 */
	public function get_sections_and_items_course_from_db_and_sort(): array {
		$sections_items = [];
		$course_id      = $this->get_id();
		$lp_course_db   = LP_Course_DB::getInstance();

		try {
			$sections_results       = $lp_course_db->get_sections( $course_id );
			$sections_items_results = $lp_course_db->get_full_sections_and_items_course( $course_id );
			$count_items            = count( $sections_items_results );
			$index_items_last       = $count_items - 1;
			$section_current        = 0;

			foreach ( $sections_items_results as $index => $sections_item ) {
				$section_new      = $sections_item->section_id;
				$section_order    = $sections_item->section_order;
				$item             = new stdClass();
				$item->id         = $sections_item->item_id;
				$item->item_id    = $sections_item->item_id;
				$item->order      = $sections_item->item_order;
				$item->item_order = $sections_item->item_order;
				$item->type       = $sections_item->item_type;
				$item->item_type  = $sections_item->item_type;
				$item_tmp         = LP_Course_Item::get_item( $item->id );
				if ( $item_tmp ) {
					$item->title   = html_entity_decode( $item_tmp->get_title() );
					$item->preview = $item_tmp->is_preview();
				}

				if ( $section_new != $section_current ) {
					$sections_items[ $section_new ]                      = new stdClass();
					$sections_items[ $section_new ]->id                  = $section_new; // old field will be deprecated in future
					$sections_items[ $section_new ]->section_id          = $section_new; // new field
					$sections_items[ $section_new ]->order               = $section_order; // old field will be deprecated in future
					$sections_items[ $section_new ]->section_order       = $section_order; // new field
					$sections_items[ $section_new ]->title               = html_entity_decode( $sections_item->section_name ); // old field will be deprecated in future
					$sections_items[ $section_new ]->section_name        = html_entity_decode( $sections_item->section_name ); // new field
					$sections_items[ $section_new ]->description         = html_entity_decode( $sections_item->section_description ); // old field will be deprecated in future
					$sections_items[ $section_new ]->section_description = html_entity_decode( $sections_item->section_description ); // new field
					$sections_items[ $section_new ]->items               = [];

					// Sort item by item_order
					if ( $section_current != 0 ) {
						usort(
							$sections_items[ $section_current ]->items,
							function ( $item1, $item2 ) {
								return $item1->order - $item2->order;
							}
						);
					}

					$section_current = $section_new;
				}

				$sections_items[ $section_new ]->items[ $item->id ] = $item;

				if ( $index_items_last === $index ) {
					usort(
						$sections_items[ $section_current ]->items,
						function ( $item1, $item2 ) {
							return $item1->order - $item2->order;
						}
					);
				}
			}

			// Check case if section empty items
			foreach ( $sections_results as $section ) {
				$section_id = $section->section_id;
				if ( isset( $sections_items[ $section_id ] ) ) {
					continue;
				}

				$section_obj                      = new stdClass();
				$section_obj->id                  = $section_id;
				$section_obj->section_id          = $section_id;
				$section_obj->order               = $section->section_order;
				$section_obj->section_order       = $section->section_order;
				$section_obj->title               = html_entity_decode( $section->section_name );
				$section_obj->section_name        = html_entity_decode( $section->section_name );
				$section_obj->description         = html_entity_decode( $section->section_description );
				$section_obj->section_description = html_entity_decode( $section->section_description );
				$section_obj->items               = [];
				$sections_items[ $section_id ]    = $section_obj;
			}

			// Sort section by section_order
			usort(
				$sections_items,
				function ( $section1, $section2 ) {
					return $section1->order - $section2->order;
				}
			);
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $sections_items;
	}

	/**
	 * Get permalink course
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		if ( ! empty( $this->permalink ) ) {
			return $this->permalink;
		}

		try {
			$coursePostModel = new CoursePostModel( $this );
			$this->permalink = $coursePostModel->get_permalink();
		} catch ( Throwable $e ) {
			$this->permalink = '';
		}

		return $this->permalink;
	}

	/**
	 * Get value option No enroll requirement
	 *
	 * @return mixed
	 */
	public function get_no_enroll_requirement() {
		return $this->get_meta_value_by_key( CoursePostModel::META_KEY_NO_REQUIRED_ENROLL, 'no' );
	}

	/**
	 * Get description of Course
	 *
	 * @return string
	 */
	public function get_description(): string {
		$course_post = new CoursePostModel( $this );

		return $course_post->get_the_content();
	}

	/**
	 * Get short description of Course
	 *
	 * @return string
	 */
	public function get_short_description(): string {
		$course_post = new CoursePostModel( $this );

		return $course_post->get_the_excerpt();
	}

	/**
	 * Get value option No enroll requirement
	 *
	 * @return bool
	 */
	public function has_no_enroll_requirement(): bool {
		return $this->get_no_enroll_requirement() === 'yes';
	}

	/**
	 * Get value from meta data by key
	 *
	 * @param string $key
	 * @param mixed|false $default_value
	 *
	 * @return false|mixed
	 * @since 4.2.6.9
	 * @version 1.0.1
	 */
	public function get_meta_value_by_key( string $key, $default_value = false ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			$value = maybe_unserialize( $this->meta_data->{$key} );
		} else {
			$coursePost = new CoursePostModel( $this );
			$value      = $coursePost->get_meta_value_by_key( $key, $default_value );
		}

		$this->meta_data->{$key} = $value;

		return $value;
	}

	/**
	 * Check course is in stock
	 * True is in stock, False is out of stock
	 *
	 * @return mixed
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	public function is_in_stock() {
		$in_stock    = true;
		$max_allowed = (int) $this->get_meta_value_by_key( CoursePostModel::META_KEY_MAX_STUDENTS, 0 );

		if ( $max_allowed ) {
			$in_stock = $max_allowed > $this->get_total_user_enrolled_or_purchased();
		}

		return apply_filters( 'learn-press/is-in-stock', $in_stock, $this->get_id() );
	}

	/**
	 * Check course is enable repurchase
	 *
	 * @return bool
	 * @since 4.2.7.2
	 * @version 1.0.1
	 */
	public function enable_allow_repurchase(): bool {
		$enable = $this->get_meta_value_by_key( CoursePostModel::META_KEY_ALLOW_COURSE_REPURCHASE, 'no' );

		return 'yes' === $enable;
	}

	/**
	 * Type repurchase
	 *
	 * @return string
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function get_type_repurchase(): string {
		return $this->get_meta_value_by_key( CoursePostModel::META_KEY_COURSE_REPURCHASE_OPTION, 'reset' );
	}

	/**
	 * Get external link
	 *
	 * @return string
	 */
	public function get_external_link(): string {
		return esc_url_raw(
			$this->get_meta_value_by_key( CoursePostModel::META_KEY_EXTERNAL_LINK_BY_COURSE, '' )
		);
	}

	/**
	 * Get item's link
	 * @move from LP_Abstract_Course
	 *
	 * @param int $item_id
	 *
	 * @since 3.0.0
	 * @version 1.0.1
	 * @return string
	 */
	public function get_item_link( int $item_id ): string {
		$item_type        = get_post_type( $item_id );
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
	 * Get total user enrolled, purchased or finished
	 *
	 * @move from LP_Abstract_Course
	 * @return int
	 * @version 1.0.1
	 * @since 4.1.4
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
	 * Get fake students.
	 *
	 * @return int
	 */
	public function get_fake_students(): int {
		return (int) $this->get_meta_value_by_key( CoursePostModel::META_KEY_STUDENTS, 0 );
	}

	/**
	 * Count number of students enrolled course.
	 * Check global settings `enrolled_students_number`
	 * and add the fake value if both are set.
	 *
	 * @return int
	 * @move from LP_Abstract_Course
	 */
	public function count_students(): int {
		$total  = $this->get_total_user_enrolled_or_purchased();
		$total += $this->get_fake_students();

		return $total;
	}

	/**
	 * Count total items in Course
	 *
	 * @param $item_type
	 *
	 * @return int
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function count_items( $item_type ): int {
		$count = 0;

		$total_items = $this->get_total_items();
		if ( isset( $total_items->{$item_type} ) ) {
			return $total_items->{$item_type};
		}

		return $count;
	}

	/**
	 * Get Duration of course
	 * Timestamp in second
	 *
	 * @return int
	 */
	public function get_duration(): string {
		return $this->get_meta_value_by_key( CoursePostModel::META_KEY_DURATION, '0' );
	}

	/**
	 * Check user can enroll course.
	 * @move from can_enroll_course method of LP_User class, since 4.1.1
	 *
	 * @param UserModel|false $user
	 *
	 * @return bool|WP_Error
	 * @since 4.2.7.3
	 * @version 1.0.1
	 */
	public function can_enroll( $user ) {
		$can_enroll = true;
		$error_code = '';

		$user_id = 0;
		if ( $user instanceof UserModel ) {
			$user_id = $user->get_id();
		}

		try {
			if ( ! in_array( $this->post_status, [ 'publish', 'private' ] ) ) {
				$error_code = 'course_not_publish';
				throw new Exception( __( 'The course is not public', 'learnpress' ) );
			}

			$userCourseModel           = UserCourseModel::find( $user_id, $this->get_id(), true );
			$enable_no_required_enroll = $this->has_no_enroll_requirement();
			$out_of_stock              = ! $this->is_in_stock();

			// Case user can retake course.
			if ( $userCourseModel && $userCourseModel->can_retake() ) {
				$error_code = 'course_can_retry';
				throw new Exception( esc_html__( 'Course can retake.', 'learnpress' ) );
			}

			// Case course is out of stock, show message when user is not login or user_item not exits
			if ( $out_of_stock &&
				( ! $user || ! $userCourseModel || ! $userCourseModel->has_enrolled_or_finished() ) ) {
				$error_code = 'course_out_of_stock';
				throw new Exception( __( 'The course is full of students.', 'learnpress' ) );
			}

			// Case user is logged in and user_item exists
			if ( $userCourseModel && $user ) {
				if ( $userCourseModel->has_enrolled() ) {
					$error_code = 'course_is_enrolled';
					throw new Exception( __( 'This course is already enrolled!', 'learnpress' ) );
				} elseif ( $userCourseModel->has_finished() ) {
					$error_code = 'course_is_finished';
					throw new Exception( __( 'The course is finished.', 'learnpress' ) );
				}
			}

			if ( $enable_no_required_enroll ) {
				if ( ! $user ) {
					$error_code = 'course_is_no_required_enroll_not_login';
					throw new Exception(
						__( 'Enrollment in the course is not mandatory. You can access course for learning now.', 'learnpress' )
					);
				} else {

				}
			} else {
				if ( ! empty( $this->get_external_link() )
					&& ( ! $userCourseModel || $userCourseModel->get_status() === LP_USER_COURSE_CANCEL )
					&& ! $this->is_offline() ) {
					$error_code = 'course_is_external';
					throw new Exception( __( 'The course is external', 'learnpress' ) );
				}

				if ( ! $this->is_free() ) {
					if ( ! $user ) {
						$error_code = 'course_is_not_purchased_not_login';
						throw new Exception( __( 'The course is not purchased.', 'learnpress' ) );
					} elseif ( ! $userCourseModel || ! $userCourseModel->has_purchased() ) {
						$error_code = 'course_is_not_purchased';
						throw new Exception( __( 'The course is not purchased.', 'learnpress' ) );
					}
				}
			}
		} catch ( Throwable $e ) {
			if ( empty( $error_code ) ) {
				$error_code = 'course_can_not_enroll';
			}
			$can_enroll = new WP_Error( $error_code, $e->getMessage() );
		}

		// Hook old
		if ( has_filter( 'learn-press/user/can-enroll-course' ) ) {
			$output          = new stdClass();
			$output->check   = true;
			$output->message = '';
			if ( $can_enroll instanceof WP_Error ) {
				$output->check   = false;
				$output->message = $can_enroll->get_error_message();
			}

			$course_old = learn_press_get_course( $this->get_id() );
			$user_old   = learn_press_get_user( $user_id );
			$output     = apply_filters( 'learn-press/user/can-enroll-course', $output, $course_old, false, $user_old );
			if ( $output === false ) {
				$can_enroll = new WP_Error( '', '' );
			} elseif ( ! $output->check && $output->message ) {
				$can_enroll = new WP_Error( 'error_custom', $output->message );
			}
			//_deprecated_function( 'The learn-press/user/can-enroll-course filter', '4.2.7.3', 'learn-press/user/can-enroll/course' );
		}

		return apply_filters( 'learn-press/user/can-enroll/course', $can_enroll, $this, $user );
	}

	/**
	 * Check user can purchase course.
	 * @move from can_purchase_course method of LP_User class, since 4.0.8
	 * @use LP_User::can_purchase_course
	 *
	 * @param UserModel|false $user
	 *
	 * @return bool|WP_Error
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function can_purchase( $user ) {
		$can_purchase = true;
		$error_code   = '';

		$user_id = 0;
		if ( $user instanceof UserModel ) {
			$user_id = $user->get_id();
		}

		try {
			$can_enroll = $this->can_enroll( $user );
			if ( $can_enroll instanceof WP_Error ) {
				$error_code_return = [
					'course_is_not_purchased_not_login',
					'course_is_not_purchased',
					'course_is_enrolled',
					'course_is_finished',
				];
				if ( ! in_array( $can_enroll->get_error_code(), $error_code_return ) ) {
					$error_code = $can_enroll->get_error_code();
					throw new Exception( $can_enroll->get_error_message() );
				}
			}

			if ( $this->is_free() ) {
				$error_code = 'course_is_free';
				throw new Exception( __( 'The course is free.', 'learnpress' ) );
			}

			$enable_no_required_enroll = $this->has_no_enroll_requirement();
			if ( $enable_no_required_enroll ) {
				$error_code = 'course_is_no_required_enroll';
				throw new Exception(
					__( 'Enrollment in the course is not mandatory. You can access course for learning now.', 'learnpress' )
				);
			}

			$userCourseModel = UserCourseModel::find( $user_id, $this->get_id(), true );
			if ( $user ) {
				if ( $userCourseModel ) {
					if ( $userCourseModel->has_purchased() ) {
						$error_code = 'course_purchased';
						throw new Exception( __( 'Course is purchased', 'learnpress' ) );
					}

					if ( $this->enable_allow_repurchase() ) {
						if ( $userCourseModel->has_enrolled() && $userCourseModel->timestamp_remaining_duration() !== 0 ) {
							$error_code = 'course_is_enrolled';
							throw new Exception( 'This course is already enrolled!' );
						}
					} else {
						if ( $userCourseModel->has_enrolled_or_finished() ) {
							$error_code = 'course_is_enrolled_or_finished';
							throw new Exception( __( 'Course is enrolled or finished', 'learnpress' ) );
						}
					}
				}
			}
		} catch ( Throwable $e ) {
			if ( empty( $error_code ) ) {
				$error_code = 'course_can_not_purchase';
			}
			$can_purchase = new WP_Error( $error_code, $e->getMessage() );
		}

		// Hook old
		if ( has_filter( 'learn-press/user/can-purchase-course' ) ) {
			$can_purchase = apply_filters( 'learn-press/user/can-purchase-course', $can_purchase, $user_id, $this->get_id() );
			if ( $can_purchase === false ) {
				$can_purchase = new WP_Error( '', '' );
			}
			//_deprecated_function( 'The learn-press/user/can-purchase-course filter', '4.2.7.3', 'learn-press/user/can-purchase/course' );
		}

		return apply_filters( 'learn-press/user/can-purchase/course', $can_purchase, $this, $user );
	}

	/**
	 * Get item model if query success.
	 * If not exists, return false.
	 * If exists, return PostModel.
	 *
	 * @param LP_Course_JSON_Filter $filter
	 * @param bool $check_cache
	 *
	 * @return CourseModel|false|static
	 */
	public static function get_item_model_from_db( LP_Course_JSON_Filter $filter, bool $check_cache = false ) {
		$course_model = false;

		try {
			$filter->only_fields = [ 'json', 'post_content' ];
			// Load cache
			if ( $check_cache ) {

				$key_cache       = "course-model/{$filter->ID}/" . md5( json_encode( $filter ) );
				$lp_course_cache = new LP_Course_Cache();
				$course_model    = $lp_course_cache->get_cache( $key_cache );

				if ( $course_model instanceof CourseModel ) {
					return $course_model;
				}
			}

			$course_rs = self::get_course_from_db( $filter );
			if ( $course_rs instanceof stdClass && isset( $course_rs->json ) ) {
				$course_obj   = LP_Helper::json_decode( $course_rs->json );
				$course_model = new static( $course_obj );
				//$course_model->json         = $course_rs->json;
				$course_model->post_content = $course_rs->post_content;
				$course_model->get_author_model();
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $course_model;
	}

	/**
	 * Get course by ID
	 *
	 * @param int $course_id
	 * @param bool $check_cache
	 *
	 * @return false|CourseModel|static
	 */
	public static function find( int $course_id, bool $check_cache = false ) {
		$filter_course     = new LP_Course_JSON_Filter();
		$filter_course->ID = $course_id;
		$key_cache         = "courseModel/find/id/{$course_id}";
		$lp_course_cache   = new LP_Course_Cache();

		// Check cache
		if ( $check_cache ) {
			$course_model = $lp_course_cache->get_cache( $key_cache );
			if ( $course_model instanceof CourseModel ) {
				return $course_model;
			}
		}

		// Query database no cache.
		$course_model = self::get_item_model_from_db( $filter_course );
		if ( false === $course_model ) { // Find on table posts
			$course_rs = CoursePostModel::find( $course_id );
			if ( $course_rs instanceof CoursePostModel ) {
				$course_model = new static( $course_rs );
			}
		}

		// Set cache
		if ( $course_model instanceof CourseModel ) {
			$lp_course_cache->set_cache( $key_cache, $course_model );
		}

		return $course_model;
	}

	/**
	 * Get course from table learnpress_courses
	 *
	 * @return array|object|stdClass|null
	 * @throws Exception
	 */
	private static function get_course_from_db( LP_Course_JSON_Filter $filter ) {
		$lp_course_json_db = LP_Course_JSON_DB::getInstance();
		$lp_course_json_db->get_query_single_row( $filter );
		$query_single_row = $lp_course_json_db->get_courses( $filter );

		return $lp_course_json_db->wpdb->get_row( $query_single_row );
	}

	/**
	 * Save course data to table learnpress_courses.
	 *
	 * @throws Exception
	 * @since 4.2.6.9
	 * @version 1.0.1
	 */
	public function save(): CourseModel {
		$lp_course_json_db = LP_Course_JSON_DB::getInstance();

		$data = [];

		$courseObjToJSON = clone $this;
		unset( $courseObjToJSON->post_content );
		unset( $courseObjToJSON->json );
		$this->json = json_encode( $courseObjToJSON, JSON_UNESCAPED_UNICODE );
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		if ( ! isset( $data['ID'] ) ) {
			throw new Exception( 'Course ID is invalid!' );
		}

		$filter              = new LP_Course_JSON_Filter();
		$filter->ID          = $this->ID;
		$filter->only_fields = [ 'ID' ];
		$course_rs           = self::get_course_from_db( $filter );
		// Check if exists course id.
		if ( empty( $course_rs ) ) { // Insert data.
			$lp_course_json_db->insert_data( $data );
		} else { // Update data.
			$lp_course_json_db->update_data( $data );
		}

		// Clear cache
		$this->clean_caches();

		return $this;
	}

	/**
	 * Delete row
	 *
	 * @throws Exception
	 */
	public function delete() {
		$lp_course_json_db  = LP_Course_JSON_DB::getInstance();
		$filter             = new LP_Course_JSON_Filter();
		$filter->where[]    = $lp_course_json_db->wpdb->prepare( 'AND ID = %d', $this->ID );
		$filter->collection = $lp_course_json_db->tb_lp_courses;
		$lp_course_json_db->delete_execute( $filter );

		// Clear cache
		$this->clean_caches();
	}

	/**
	 * Clean caches
	 *
	 * @since 4.2.7.4
	 * @version 1.0.0
	 * @return void
	 */
	public function clean_caches() {
		$key_cache       = "courseModel/find/id/{$this->ID}";
		$lp_course_cache = new LP_Course_Cache();
		$lp_course_cache->clear( $key_cache );
	}

	/**
	 * Return course's items support.
	 * To replace learn_press_course_get_support_item_types()
	 * Should add hook on addons before use this function.
	 *
	 * @return array
	 * @since 4.2.7.4
	 */
	public static function item_types_support(): array {
		$item_types = [
			LP_LESSON_CPT,
			LP_QUIZ_CPT,
		];

		return apply_filters( 'learn-press/course/item-types-support', $item_types );
	}
}
