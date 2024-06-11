<?php

/**
 * Class Course
 * To replace class LP_User_Item
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LearnPress;
use LP_Course_Cache;
use LP_Course_DB;
use LP_Course_Filter;
use LP_Datetime;
use LP_User;
use LP_User_Guest;

use Throwable;
use WP_Post;
use WP_Term;

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
	 * @var LP_User author model
	 */
	public $author;
	/**
	 * @var string post date
	 */
	public $post_date = null;
	/**
	 * @var string post date gmt
	 */
	public $post_date_gmt = null;
	/**
	 * @var string post content
	 */
	public $post_content = '';
	/**
	 * Item type (course, lesson, quiz ...)
	 *
	 * @var string Item type
	 */
	public $post_title = '';
	/**
	 * Item status (completed, enrolled, finished ...)
	 *
	 * @var string
	 */
	public $post_excerpt = '';
	/**
	 * Item graduation
	 *
	 * @var string (passed, failed, in-progress...)
	 */
	public $post_status = '';
	/**
	 * Ref type (Order, course ...)
	 *
	 * @var string
	 */
	public $post_name = '';
	/**
	 * Parent id
	 *
	 * @var int
	 */
	public $post_type = LP_COURSE_CPT;


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
	}


	/**
	 * Get user model
	 *
	 * @return false|LP_User
	 */
	public function get_author_model() {
		if ( empty( $this->author ) ) {
			$author_id = get_post_field( 'post_author', $this );
			$this->author = learn_press_get_user( $author_id );
		}

		return $this->author;
	}

	/**
	 * Map array, object data to UserItemModel.
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
	 * Get course from database.
	 * If not exists, return false.
	 * If exists, return CourseModel.
	 *
	 * @param LP_Course_Filter $filter
	 * @param bool $no_cache
	 *
	 * @return CourseModel|false|static
	 */
	public static function get_course_model_from_db( LP_Course_Filter $filter, bool $no_cache = true ) {
		$lp_course_db = LP_Course_DB::getInstance();
		$course_model = false;

		try {

		} catch ( Throwable $e ) {

		}

		return $course_model;
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return CourseModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function save(): CourseModel {
		$this->clean_caches();

		return $this;
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 */
	public function clean_caches() {
		// Clear cache.
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return (int) $this->ID;
	}

	public function get_image_url( $size = 'post-thumbnail' ): string {
		$image_url = '';

		if ( has_post_thumbnail( $this ) ) {
			$image_url = get_the_post_thumbnail_url( $this, $size );
		}

		if ( empty( $image_url ) ) {
			$image_url = LearnPress::instance()->image( 'no-image.png' );
		}

		return $image_url;
	}

	/**
	 * Get the price of course.
	 *
	 * @return float
	 */
	public function get_price(): float {
		$key_cache = "{$this->ID}/price";
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

		return apply_filters( 'learnPress/course/price', (float) $price, $this->get_id() );
	}

	/**
	 * Get the regular price of course.
	 *
	 * @return float
	 */
	public function get_regular_price(): float {
		// Regular price
		$regular_price = $this->get_meta_value_by_key( '_lp_price', '' ); // For LP version < 1.4.1.2
		if ( metadata_exists( 'post', $this->ID, '_lp_regular_price' ) ) {
			$regular_price = $this->get_meta_value_by_key( '_lp_regular_price', '' );
		}

		$regular_price = floatval( $regular_price );

		return apply_filters( 'learnPress/course/regular-price', $regular_price, $this );
	}

	/**
	 * Get the sale price of course. Check if sale price is set
	 * and the dates are valid.
	 *
	 * @return string|float
	 */
	public function get_sale_price() {
		$sale_price_value = $this->get_meta_value_by_key( '_lp_sale_price', '' );

		if ( '' !== $sale_price_value ) {
			return floatval( $sale_price_value );
		}

		return $sale_price_value;
	}

	public function get_meta_value_by_key( $key, $default = false ) {
		$value = get_post_meta( $this->ID, $key, true );
		if ( empty( $value ) ) {
			$value = $default;
		}

		return $value;
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
		$start_date     = $this->get_meta_value_by_key( '_lp_sale_start' );
		$end_date       = $this->get_meta_value_by_key( '_lp_sale_end' );

		if ( $regular_price > $sale_price && is_float( $sale_price ) ) {
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
	 * Check if a course is Free
	 *
	 * @return bool
	 */
	public function is_free(): bool {
		return apply_filters( 'learnPress/course/is-free', $this->get_price() == 0, $this );
	}

	/**
	 * Get html course price
	 *
	 * @author tungnx
	 * @since 4.1.5
	 * @version 1.0.1
	 * @return string
	 */
	public function get_price_html(): string {
		$price_html = '';

		if ( $this->is_free() ) {
			if ( is_float( $this->get_sale_price() ) ) {
				$price_html .= sprintf( '<span class="origin-price">%s</span>', $this->get_regular_price_html() );
			}

			$price_html .= sprintf( '<span class="free">%s</span>', esc_html__( 'Free', 'learnpress' ) );
			$price_html  = apply_filters( 'learn_press_course_price_html_free', $price_html, $this );
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
	 * Get the regular price format of course.
	 *
	 * @since 4.1.5
	 * @version 1.0.0
	 * @author tungnx
	 * @return mixed
	 */
	public function get_regular_price_html() {
		$price = learn_press_format_price( $this->get_regular_price(), true );

		return apply_filters( 'learnPress/course/regular-price', $price, $this );
	}

	/**
	 * Get categories of course.
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 * @return array|WP_Term[]
	 */
	public function get_categories(): array {
		// Todo: set cache.
		$wpPost = new WP_Post( $this );
		$categories = get_the_terms( $wpPost, LP_COURSE_CATEGORY_TAX );
		if ( ! $categories ) {
			$categories = array();
		}

		return $categories;
	}
}
