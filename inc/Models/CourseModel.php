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
use LP_Helper;
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
	 * @var UserModel author model
	 */
	public $author;
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
	 * @var stdClass all meta data
	 */
	public $meta_data = null;
	/**
	 * @var string JSON Store all data a single course
	 */
	public $json = null;
	/**
	 * @var string lang of Course
	 */
	public $lang = null;
	/***** Field not on table *****/
	/**
	 * @var null|stdClass Course object from json
	 */
	public $course_from_json = null;
	public $image_url = '';

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
	 * Map array, object data to PostModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return PostModel|static
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
	 * @return stdClass|null
	 * @throws Exception
	 *
	 */
	public function get_obj_from_json() {
		if ( ! empty( $this->course_from_json ) ) {
			return $this->course_from_json;
		}

		if ( ! empty( $this->json ) ) {
			$this->course_from_json = LP_Helper::json_decode( $this->json );
		}

		return $this->course_from_json;
	}

	/**
	 * Get image url
	 * Check has data on table learnpress_courses return
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

		if ( $this->course_from_json && isset( $this->course_from_json->image_url ) ) {
			$image_url = $this->course_from_json->image_url;
		} else {
			$post      = new PostModel( $this );
			$image_url = $post->get_image_url();
		}

		$this->image_url = $image_url;

		return $image_url;
	}

	/**
	 * Get post from database.
	 * If not exists, return false.
	 * If exists, return PostModel.
	 *
	 * @param LP_Course_JSON_Filter $filter
	 * @param bool $no_cache
	 *
	 * @return CourseModel|false|static
	 */
	public static function get_item_model_from_db( LP_Course_JSON_Filter $filter, bool $no_cache = true ) {
		$lp_course_json_db = LP_Course_JSON_DB::getInstance();
		$course_model      = false;

		try {
			$course_rs = self::get_course_from_db( $filter );
			if ( $course_rs instanceof stdClass ) {
				$course_model = new static( $course_rs );
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
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		if ( ! isset( $data['ID'] ) ) {
			throw new Exception( 'Course ID is invalid!' );
		}

		$filter     = new LP_Course_JSON_Filter();
		$filter->ID = $this->ID;
		$course_rs  = self::get_course_from_db( $filter );
		// Check if exists course id.
		if ( empty( $course_rs ) ) { // Insert data.
			$lp_course_json_db->insert_data( $data );
		} else { // Update data.
			$lp_course_json_db->update_data( $data );
		}

		return $this;
	}
}
