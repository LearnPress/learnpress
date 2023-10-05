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
use LP_User_Items_Cache;
use LP_User_Items_DB;

class UserItemModel {
	/**
	 * Auto increment
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
		foreach ( get_object_vars( $this ) as $k => $v ) {
			$data[ $k ] = $v;
		}

		if ( ! isset( $data['start_time'] ) ) {
			$data['start_time'] = gmdate( 'Y-m-d H:i:s', time() );
		}

		// Check if exists user_item_id.
		if ( empty( $this->user_item_id ) ) { // Insert data.
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
