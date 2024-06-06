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
use LP_User;
use LP_User_Filter;

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
	 * If exists, return CourseModel.
	 *
	 * @param LP_User_Filter $filter
	 * @param bool $no_cache
	 *
	 * @return UserModel|false|static
	 */
	public static function get_user_model_from_db( LP_User_Filter $filter, bool $no_cache = true ) {
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
}
