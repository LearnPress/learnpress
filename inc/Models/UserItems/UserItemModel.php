<?php

/**
 * Class UserItemModel
 * To replace class LP_User_Item
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItems;

use Exception;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserItemMeta\UserItemMetaModel;
use LP_Datetime;
use LP_User;
use LP_User_Guest;
use LP_User_Item_Meta_Filter;
use LP_User_Items_Cache;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use stdClass;
use Throwable;

class UserItemModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	private $user_item_id = 0;
	/**
	 * @var string User ID, foreign key
	 */
	public $user_id = 0;
	/**
	 * Item id (course, lesson, quiz ...)
	 *
	 * @var string foreign key
	 */
	public $item_id = 0;
	/**
	 * @var string Time start item
	 */
	public $start_time = null;
	/**
	 * @var string Time finish item
	 */
	public $end_time = null;
	/**
	 * Item type (course, lesson, quiz ...)
	 *
	 * @var string Item type
	 */
	public $item_type = '';
	/**
	 * Item status (completed, enrolled, finished ...)
	 *
	 * @var string
	 */
	public $status = '';
	/**
	 * Item graduation
	 *
	 * @var string (passed, failed, in-progress...)
	 */
	public $graduation = '';
	/**
	 * Ref id (Order, course ...)
	 *
	 * @var int Reference id
	 */
	public $ref_id = 0;
	/**
	 * Ref type (Order, course ...)
	 *
	 * @var string
	 */
	public $ref_type = '';
	/**
	 * Parent id
	 *
	 * @var int
	 */
	public $parent_id = 0;
	/**
	 * @var null|PostModel|CoursePostModel
	 */
	public $item;
	/**
	 * @var LP_User|null
	 */
	public $user;
	/**
	 * List UserItemMetaModel
	 * object {meta_key: {meta_id, learnpress_user_item_id, meta_key, meta_value, extra_value}}
	 *
	 * @var {meta_key: UserItemMetaModel}
	 */
	public $meta_data;

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
			$this->get_user_model();
		}
	}

	/**
	 * Get user item id
	 *
	 * @return int
	 */
	public function get_user_item_id(): int {
		return $this->user_item_id;
	}

	/**
	 * Set user item id
	 *
	 * @param int $user_item_id
	 */
	private function set_user_item_id( int $user_item_id ) {
		$this->user_item_id = $user_item_id;
	}

	/**
	 * Get user model
	 *
	 * @return false|LP_User|LP_User_Guest
	 */
	public function get_user_model() {
		if ( empty( $this->user ) ) {
			$this->user = learn_press_get_user( $this->user_id );
		}

		return $this->user;
	}

	/**
	 * Map array, object data to UserItemModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return UserItemModel
	 */
	public function map_to_object( $data ): UserItemModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get user item from database by user_id, item_id, item_type.
	 * If not exists, return false.
	 * If exists, return UserItemModel.
	 *
	 * @param LP_User_Items_Filter $filter
	 * @param bool $no_cache
	 *
	 * @return UserItemModel|false|static
	 */
	public static function get_user_item_model_from_db( LP_User_Items_Filter $filter, bool $check_cache = false ) {
		$lp_user_item_db = LP_User_Items_DB::getInstance();
		$user_item_model = false;

		try {
			$filter->order    = $filter::ORDER_DESC;
			$filter->order_by = $filter::COL_USER_ITEM_ID;
			if ( empty( $filter->item_type ) ) {
				$filter->item_type = ( new static() )->item_type;
			}
			$lp_user_item_db->get_query_single_row( $filter );
			$query_single_row = $lp_user_item_db->get_user_items( $filter );
			$user_item_rs     = $lp_user_item_db->wpdb->get_row( $query_single_row );
			if ( $user_item_rs instanceof stdClass ) {
				$user_item_model       = new static( $user_item_rs );
				$user_item_model->user = $user_item_model->get_user_model();
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $user_item_model;
	}

	/**
	 * Get user item metadata from object meta_data or database by user_item_id, meta_key.
	 *
	 * @param string $key
	 *
	 * @return false|UserItemMetaModel
	 */
	public function get_meta_model_from_key( string $key ) {
		$user_item_metadata = false;

		// Check object meta_data has value of key.
		if ( $this->meta_data instanceof stdClass
		     && property_exists( $this->meta_data, $key ) ) {
			$user_item_metadata = $this->meta_data->{$key};
		} else { // Get from DB
			$filter                          = new LP_User_Item_Meta_Filter();
			$filter->meta_key                = $key;
			$filter->learnpress_user_item_id = $this->get_user_item_id();
			$user_item_metadata              = UserItemMetaModel::get_user_item_meta_model_from_db( $filter );
		}

		return $user_item_metadata;
	}

	/**
	 * Get metadata from key
	 *
	 * @param string $key
	 * @param bool $get_extra
	 *
	 * @return false|string
	 */
	public function get_meta_value_from_key( string $key, bool $get_extra = false ) {
		$data = false;

		$user_item_metadata = $this->get_meta_model_from_key( $key );
		if ( $user_item_metadata instanceof UserItemMetaModel ) {
			if ( ! $this->meta_data instanceof stdClass ) {
				$this->meta_data = new stdClass();
			}
			$this->meta_data->{$key} = $user_item_metadata;

			if ( $get_extra ) {
				$data = $user_item_metadata->extra_value;
			} else {
				$data = $user_item_metadata->meta_value;
			}
		}

		return $data;
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return UserItemModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function save(): UserItemModel {
		$lp_user_item_db  = LP_User_Items_DB::getInstance();
		$user_item_id_new = 0;
		$data             = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		if ( ! isset( $data['start_time'] ) ) {
			$data['start_time'] = gmdate( 'Y-m-d H:i:s', time() );
		}

		// Check if exists user_item_id.
		if ( empty( $this->get_user_item_id() ) ) { // Insert data.
			if ( empty( $data['user_id'] ) ) {
				throw new Exception( 'User ID is require.' );
			}
			if ( empty( $data['item_id'] ) ) {
				throw new Exception( 'Item ID is require.' );
			}
			if ( empty( $data['item_type'] ) ) {
				throw new Exception( 'Item Type is require.' );
			}

			$user_item_id_new = $lp_user_item_db->insert_data( $data );
			if ( empty( $user_item_id_new ) ) {
				throw new Exception( 'Cannot insert data to database.' );
			}
		} else { // Update data.
			$lp_user_item_db->update_data( $data );
		}

		if ( $user_item_id_new ) {
			$this->set_user_item_id( $user_item_id_new );
		}

		$this->clean_caches();

		return $this;
	}

	/**
	 * Get total timestamp complete, done item.
	 *
	 * @return int
	 */
	public function get_total_timestamp_completed(): int {
		$time_interval = 0;

		if ( empty( $this->start_time ) || empty( $this->end_time ) ) {
			return $time_interval;
		}

		$start = new LP_Datetime( $this->start_time );
		$end   = new LP_Datetime( $this->end_time );

		return $end->getTimestamp() - $start->getTimestamp();
	}

	/**
	 * Get expiration time.
	 *
	 * @return null|LP_Datetime $time
	 * @since 3.3.0
	 * @version 3.3.3
	 */
	public function get_expiration_time() {
		$duration = get_post_meta( $this->item_id, '_lp_duration', true );

		if ( ! absint( $duration ) || empty( $this->start_time ) ) {
			$expire = null;
		} else {
			$start      = new LP_Datetime( $this->start_time );
			$start_time = $start->getTimestamp();
			// Convert duration from string to seconds.
			if ( ! is_numeric( $duration ) ) {
				$duration = strtotime( $duration ) - time();
			}

			$expire_time = $start_time + $duration;
			$expire      = new LP_Datetime( $expire_time );
		}

		return apply_filters( 'learnPress/user-item/expiration-time', $expire, $duration, $this );
	}

	public function delete() {

	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 */
	public function clean_caches() {
		// Clear cache user item.
		$lp_user_items_cache = new LP_User_Items_Cache( true );
		$lp_user_items_cache->clean_user_item(
			[
				$this->user_id,
				$this->item_id,
				$this->item_type,
			]
		);
	}
}
