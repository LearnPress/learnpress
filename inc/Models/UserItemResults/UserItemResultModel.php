<?php

/**
 * Class UserItemResultModel
 *
 * Model for learnpress_user_item_results table.
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.5.0
 */

namespace LearnPress\Models\UserItemResults;

use Exception;
use LearnPress\Databases\UserItemResultsDB;
use LearnPress\Filters\UserItemResultsFilter;
use LearnPress\Models\UserItems\UserItemModel;
use LP_Cache;
use LP_Debug;
use LP_Helper;
use stdClass;
use Throwable;

class UserItemResultModel extends UserItemModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	private $id = 0;

	/**
	 * User item id, foreign key
	 *
	 * @var int
	 */
	public $user_item_id = 0;

	/**
	 * Key to identify users not logged in
	 *
	 * @var string
	 */
	public $guest_key = '';

	/**
	 * Store result of user item type JSON
	 *
	 * @var string|null
	 */
	public $result = null;

	/**
	 * Store more data type JSON
	 *
	 * @var string|null
	 */
	public $extra_data = null;

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
	 * Get id
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Set id
	 *
	 * @param int $id
	 */
	private function set_id( int $id ) {
		$this->id = $id;
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
	 * Get user id
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return $this->user_id;
	}

	/**
	 * Get guest key
	 *
	 * @return string
	 */
	public function get_guest_key(): string {
		return $this->guest_key;
	}

	/**
	 * Get item id
	 *
	 * @return int
	 */
	public function get_item_id(): int {
		return $this->item_id;
	}

	/**
	 * Get item type
	 *
	 * @return string
	 */
	public function get_item_type(): string {
		return $this->item_type;
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
	 * Get ref id
	 *
	 * @return int
	 */
	public function get_ref_id(): int {
		return $this->ref_id;
	}

	/**
	 * Get ref type
	 *
	 * @return string
	 */
	public function get_ref_type(): string {
		return $this->ref_type;
	}

	/**
	 * Get parent id
	 *
	 * @return int
	 */
	public function get_parent_id(): int {
		return $this->parent_id;
	}

	/**
	 * Get result as decoded data
	 *
	 * @return mixed
	 */
	public function get_result() {
		return $this->decode_json_field( $this->result );
	}

	/**
	 * Decoded `extra_data` blob. Always an array, even for legacy rows that
	 * stored NULL or invalid JSON.
	 *
	 * @return array
	 */
	public function get_extra_data(): array {
		try {
			if ( empty( $this->extra_data ) ) {
				return array();
			}

			return LP_Helper::json_decode( $this->extra_data, true );
		} catch ( Throwable $e ) {
			return array();
		}
	}

	/**
	 * Write one key into `extra_data`, preserving the others. Does not save().
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function set_extra_data( string $key, $value ) {
		$data         = $this->get_extra_data();
		$data[ $key ] = $value;

		$this->extra_data = (string) wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Map array, object data to UserItemResultModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return UserItemResultModel
	 */
	public function map_to_object( $data ): UserItemResultModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get user item result from database by filter.
	 * If not exists, return false.
	 * If exists, return UserItemResultModel.
	 *
	 * @param UserItemResultsFilter $filter
	 *
	 * @return UserItemResultModel|false|static
	 */
	public static function get_user_item_result_model_from_db( UserItemResultsFilter $filter ) {
		$db                     = UserItemResultsDB::getInstance();
		$user_item_result_model = false;

		try {
			// Set order by id DESC to get the latest user item result.
			$filter->order    = $filter::ORDER_DESC;
			$filter->order_by = $filter::COL_ID;

			$db->get_query_single_row( $filter );
			$query_single_row = $db->get_user_item_results( $filter );
			$user_item_result = $db->wpdb->get_row( $query_single_row );
			if ( $user_item_result instanceof stdClass ) {
				$user_item_result_model = new static( $user_item_result );
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $user_item_result_model;
	}

	/**
	 * Find user item result by id.
	 *
	 * @param int $id
	 *
	 * @return UserItemResultModel|false|static
	 */
	public static function find( int $id ) {
		$filter           = new UserItemResultsFilter();
		$filter->id       = $id;
		$filter->order_by = UserItemResultsFilter::COL_ID;
		$filter->order    = UserItemResultsFilter::ORDER_DESC;

		return static::get_user_item_result_model_from_db( $filter );
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
		$key_cache         = "userItemResultModel/find/{$user_id}/{$item_id}/{$item_type}";
		$filter            = new UserItemResultsFilter();
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
		$lpUserItemCache = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$userItemModel = $lpUserItemCache->get_cache( $key_cache );
			if ( $userItemModel instanceof UserItemModel ) {
				return new static( $userItemModel );
			}
		}

		$userItemModel = static::get_user_item_result_model_from_db( $filter );
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
	 * Update data to database.
	 *
	 * If id is empty, insert new data, else update data.
	 *
	 * @return UserItemResultModel
	 * @throws Exception
	 */
	public function save(): UserItemResultModel {
		$db     = UserItemResultsDB::getInstance();
		$id_new = 0;
		$data   = get_object_vars( $this );

		// Encode JSON fields if needed.
		foreach ( [ 'result', 'extra_data' ] as $json_field ) {
			if ( isset( $data[ $json_field ] ) && ( is_array( $data[ $json_field ] ) || is_object( $data[ $json_field ] ) ) ) {
				$data[ $json_field ] = json_encode( $data[ $json_field ], JSON_UNESCAPED_UNICODE );
			}
		}

		$args = [
			'data'       => $data,
			'filter'     => new UserItemResultsFilter(),
			'table_name' => $db->tb_lp_question_answers,
			'key_auto_increment' => UserItemResultsFilter::COL_ID, // For insert
			'where_key' => UserItemResultsFilter::COL_ID, // For update
		];

		// Check if exists id.
		if ( empty( $this->get_id() ) ) { // Insert data.
			if ( empty( $data['user_item_id'] ) ) {
				throw new Exception( 'User Item ID is require.' );
			}
			if ( empty( $data['item_id'] ) ) {
				throw new Exception( 'Item ID is require.' );
			}
			if ( empty( $data['item_type'] ) ) {
				throw new Exception( 'Item Type is require.' );
			}
			if ( empty( $data['user_id'] ) && empty( $data['guest_key'] ) ) {
				throw new Exception( 'User ID or Guest Key is require.' );
			}

			$id_new = $db->insert_data( $args );
			if ( empty( $id_new ) ) {
				throw new Exception( 'Cannot insert data to database.' );
			}
		} else { // Update data.
			$db->update_data( $args );
		}

		if ( $id_new ) {
			$this->set_id( $id_new );
		}

		$this->clean_caches();

		return $this;
	}

	/**
	 * Delete user item result.
	 *
	 * @throws Exception
	 */
	public function delete() {
		$db                 = UserItemResultsDB::getInstance();
		$filter             = new UserItemResultsFilter();
		$filter->collection = $db->tb_lp_user_item_results;
		$filter->where[]    = $db->wpdb->prepare( 'AND id = %d', $this->get_id() );
		$db->delete_execute( $filter );

		$this->clean_caches();
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 */
	public function clean_caches() {
		// Caching can be added here later if needed.
	}
}
