<?php

/**
 * Class Course Model
 * Purpose: Use to map property separate table learnpress_course
 * Field json for store all value of single course.
 * Another fields for query list courses faster
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LP_Course_JSON_DB;
use LP_Course_JSON_Filter;
use LP_Datetime;
use LP_Helper;
use LP_User;
use LP_User_Filter;
use stdClass;
use Throwable;

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
	/**
	 * @var string JSON Store all data a single course
	 */
	public $json = null; // Only set when save, don't set when get
	/**
	 * @var string lang of Course
	 */
	public $lang = null;
	/***** Field not on table *****/
	/**
	 * @var UserModel author model
	 */
	public $author;
	/**
	 * @var stdClass all meta data
	 */
	public $meta_data = null;
	public $image_url = '';
	public $categories = [];
	private $price = 0; // Not save in database, must auto reload calculate
	private $regular_price = 0;
	private $sale_price = '';
	private $sale_start = '';
	private $sale_end = '';
	private $passing_condition = '';
	public $post_excerpt = '';

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
	 * @throws Exception
	 */
	public function get_author_model() {
		if ( ! empty( $this->author ) ) {
			return $this->author;
		}

		$post         = new CoursePostModel( $this );
		$this->author = $post->get_author_model();

		return $this->author;
	}

	/**
	 * Get categories
	 * Check has data on table learnpress_courses return
	 * if not check get from Post
	 *
	 * @return array
	 */
	public function get_categories(): array {
		if ( ! empty( $this->categories ) ) {
			return $this->categories;
		}

		$post       = new PostModel( $this );
		$categories = $post->get_categories();

		$this->categories = $categories;

		return $this->categories;
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

		$this->price = $price;

		return apply_filters( 'learnPress/course/price', (float) $price, $this->get_id() );
	}

	/**
	 * Get regular price
	 * Check has data on table learnpress_courses return
	 * if not check get from Post
	 *
	 * @return float
	 */
	public function get_regular_price(): float {
		if ( ! empty( $this->regular_price ) ) {
			return $this->regular_price;
		}

		$key = CoursePostModel::META_KEY_REGULAR_PRICE;
		if ( $this->meta_data && isset( $this->meta_data->{$key} ) ) {
			$regular_price = $this->meta_data->{$key};
		} else {
			$coursePost    = new CoursePostModel( $this );
			$regular_price = $coursePost->get_regular_price();
		}

		$this->regular_price = (float) $regular_price;

		return $this->regular_price;
	}

	/**
	 * Get regular price
	 * Check has data on table learnpress_courses return
	 * if not check get from Post
	 *
	 * @return float|string
	 */
	public function get_sale_price() {
		if ( $this->sale_price !== '' ) {
			return $this->sale_price;
		}

		$key = CoursePostModel::META_KEY_SALE_PRICE;
		if ( $this->meta_data && isset( $this->meta_data->{$key} ) ) {
			$sale_price = $this->meta_data->{$key};

			if ( '' !== $sale_price ) {
				$sale_price = floatval( $sale_price );
			}
		} else {
			$coursePost = new CoursePostModel( $this );
			$sale_price = $coursePost->get_sale_price();
		}

		$this->sale_price = $sale_price;

		return $this->sale_price;
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
	 * Get date sale start
	 *
	 * @return mixed
	 */
	public function get_sale_start() {
		if ( $this->sale_start !== '' ) {
			return $this->sale_start;
		}

		$this->sale_start = $this->get_meta_value_by_key( CoursePostModel::META_KEY_SALE_START );

		return $this->sale_start;
	}

	/**
	 * Get date sale end
	 *
	 * @return mixed
	 */
	public function get_sale_end() {
		if ( $this->sale_end !== '' ) {
			return $this->sale_end;
		}

		$this->sale_end = $this->get_meta_value_by_key( CoursePostModel::META_KEY_SALE_END );

		return $this->sale_end;
	}

	/**
	 * Check if a course is Free
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function is_free(): bool {
		return apply_filters( 'learnPress/course/is-free', $this->get_price() == 0, $this );
	}

	public function get_meta_value_by_key( string $key, $default = false ) {
		if ( ! empty( $this->meta_data ) && isset( $this->meta_data->{$key} ) ) {
			$value = $this->meta_data->{$key};
		} else {
			$coursePost = new CoursePostModel( $this );
			$value      = $coursePost->get_meta_value_by_key( $key );
		}

		if ( empty( $value ) ) {
			$value = $default;
		}

		$this->meta_data->{$key} = $value;

		return $value;
	}

	/**
	 * Get item model if query success.
	 * If not exists, return false.
	 * If exists, return PostModel.
	 *
	 * @param LP_Course_JSON_Filter $filter
	 * @param bool $no_cache
	 *
	 * @return CourseModel|false|static
	 */
	public static function get_item_model_from_db( LP_Course_JSON_Filter $filter, bool $no_cache = true ) {
		$course_model = false;

		try {
			$filter->only_fields = [ 'json', 'post_content' ];
			$course_rs           = self::get_course_from_db( $filter );
			if ( $course_rs instanceof stdClass && isset( $course_rs->json ) ) {
				$course_obj                 = LP_Helper::json_decode( $course_rs->json );
				$course_model               = new static( $course_obj );
				$course_model->json         = $course_rs->json;
				$course_model->post_content = $course_rs->post_content;
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
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
	 */
	public function save(): CourseModel {
		$lp_course_json_db = LP_Course_JSON_DB::getInstance();

		$data = [];

		$courseObjToJSON = clone $this;
		unset( $courseObjToJSON->post_content );
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
		$filter->where[]    = $lp_course_json_db->wpdb->prepare( "AND ID = %d", $this->ID );
		$filter->collection = $lp_course_json_db->tb_lp_courses;
		$lp_course_json_db->delete_execute( $filter );
	}
}
