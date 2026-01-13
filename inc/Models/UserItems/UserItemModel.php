<?php

/**
 * Class UserItemModel
 * To replace class LP_User_Item
 *
 * @package LearnPress/Classes
 * @version 1.0.3
 * @since 4.2.5
 */

namespace LearnPress\Models\UserItems;

use Exception;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserItemMeta\UserItemMetaModel;
use LearnPress\Models\UserModel;
use LP_Datetime;
use LP_User_Item_Meta_DB;
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
	public $graduation = null;
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
	 * List UserItemMetaModel
	 * object {meta_key: {meta_id, learnpress_user_item_id, meta_key, meta_value, extra_value}}
	 *
	 * @var {meta_key: UserItemMetaModel}
	 */
	public $meta_data;

	// Constants
	const STATUS_COMPLETED       = 'completed';
	const STATUS_FINISHED        = 'finished';
	const STATUS_ENROLLED        = 'enrolled';
	const STATUS_PURCHASED       = 'purchased';
	const STATUS_CANCEL          = 'cancel';
	const GRADUATION_IN_PROGRESS = 'in-progress';
	const GRADUATION_PASSED      = 'passed';
	const GRADUATION_FAILED      = 'failed';

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

		if ( ! $this->meta_data instanceof stdClass ) {
			$this->meta_data = new stdClass();
		}
	}

	/**
	 * Get start time
	 *
	 * @return string
	 */
	public function get_start_time(): string {
		return is_null( $this->start_time ) ? '' : $this->start_time;
	}

	/**
	 * Get end time
	 *
	 * @return string
	 */
	public function get_end_time(): string {
		return is_null( $this->end_time ) ? '' : $this->end_time;
	}

	/**
	 * Get status
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get graduation
	 *
	 * @return string
	 */
	public function get_graduation(): string {
		return is_null( $this->graduation ) ? '' : $this->graduation;
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
	 * @return false|UserModel
	 * @since 4.2.6
	 * @version 1.0.1
	 */
	public function get_user_model() {
		return UserModel::find( $this->user_id, true );
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
	 * @param LP_User_Items_Filter|UserItemsFilter $filter
	 *
	 * @return UserItemModel|false|static
	 * @since 4.2.5
	 * @version 1.0.2
	 */
	public static function get_user_item_model_from_db( $filter ) {
		$lp_user_item_db = LP_User_Items_DB::getInstance();
		$user_item_model = false;

		try {
			// Set order by user_item_id DESC to get the latest user item.
			$filter->order    = $filter::ORDER_DESC;
			$filter->order_by = $filter::COL_USER_ITEM_ID;
			if ( empty( $filter->item_type ) ) {
				$filter->item_type = ( new static() )->item_type;
			}

			$lp_user_item_db->get_query_single_row( $filter );
			$query_single_row = $lp_user_item_db->get_user_items( $filter );
			$user_item_rs     = $lp_user_item_db->wpdb->get_row( $query_single_row );
			if ( $user_item_rs instanceof stdClass ) {
				$user_item_model = new static( $user_item_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $user_item_model;
	}

	/**
	 * Find User Item by user_id, item_id, item_type.
	 *
	 * @param int $user_id
	 * @param int $item_id
	 * @param string $item_type
	 * @param int $ref_id
	 * @param string $ref_type
	 * @param bool $check_cache
	 *
	 * @return false|UserItemModel|static
	 * @since 4.2.7.3
	 * @version 1.0.2
	 */
	public static function find_user_item(
		int $user_id,
		int $item_id,
		string $item_type,
		int $ref_id = 0,
		string $ref_type = '',
		bool $check_cache = false
	) {
		$key_cache         = "userItemModel/find/{$user_id}/{$item_id}/{$item_type}";
		$filter            = new LP_User_Items_Filter();
		$filter->user_id   = $user_id;
		$filter->item_id   = $item_id;
		$filter->item_type = $item_type;
		if ( ! empty( $ref_id ) ) {
			$filter->ref_id = $ref_id;
			$key_cache     .= "/{$ref_id}";
		}
		if ( ! empty( $ref_type ) ) {
			$filter->ref_type = $ref_type;
			$key_cache       .= "/{$ref_type}";
		}
		$lpUserItemCache = new LP_User_Items_Cache();

		// Check cache
		if ( $check_cache ) {
			$userItemModel = $lpUserItemCache->get_cache( $key_cache );
			if ( $userItemModel instanceof UserItemModel ) {
				return new static( $userItemModel );
			}
		}

		$userItemModel = static::get_user_item_model_from_db( $filter );
		// Set cache
		if ( $userItemModel instanceof UserItemModel ) {
			if ( ! $userItemModel->meta_data instanceof stdClass ) {
				$userItemModel->meta_data = new stdClass();
			}

			$lpUserItemCache->set_cache( $key_cache, $userItemModel );
		}

		return $userItemModel;
	}

	/**
	 * Get user item metadata from object meta_data or database by user_item_id, meta_key.
	 *
	 * @param string $key
	 *
	 * @return false|UserItemMetaModel
	 * @since 4.2.5
	 * @version 1.0.1
	 */
	public function get_meta_model_from_key( string $key ) {
		$filter                          = new LP_User_Item_Meta_Filter();
		$filter->meta_key                = $key;
		$filter->learnpress_user_item_id = $this->get_user_item_id();

		return UserItemMetaModel::get_user_item_meta_model_from_db( $filter );
	}

	/**
	 * Get metadata from key
	 *
	 * @param string $key
	 * @param mixed $default_value
	 * @param bool $get_extra
	 *
	 * @return false|string
	 * @since 4.2.5
	 * @version 1.0.3
	 */
	public function get_meta_value_from_key( string $key, $default_value = false, bool $get_extra = false ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return maybe_unserialize( $this->meta_data->{$key} );
		}

		$user_item_metadata = $this->get_meta_model_from_key( $key );
		if ( $user_item_metadata instanceof UserItemMetaModel ) {
			if ( ! $this->meta_data instanceof stdClass ) {
				$this->meta_data = new stdClass();
			}

			if ( $get_extra ) {
				$data = $user_item_metadata->extra_value;
			} else {
				$data = $user_item_metadata->meta_value;
			}

			$this->meta_data->{$key} = maybe_unserialize( $data );
		} else {
			$data = $default_value;
		}

		return $data;
	}

	/**
	 * Update meta value for key.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param bool $is_extra
	 *
	 * @return void
	 * @since 4.2.7.4
	 * @version 1.0.0
	 */
	public function set_meta_value_for_key( string $key, $value, bool $is_extra = false ) {
		if ( ! $this->meta_data instanceof stdClass ) {
			$this->meta_data = new stdClass();
		}

		$this->meta_data->{$key} = $value;
		$lp_db                   = LP_User_Items_DB::getInstance();
		if ( $is_extra ) {
			if ( is_object( $value ) || is_array( $value ) ) {
				$value = json_encode( $value );
			}
			$lp_db->update_extra_value( $this->get_user_item_id(), $key, $value );
		} else {
			learn_press_update_user_item_meta( $this->get_user_item_id(), $key, $value );
		}

		//$this->clean_caches();
	}

	/**
	 * Delete meta from key.
	 *
	 * @param string $key
	 *
	 * @return void
	 * @since 4.2.7.4
	 * @version 1.0.0
	 */
	public function delete_meta( string $key ) {
		$user_item_metadata = $this->get_meta_model_from_key( $key );
		if ( $user_item_metadata instanceof UserItemMetaModel ) {
			$user_item_metadata->delete();
			$this->meta_data->{$key} = null;
			$this->clean_caches();
		}
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return UserItemModel
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.3
	 */
	public function save(): UserItemModel {
		$lp_user_item_db  = LP_User_Items_DB::getInstance();
		$user_item_id_new = 0;
		$data             = get_object_vars( $this );

		if ( ! isset( $data['start_time'] ) ) {
			$data['start_time'] = gmdate( 'Y-m-d H:i:s', time() );
			$this->start_time   = $data['start_time'];
		}

		// Check if exists user_item_id.
		if ( empty( $this->get_user_item_id() ) ) { // Insert data.
			if ( empty( $data['item_id'] ) ) {
				throw new Exception( 'Item ID is require.' );
			}
			if ( empty( $data['item_type'] ) ) {
				throw new Exception( 'Item Type is require.' );
			}

			// Guest can buy course if enable guest checkout.
			if ( empty( $data['user_id'] ) && LP_COURSE_CPT !== $data['item_type'] ) {
				throw new Exception( 'User ID is require.' );
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

		// Clear caches.
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

		if ( empty( $this->get_start_time() ) || empty( $this->get_end_time() ) ) {
			return $time_interval;
		}

		$start = new LP_Datetime( $this->get_start_time() );
		$end   = new LP_Datetime( $this->get_end_time() );

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

	/**
	 * Get translate value.
	 *
	 * @param string $value
	 *
	 * @return string
	 * @since 4.2.7.8
	 * @version 1.0.0
	 */
	public function get_string_i18n( string $value ): string {
		switch ( $value ) {
			case self::STATUS_COMPLETED:
				$value = __( 'Completed', 'learnpress' );
				break;
			case self::STATUS_FINISHED:
				$value = __( 'Finished', 'learnpress' );
				break;
			case self::STATUS_ENROLLED:
				$value = __( 'Enrolled', 'learnpress' );
				break;
			case self::STATUS_CANCEL:
				$value = __( 'Cancel', 'learnpress' );
				break;
			case self::GRADUATION_IN_PROGRESS:
				$value = __( 'In Progress', 'learnpress' );
				break;
			case self::GRADUATION_PASSED:
				$value = __( 'Passed', 'learnpress' );
				break;
			case self::GRADUATION_FAILED:
				$value = __( 'Failed', 'learnpress' );
				break;
		}

		return $value;
	}

	/**
	 * Delete user item.
	 *
	 * @throws Exception
	 * @since 4.2.7.3
	 * @version 1.0.2
	 */
	public function delete() {
		// Delete meta data of user item.
		$lp_user_item_meta_db = LP_User_Item_Meta_DB::getInstance();
		$filter               = new LP_User_Item_Meta_Filter();
		$filter->where[]      = $lp_user_item_meta_db->wpdb->prepare( 'AND learnpress_user_item_id = %d', $this->get_user_item_id() );
		$filter->collection   = $lp_user_item_meta_db->tb_lp_user_itemmeta;
		$lp_user_item_meta_db->delete_execute( $filter );
		$this->meta_data = null;

		// Delete user item relationships.
		$lp_user_item_db    = LP_User_Items_DB::getInstance();
		$filter             = new LP_User_Items_Filter();
		$filter->where[]    = $lp_user_item_db->wpdb->prepare( 'AND parent_id = %d', $this->get_user_item_id() );
		$filter->collection = $lp_user_item_db->tb_lp_user_items;
		$lp_user_item_db->delete_execute( $filter );

		// Delete user item.
		$lp_user_item_db    = LP_User_Items_DB::getInstance();
		$filter             = new LP_User_Items_Filter();
		$filter->where[]    = $lp_user_item_db->wpdb->prepare( 'AND user_item_id = %d', $this->get_user_item_id() );
		$filter->collection = $lp_user_item_db->tb_lp_user_items;
		$lp_user_item_db->delete_execute( $filter );

		$this->clean_caches();
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 */
	public function clean_caches() {
		// Clear cache user item.
		$lp_user_items_cache = new LP_User_Items_Cache();
		$lp_user_items_cache->clean_user_item(
			[
				$this->user_id,
				$this->item_id,
				$this->item_type,
			]
		);

		$key_cache_user_item = "userItemModel/find/{$this->user_id}/{$this->item_id}/{$this->item_type}";
		$lp_user_items_cache->clear( $key_cache_user_item );

		$key_cache_user_item_ref = "userItemModel/find/{$this->user_id}/{$this->item_id}/{$this->item_type}/{$this->ref_id}/{$this->ref_type}";
		$lp_user_items_cache->clear( $key_cache_user_item_ref );
	}
}
