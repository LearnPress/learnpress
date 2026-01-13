<?php

/**
 * Class Course
 * To replace class LP_User_Item
 *
 * @package LearnPress/Classes
 * @version 1.0.8
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LearnPress;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\FilterBase;
use LearnPress\Filters\PostFilter;
use LP_Cache;
use LP_Post_Meta_DB;
use LP_Post_Meta_Filter;
use LP_Post_Type_Filter;

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
	 * Get post course by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|PostModel
	 * @version 1.0.0
	 * @since 4.3.2
	 */
	public static function find_by_id( int $post_id, bool $check_cache = false ) {
		$filter            = new PostFilter();
		$filter->ID        = $post_id;
		$filter->post_type = ( new static() )->post_type;

		$type      = ( new static() )->post_type;
		$key_cache = "postModel/find/{$post_id}/" . $type;
		$lp_cache  = new LP_Cache();

		// Check first load cache
		$postModel = LP_Cache::cache_load_first( 'get', $key_cache );
		if ( false !== $postModel ) {
			return $postModel;
		}

		// Check cache
		if ( $check_cache ) {
			$quizPostModel = $lp_cache->get_cache( $key_cache );
			if ( $quizPostModel instanceof QuizPostModel ) {
				return $quizPostModel;
			}
		}

		$postModel = self::get_item_model_from_db( $filter );
		// Set cache
		if ( $postModel instanceof PostModel ) {
			$lp_cache->set_cache( $key_cache, $postModel );
		}
		LP_Cache::cache_load_first( 'set', $key_cache, $postModel );

		return $postModel;
	}

	/**
	 * Get post from database.
	 * If not exists, return false.
	 * If exists, return PostModel.
	 *
	 * @param LP_Post_Type_Filter|FilterBase $filter
	 *
	 * @return PostModel|false|static
	 * @version 1.0.2
	 */
	public static function get_item_model_from_db( $filter ) {
		$lp_post_db = PostDB::getInstance();
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
	 * Check capabilities to create new post.
	 *
	 * @return bool
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function check_capabilities_create(): bool {
		return true;
	}

	/**
	 * Check capabilities to update post.
	 *
	 * @return bool
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function check_capabilities_update(): bool {
		return true;
	}

	/**
	 * Check capabilities of item's course.
	 * Check user current can edit it.
	 *
	 * @return void
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.2.9
	 */
	public function check_capabilities_create_item_course() {
		$course_item_types = CourseModel::item_types_support();
		// Questions not type item of course, it is type item of quiz, but need check here.
		$course_item_types[] = LP_QUESTION_CPT;
		if ( ! in_array( $this->post_type, $course_item_types, true ) ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! user_can( $user, 'edit_' . LP_LESSON_CPT . 's' ) ) {
			throw new Exception( __( 'You do not have permission to create item.', 'learnpress' ) );
		}
	}

	/**
	 * Check capabilities of item's course.
	 * Check user current can edit it.
	 *
	 * @return void
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.2.9
	 */
	public function check_capabilities_update_item_course() {
		$course_item_types = CourseModel::item_types_support();
		// Questions not type item of course, it is type item of quiz, but need check here.
		$course_item_types[] = LP_QUESTION_CPT;
		if ( ! in_array( $this->post_type, $course_item_types, true ) ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! user_can( $user, 'edit_' . LP_LESSON_CPT, $this->ID ) ) {
			throw new Exception( __( 'You do not have permission to edit this item.', 'learnpress' ) );
		}
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.3
	 */
	public function save( bool $force_save = false ) {
		$data = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		// Check if exists course id.
		if ( empty( $this->ID ) ) { // Insert data.
			// Check permission
			if ( ! $force_save ) {
				if ( ! $this->check_capabilities_create() ) {
					throw new Exception( __( 'You do not have permission to create item.', 'learnpress' ) );
				}

				$this->check_capabilities_create_item_course();
			}

			unset( $data['ID'] );
			$post_id = wp_insert_post( $data, true );
		} else { // Update data.
			// Check permission
			if ( ! $force_save ) {
				if ( ! $this->check_capabilities_update() ) {
					throw new Exception( __( 'You do not have permission to edit this item.', 'learnpress' ) );
				}

				$this->check_capabilities_update_item_course();
			}

			$post_id = wp_update_post( $data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message() );
		} else {
			$this->ID = $post_id;
		}

		$post = get_post( $this->ID );
		foreach ( get_object_vars( $post ) as $property => $value ) {
			// If property is not exists in PostModel, skip it.
			if ( ! property_exists( $this, $property ) ) {
				continue;
			}
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
	 * @version 1.0.3
	 */
	public function get_image_url( $size = 'post-thumbnail' ): string {
		$image_url = '';

		if ( has_post_thumbnail( $this ) ) {
			if ( is_string( $size ) ) {
				$image_url = get_the_post_thumbnail_url( $this, $size );
			} elseif ( is_array( $size ) && count( $size ) === 2 ) {
				// Check file crop is existing.
				$attachment_id = get_post_thumbnail_id( $this );
				$file_path     = get_attached_file( $attachment_id );
				$file_url      = wp_get_attachment_url( $attachment_id );
				$upload_dir    = wp_upload_dir();
				$base_dir      = $upload_dir['basedir'];

				// Get file path with size.
				$file_path_arr    = explode( '.', $file_path );
				$file_path_length = count( $file_path_arr );
				$extension        = end( $file_path_arr );
				unset( $file_path_arr[ $file_path_length - 1 ] );
				$file_path_join      = implode( '.', $file_path_arr );
				$file_path_with_size = $file_path_join . '-' . $size[0] . 'x' . $size[1] . '.' . $extension;
				if ( file_exists( $file_path_with_size ) ) {
					$file_url_arr    = explode( '.', $file_url );
					$file_url_length = count( $file_url_arr );
					$url_extension   = end( $file_url_arr );
					unset( $file_url_arr[ $file_url_length - 1 ] );
					$file_url_join = implode( '.', $file_url_arr );
					$image_url     = $file_url_join . '-' . $size[0] . 'x' . $size[1] . '.' . $url_extension;
				} else {
					// Custom crop size for image.
					$resized_file = wp_get_image_editor( $file_path );

					if ( ! is_wp_error( $resized_file ) ) {
						$resized_file->resize( $size[0], $size[1], true );
						$resized_image = $resized_file->save( $file_path_with_size );

						if ( ! is_wp_error( $resized_image ) ) {
							// Build the URL for the resized image
							$imag_dir  = $resized_image['path'];
							$imag_dir  = str_replace( $base_dir, '', $imag_dir );
							$image_url = $upload_dir['baseurl'] . $imag_dir;
						}
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
	 * @param bool $fore_update
	 *
	 * @return void
	 * @throws Exception
	 * @since 4.2.6.9
	 * @version 1.0.3
	 */
	public function save_meta_value_by_key( string $key, $value, bool $fore_update = false ) {
		// Check permission
		if ( ! $fore_update ) {
			if ( ! $this->check_capabilities_update() ) {
				throw new Exception( __( 'You do not have permission to edit this item.', 'learnpress' ) );
			}
			$this->check_capabilities_create_item_course();
		}

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
