<?php

/**
 * Class UserModel
 *
 * @version 1.0.0
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LP_Course_DB;
use LP_Profile;
use LP_User;
use LP_User_DB;
use LP_User_Filter;

use stdClass;
use Throwable;

class UserModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	public $ID = 0;
	/**
	 * @var string author id, foreign key
	 */
	public $user_login = 0;
	/**
	 * @var LP_User author model
	 */
	public $user_nicename;
	/**
	 * @var string post date
	 */
	public $user_email = null;
	/**
	 * @var string post date gmt
	 */
	public $user_url = null;
	/**
	 * @var string post content
	 */
	public $user_register = '';
	/**
	 * Item type (course, lesson, quiz ...)
	 *
	 * @var string Item type
	 */
	public $display_name = '';
	/**
	 * @var stdClass all meta data
	 */
	public $meta_data = null;
	/**
	 * @var string image url
	 */
	public $image_url = '';

	const META_KEY_IMAGE = '_lp_profile_picture';

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
	 * Map array, object data to UserItemModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return UserModel
	 */
	public function map_to_object( $data ): UserModel {
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
	 * If exists, return CoursePostModel.
	 *
	 * @param LP_User_Filter $filter
	 * @param bool $no_cache
	 *
	 * @return UserModel|false|static
	 */
	public static function get_user_model_from_db( LP_User_Filter $filter, bool $no_cache = true ) {
		$lp_user_db = LP_User_DB::instance();
		$user_model = false;

		try {
			$filter->only_fields = [ 'ID', 'user_nicename', 'user_email', 'display_name' ];
			$lp_user_db->get_query_single_row( $filter );
			$query_single_row = $lp_user_db->get_users( $filter );
			$user_rs          = $lp_user_db->wpdb->get_row( $query_single_row );
			if ( $user_rs instanceof stdClass ) {
				$user_model = new UserModel( $user_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $user_model;
	}

	/**
	 * Get all meta_data, all keys of a user it
	 *
	 * @return stdClass|null
	 * @throws Exception
	 */
	public function get_all_metadata() {

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

		$value = get_user_meta( $this->ID, $key, true );
		if ( empty( $value ) ) {
			$value = $default;
		}

		$this->meta_data->{$key} = $value;

		return $value;
	}

	/**
	 * Get upload profile src.
	 *
	 * @return string
	 */
	public function get_image_url(): string {
		if ( ! empty( $this->image_url ) ) {
			return $this->image_url;
		}

		$profile_picture = $this->get_meta_value_by_key( self::META_KEY_IMAGE, '' );
		if ( ! empty( $profile_picture ) ) {
			// Check if hase slug / at the beginning of the path, if not add it.
			$slash           = substr( $profile_picture, 0, 1 ) === '/' ? '' : '/';
			$profile_picture = $slash . $profile_picture;
			// End check.
			$upload    = learn_press_user_profile_picture_upload_dir();
			$file_path = $upload['basedir'] . $profile_picture;

			if ( file_exists( $file_path ) ) {
				$this->image_url = $upload['baseurl'] . $profile_picture;
			}
		}

		return $this->image_url;
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return UserModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function save(): UserModel {
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

	/**
	 * Get description of user.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return wpautop( $this->get_meta_value_by_key( 'description', '' ) );
	}
}
