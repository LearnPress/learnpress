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
use WP_Error;
use WP_Post;
use WP_Term;

defined( 'ABSPATH' ) || exit();

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

	const STATUS_PUBLISH = 'publish';
	const STATUS_TRASH   = 'trash';

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
	 * @since 4.2.6.9
	 * @version 1.0.1
	 */
	public function get_author_model() {
		if ( ! empty( $this->post_author ) ) {
			$author_id = $this->post_author;
		} else {
			$author_id = get_post_field( 'post_author', $this );
		}

		return UserModel::find( $author_id, true );
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
	 *
	 * @return PostModel|false|static
	 * @version 1.0.1
	 */
	public static function get_item_model_from_db( LP_Post_Type_Filter $filter ) {
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
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.1
	 */
	public function save() {
		$data = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		$filter              = new LP_Post_Type_Filter();
		$filter->ID          = $this->ID;
		$filter->only_fields = [ 'ID' ];
		$post_rs             = self::get_item_model_from_db( $filter );
		// Check if exists course id.
		if ( empty( $post_rs ) ) { // Insert data.
			$post_id = wp_insert_post( $data, true );
		} else { // Update data.
			$post_id = wp_update_post( $data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message() );
		} else {
			$this->ID = $post_id;
		}

		$post = get_post( $this->ID );
		foreach ( get_object_vars( $post ) as $property => $value ) {
			$this->{$property} = $value;
		}

		$this->clean_caches();
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

	/**
	 * Get image url of post.
	 *
	 * @param string|int[] $size
	 *
	 * @return string
	 * @since 4.2.6.9
	 * @version 1.0.2
	 */
	public function get_image_url( $size = 'post-thumbnail' ): string {
		$image_url = '';

		if ( has_post_thumbnail( $this ) ) {
			if ( is_string( $size ) ) {
				$image_url = get_the_post_thumbnail_url( $this, $size );
			} elseif ( is_array( $size ) && count( $size ) === 2 ) {
				// Custom crop size for image.
				$attachment_id = get_post_thumbnail_id( $this );
				$file_path     = get_attached_file( $attachment_id );
				$resized_file  = wp_get_image_editor( $file_path );

				if ( ! is_wp_error( $resized_file ) ) {
					$resized_file->resize( $size[0], $size[1], true );
					$resized_image = $resized_file->save();

					if ( ! is_wp_error( $resized_image ) ) {
						// Build the URL for the resized image
						$upload_dir = wp_upload_dir();
						$base_dir   = $upload_dir['basedir'];
						$imag_dir   = $resized_image['path'];
						$imag_dir   = str_replace( $base_dir, '', $imag_dir );
						$image_url  = $upload_dir['baseurl'] . $imag_dir;
					}
				}
			}
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
	 * @param mixed $default_value
	 * @param bool $single
	 *
	 * @return false|mixed
	 */
	public function get_meta_value_by_key( string $key, $default_value = false, bool $single = true ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$value = get_post_meta( $this->ID, $key, $single );
		if ( empty( $value ) ) {
			$value = $default_value;
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
		$this->meta_data->{$key} = $value;
		update_post_meta( $this->ID, $key, $value );
	}

	/**
	 * Get categories of course.
	 *
	 * @return array|WP_Term[]
	 * @version 1.0.1
	 * @since 4.2.3
	 */
	public function get_categories(): array {
		// Todo: set cache.
		$wpPost     = new WP_Post( $this );
		$categories = get_the_terms( $wpPost, LP_COURSE_CATEGORY_TAX );
		if ( ! $categories || $categories instanceof WP_Error ) {
			$categories = array();
		}

		return $categories;
	}

	/**
	 * Get tags of course.
	 *
	 * @return array|WP_Term[]
	 * @version 1.0.1
	 * @since 4.2.7.2
	 */
	public function get_tags(): array {
		// Todo: set cache.
		$wpPost = new WP_Post( $this );
		$tags   = get_the_terms( $wpPost, LP_COURSE_TAXONOMY_TAG );
		if ( ! $tags || $tags instanceof WP_Error ) {
			$tags = array();
		}

		return $tags;
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
	 * Get excerpt of WP
	 *
	 * @return string
	 */
	public function get_the_excerpt(): string {
		$content = get_the_excerpt( $this );

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

	/**
	 * Get edit link of post
	 *
	 * @return string|null
	 * @since 4.2.7.4
	 * @version 1.0.1
	 */
	public function get_edit_link() {
		return admin_url( "post.php?post=$this->ID&action=edit" );
		/**
		 * Not using get_edit_post_link() because it may not return the correct link in some cases
		 * get_post_type_object is null.
		 */
		//return get_edit_post_link( $this->get_id() );
	}
}
