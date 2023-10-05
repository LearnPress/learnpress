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
	public $meta_value = null;
	/**
	 * @var string Extra value JSON
	 */
	public $extra_value = null;

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
		//Todo: write code to save/update data to database.
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
