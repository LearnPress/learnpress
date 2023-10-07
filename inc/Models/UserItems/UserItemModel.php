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
use LP_User;
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
	public $user_item_id = 0;
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
	 * @var LP_User|null
	 */
	public $user;

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
	 * @param  array|object|mixed $data
	 * @return UserItemModel
	 */
	public function map_to_object( $data ): UserItemModel {
		foreach ( $data as $key => $value ) {
			if ( isset( $this->{$key} ) ) {
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
	 * @return UserItemModel|false
	 */
	public static function get_user_item_model_from_db( LP_User_Items_Filter $filter, bool $no_cache = false ) {
		$lp_user_item_db = LP_User_Items_DB::getInstance();
		$user_item_model = false;

		try {
			$filter->order    = $filter::ORDER_DESC;
			$filter->order_by = $filter::COL_USER_ITEM_ID;
			$lp_user_item_db->get_query_single_row( $filter );
			$query_single_row = $lp_user_item_db->get_user_items( $filter );
			$user_item_rs     = $lp_user_item_db->wpdb->get_row( $query_single_row );
			if ( $user_item_rs instanceof stdClass ) {
				$user_item_model       = new self( $user_item_rs );
				$user_item_model->user = learn_press_get_user( $user_item_model->user_id );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $user_item_model;
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
		if ( empty( $this->user_item_id ) ) { // Insert data.
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
			$this->user_item_id = $user_item_id_new;
		}

		$this->clean_caches();

		return $this;
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
