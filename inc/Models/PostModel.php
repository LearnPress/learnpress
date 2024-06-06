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

class PostModel {
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
	 * @return PostModel
	 */
	public function map_to_object( $data ): PostModel {
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
	 * If exists, return PostModel.
	 *
	 * @param LP_Course_Filter $filter
	 * @param bool $no_cache
	 *
	 * @return PostModel|false|static
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
	 * @return PostModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function save(): self {
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

	public function get_meta_value_by_key( $key, $default = false ) {
		$value = get_post_meta( $this->ID, $key, true );
		if ( empty( $value ) ) {
			$value = $default;
		}

		return $value;
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
