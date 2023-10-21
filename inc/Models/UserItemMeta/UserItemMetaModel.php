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
use LP_User_Item_Meta_Filter;
use stdClass;
use Throwable;

class UserItemMetaModel {
	/**
	 * Auto increment
	 *
	 * @var int
	 */
	private $meta_id = 0;
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
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get meta id
	 *
	 * @return int
	 */
	public function get_meta_id(): int {
		return $this->meta_id;
	}

	/**
	 * Set meta id
	 *
	 * @param int $meta_id
	 */
	public function set_meta_id( int $meta_id ) {
		$this->meta_id = $meta_id;
	}

	/**
	 * Get all data, all keys of a user item
	 *
	 * @throws Exception
	 * @return stdClass|false
	 */
	public static function get_all_data( $user_item_id ) {
		$lp_user_item_meta_db            = LP_User_Item_Meta_DB::getInstance();
		$filter                          = new LP_User_Item_Meta_Filter();
		$filter->learnpress_user_item_id = $user_item_id;
		$filter->run_query_count         = false;
		$user_itemmeta_rs                = $lp_user_item_meta_db->get_user_item_metas( $filter );
		$all_data                        = false;
		if ( $user_itemmeta_rs instanceof stdClass ) {
			$all_data = new stdClass();
			foreach ( $user_itemmeta_rs as $value ) {
				$all_data->{$value->meta_key} = new static( $value );
			}
		}

		return $all_data;
	}

	/**
	 * Get user item from database by le, item_id, item_type.
	 * If not exists, return false.
	 * If exists, return UserItemModel.
	 *
	 * @param LP_User_Item_Meta_Filter $filter
	 * @param bool $no_cache
	 * @return UserItemMetaModel|false
	 */
	public static function get_user_item_meta_model_from_db( LP_User_Item_Meta_Filter $filter, bool $no_cache = true ) {
		$lp_user_item_meta_db = LP_User_Item_Meta_DB::getInstance();
		$user_item_meta_model = false;

		try {
			$lp_user_item_meta_db->get_query_single_row( $filter );
			$query_single_row = $lp_user_item_meta_db->get_user_item_metas( $filter );
			$user_item_rs     = $lp_user_item_meta_db->wpdb->get_row( $query_single_row );
			if ( $user_item_rs instanceof stdClass ) {
				$user_item_meta_model = new static( $user_item_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $user_item_meta_model;
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
		if ( empty( $this->get_meta_id() ) ) { // Insert data.
			$meta_id_new = $lp_user_item_meta_db->insert_data( $data );
			if ( empty( $meta_id_new ) ) {
				throw new Exception( __METHOD__ . ': ' . 'Cannot insert data to database.' );
			}
		} else { // Update data.
			$lp_user_item_meta_db->update_data( $data );
		}

		if ( ! empty( $meta_id_new ) ) {
			$this->set_meta_id( $meta_id_new );
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
