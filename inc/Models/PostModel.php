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
use LP_Post_DB;
use LP_Post_Meta_DB;
use LP_Post_Meta_Filter;
use LP_Post_Type_Filter;
use LP_User;
use LP_User_Filter;
use LP_User_Guest;

use stdClass;
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
	 * @var UserModel author model
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
	 * @var string Post title
	 */
	public $post_title = '';
	/**
	 * @var string Post excerpt
	 */
	public $post_excerpt = '';
	/**
	 * @var string Post Status (publish, draft, ...)
	 */
	public $post_status = '';
	/**
	 * @var string Post name (slug for link)
	 */
	public $post_name = '';
	/**
	 * @var string Post type
	 */
	public $post_type = 'post';
	/**
	 * @var int post parent
	 */
	public $post_parent = 0;
	/**
	 * @var stdClass all meta data
	 */
	public $meta_data = null;
	/**
	 * @var stdClass all meta data
	 */
	public $is_got_meta_data;
	/**
	 * @var string only for set same property with class WP_Post
	 */
	public $filter;

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
	 * Get user model
	 *
	 * @return false|UserModel
	 */
	public function get_author_model() {
		if ( ! empty( $this->author ) ) {
			return $this->author;
		}

		if ( empty( $this->post_author ) ) {
			$author_id = $this->post_author;
		} else {
			$author_id = get_post_field( 'post_author', $this );
		}

		$this->author = UserModel::find( $author_id, true );

		return $this->author;
	}

	/**
	 * Map array, object data to PostModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return PostModel|static
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
	 * Get post from database.
	 * If not exists, return false.
	 * If exists, return PostModel.
	 *
	 * @param LP_Course_Filter $filter
	 * @param bool $check_cache
	 *
	 * @return PostModel|false|static
	 */
	public static function get_item_model_from_db( LP_Post_Type_Filter $filter, bool $check_cache = false ) {
		$lp_post_db = LP_Post_DB::getInstance();
		$post_model = false;

		try {
			if ( empty( $filter->post_type ) ) {
				$filter->post_type = ( new static() )->post_type;
			}

			$lp_post_db->get_query_single_row( $filter );
			$query_single_row = $lp_post_db->get_posts( $filter );
			$post_rs          = $lp_post_db->wpdb->get_row( $query_single_row );

			if ( $post_rs instanceof stdClass ) {
				$post_model = new static( $post_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $post_model;
	}

	/**
	 * Get all meta_data, all keys of a user it
	 *
	 * @return stdClass|null
	 * @throws Exception
	 */
	public function get_all_metadata() {
		if ( ! isset( $this->is_got_meta_data ) ) {
			$lp_item_meta_db         = LP_Post_Meta_DB::getInstance();
			$filter                  = new LP_Post_Meta_Filter();
			$filter->post_id         = $this->get_id();
			$filter->run_query_count = false;
			$filter->limit           = - 1;

			$metadata_rs = $lp_item_meta_db->get_post_metas( $filter );
			if ( ! $metadata_rs instanceof stdClass ) {
				$this->meta_data = new stdClass();
				foreach ( $metadata_rs as $value ) {
					$this->meta_data->{$value->meta_key} = $value->meta_value;
				}
			}

			$this->is_got_meta_data = 1;
		}

		return $this->meta_data;
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return static
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function save() {
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
	 * Get meta value by key.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return false|mixed
	 */
	public function get_meta_value_by_key( string $key, $default = false ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$value = get_post_meta( $this->ID, $key, true );
		if ( empty( $value ) ) {
			$value = $default;
		}

		$this->meta_data->{$key} = $value;

		return $value;
	}

	/**
	 * Get meta value by key.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function save_meta_value_by_key( string $key, $value ) {
		update_post_meta( $this->ID, $key, $value );
	}

	/**
	 * Get categories of course.
	 *
	 * @return array|WP_Term[]
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function get_categories(): array {
		// Todo: set cache.
		$wpPost     = new WP_Post( $this );
		$categories = get_the_terms( $wpPost, LP_COURSE_CATEGORY_TAX );
		if ( ! $categories ) {
			$categories = array();
		}

		return $categories;
	}

	/**
	 * Get permalink of post
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		$permalink = get_permalink( $this );
		if ( empty( $permalink ) ) {
			$permalink = '';
		}

		return $permalink;
	}

	/**
	 * Get the content of WP
	 *
	 * @return string
	 */
	public function get_the_content(): string {
		$content = get_the_content( null, false, $this );
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		return $content;
	}

	/**
	 * Get title of course
	 *
	 * @return string
	 */
	public function get_the_title(): string {
		return get_the_title( $this );
	}
}
