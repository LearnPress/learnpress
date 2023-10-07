<?php

/**
 * Class UserItemModel
 * To replace class LP_User_Item
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItemMeta;

use Exception;
use LP_User_Item_Meta_DB;

class UserItemMetaModel {
	/**
	 * Auto increment
	 *
	 * @var int
	 */
	public $meta_id = 0;
	/**
	 * @var string User ID, foreign key
	 */
	public $learnpress_user_item_id = 0;
	/**
	 * @var string meta key
	 */
	public $meta_key = 0;
	/**
	 * @var string meta value
	 */
	public $meta_value = '';
	/**
	 * @var string Extra value
	 */
	public $extra_value = '';

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
	 * @return UserItemMetaModel
	 */
	public function map_to_object( $data ): UserItemMetaModel {
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
	 * @return UserItemMetaModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function save(): UserItemMetaModel {
		$lp_user_item_meta_db = LP_User_Item_Meta_DB::getInstance();
		$data                 = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		// Check if exists user_item_id.
		if ( empty( $this->meta_id ) ) { // Insert data.
			$meta_id_new = $lp_user_item_meta_db->insert_data( $data );
			if ( empty( $user_item_id_new ) ) {
				throw new Exception( __METHOD__ . ': ' . 'Cannot insert data to database.' );
			}
		} else { // Update data.
			$lp_user_item_meta_db->update_data( $data );
		}

		if ( ! empty( $meta_id_new ) ) {
			$this->meta_id = $meta_id_new;
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

	}
}
