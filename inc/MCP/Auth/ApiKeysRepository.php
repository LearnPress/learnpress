<?php

namespace LearnPress\MCP\Auth;

use LP_Database;
use LP_Filter;
use LP_Helper;
use LearnPress\Databases\UserDB;
use LearnPress\Filters\UserFilter;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Handles persistence and credential lifecycle for LearnPress MCP API keys.
 */
class ApiKeysRepository {
	/**
	 * Allowed key permission values.
	 */
	public const PERMISSIONS = array( 'read', 'write', 'read_write' );

	/**
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $users_table;

	/**
	 * Initialize DB handles for MCP API key storage.
	 *
	 * @return void
	 */
	public function __construct() {
		global $wpdb;

		$this->wpdb        = $wpdb;
		$this->table       = LP_Database::getInstance()->tb_lp_mcp_api_keys;
		$this->users_table = $wpdb->users;
	}

	/**
	 * Create a new API key and return plaintext credentials once.
	 *
	 * @param int $user_id User ID that owns the key.
	 * @param string $description Optional key description.
	 * @param string $permissions Key permission.
	 *
	 * @return array<string, mixed>|null
	 * @throws Exception
	 */
	public function create_key( int $user_id, string $description = '', string $permissions = 'read' ): ?array {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 || ! $this->is_valid_user_id( $user_id ) ) {
			return null;
		}

		$permissions     = $this->normalize_permissions( $permissions );
		$description     = $this->normalize_description( $description );
		$consumer_key    = $this->generate_token( 'ck_' );
		$consumer_secret = $this->generate_token( 'cs_' );
		$created_at      = current_time( 'mysql', true );

		$inserted = $this->wpdb->insert(
			$this->table,
			array(
				'user_id'         => $user_id,
				'description'     => $description,
				'permissions'     => $permissions,
				'consumer_key'    => self::hash_consumer_key( $consumer_key ),
				'consumer_secret' => self::hash_consumer_secret( $consumer_secret ),
				'truncated_key'   => substr( $consumer_key, -7 ),
				'created_at'      => $created_at,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return null;
		}

		return array(
			'key_id'          => (int) $this->wpdb->insert_id,
			'user_id'         => $user_id,
			'description'     => $description,
			'permissions'     => $permissions,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, -7 ),
			'created_at'      => $created_at,
		);
	}

	/**
	 * Update mutable metadata for an API key.
	 *
	 * @param int    $key_id      Key ID.
	 * @param int    $user_id     New owner user ID.
	 * @param string $description New key description.
	 * @param string $permissions New key permissions.
	 *
	 * @return bool
	 */
	public function update_key_meta( int $key_id, int $user_id, string $description, string $permissions ): bool {
		$key_id  = absint( $key_id );
		$user_id = absint( $user_id );

		if ( $key_id <= 0 || $user_id <= 0 || ! $this->is_valid_user_id( $user_id ) ) {
			return false;
		}

		$updated = $this->wpdb->update(
			$this->table,
			array(
				'user_id'     => $user_id,
				'description' => $this->normalize_description( $description ),
				'permissions' => $this->normalize_permissions( $permissions ),
				'updated_at'  => current_time( 'mysql', true ),
			),
			array( 'key_id' => $key_id ),
			array( '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Rotate consumer key and secret for an existing key.
	 *
	 * @param int $key_id Key ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public function regenerate_key( int $key_id ): ?array {
		$key_id = absint( $key_id );
		if ( $key_id <= 0 ) {
			return null;
		}

		$row = $this->get_key( $key_id );
		if ( ! $row ) {
			return null;
		}

		$consumer_key    = $this->generate_token( 'ck_' );
		$consumer_secret = $this->generate_token( 'cs_' );
		$updated_at      = current_time( 'mysql', true );

		$updated = $this->wpdb->update(
			$this->table,
			array(
				'consumer_key'    => self::hash_consumer_key( $consumer_key ),
				'consumer_secret' => self::hash_consumer_secret( $consumer_secret ),
				'truncated_key'   => substr( $consumer_key, -7 ),
				'updated_at'      => $updated_at,
			),
			array( 'key_id' => $key_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return null;
		}

		return array(
			'key_id'          => $key_id,
			'user_id'         => (int) $row->user_id,
			'description'     => (string) $row->description,
			'permissions'     => (string) $row->permissions,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, -7 ),
			'updated_at'      => $updated_at,
		);
	}

	/**
	 * Revoke (delete) one key.
	 *
	 * @param int $key_id Key ID.
	 *
	 * @return bool
	 */
	public function revoke_key( int $key_id ): bool {
		$key_id = absint( $key_id );
		if ( $key_id <= 0 ) {
			return false;
		}

		$deleted = $this->wpdb->delete( $this->table, array( 'key_id' => $key_id ), array( '%d' ) );

		return false !== $deleted;
	}

	/**
	 * Revoke multiple keys.
	 *
	 * @param array<int, int|string> $key_ids Key IDs to revoke.
	 *
	 * @return int
	 */
	public function revoke_keys( array $key_ids ): int {
		$key_ids = array_values( array_filter( array_map( 'absint', $key_ids ) ) );
		if ( empty( $key_ids ) ) {
			return 0;
		}

		$sql = $this->wpdb->prepare(
			"DELETE FROM {$this->table} WHERE key_id IN (" . LP_Helper::db_format_array( $key_ids, '%d' ) . ')',
			$key_ids
		);

		$deleted = $this->wpdb->query( $sql );

		return $deleted > 0 ? (int) $deleted : 0;
	}

	/**
	 * Find key row by plaintext consumer key.
	 *
	 * @param string $consumer_key Plaintext consumer key.
	 *
	 * @return object|null
	 */
	public function find_by_consumer_key( string $consumer_key ) {
		$consumer_key = LP_Helper::sanitize_params_submitted( $consumer_key );
		if ( '' === $consumer_key ) {
			return null;
		}

		$filter                   = new LP_Filter();
		$filter->collection       = $this->table;
		$filter->collection_alias = 'k';
		$filter->only_fields      = array( 'k.*' );
		$filter->where[]          = $this->wpdb->prepare( 'AND k.consumer_key = %s', self::hash_consumer_key( $consumer_key ) );
		$filter->limit            = 1;
		$filter->field_count      = 'k.key_id';
		$filter->run_query_count  = false;

		$total_rows = 0;
		$rows       = LP_Database::getInstance()->execute( $filter, $total_rows );
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return null;
		}

		return reset( $rows );
	}

	/**
	 * Get a key row by key ID.
	 *
	 * @param int $key_id Key ID.
	 *
	 * @return object|null
	 */
	public function get_key( int $key_id ) {
		$key_id = absint( $key_id );
		if ( $key_id <= 0 ) {
			return null;
		}

		$filter                   = new LP_Filter();
		$filter->collection       = $this->table;
		$filter->collection_alias = 'k';
		$filter->only_fields      = array( 'k.*' );
		$filter->where[]          = $this->wpdb->prepare( 'AND k.key_id = %d', $key_id );
		$filter->limit            = 1;
		$filter->field_count      = 'k.key_id';
		$filter->run_query_count  = false;

		$total_rows = 0;
		$rows       = LP_Database::getInstance()->execute( $filter, $total_rows );
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return null;
		}

		return reset( $rows );
	}

	/**
	 * Check whether raw secret matches stored secret hash.
	 *
	 * @param string $stored_hash     Stored secret hash from database.
	 * @param string $provided_secret Raw secret provided by request.
	 *
	 * @return bool
	 */
	public function verify_secret_hash( string $stored_hash, string $provided_secret ): bool {
		$provided_hash = self::hash_consumer_secret( $provided_secret );

		return hash_equals( $stored_hash, $provided_hash );
	}

	/**
	 * Update key usage metrics.
	 *
	 * @param int $key_id Key ID.
	 *
	 * @return void
	 */
	public function touch_usage( int $key_id ): void {
		$key_id = absint( $key_id );
		if ( $key_id <= 0 ) {
			return;
		}

		$now = current_time( 'mysql', true );
		$sql = $this->wpdb->prepare(
			"UPDATE {$this->table} SET last_access = %s, call_count = call_count + 1 WHERE key_id = %d",
			$now,
			$key_id
		);

		$this->wpdb->query( $sql );
	}

	/**
	 * Query keys list for admin table.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return array<string, mixed>
	 */
	public function query_keys( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'page'     => 1,
				'per_page' => 20,
				'search'   => '',
				'user_id'  => 0,
				'orderby'  => 'created_at',
				'order'    => 'DESC',
			)
		);

		$page     = max( 1, absint( $args['page'] ) );
		$per_page = max( 1, min( 100, absint( $args['per_page'] ) ) );

		$filter                   = new LP_Filter();
		$filter->collection       = $this->table;
		$filter->collection_alias = 'k';
		$filter->only_fields      = array(
			'k.*',
			'u.display_name AS user_display_name',
			'u.user_login',
		);
		$filter->join[]           = "LEFT JOIN {$this->users_table} u ON u.ID = k.user_id";
		$filter->field_count      = 'k.key_id';
		$filter->limit            = $per_page;
		$filter->page             = $page;

		$search = LP_Helper::sanitize_params_submitted( (string) $args['search'] );
		if ( '' !== $search ) {
			$filter->where[] = $this->wpdb->prepare( 'AND k.description LIKE %s', '%' . $this->wpdb->esc_like( $search ) . '%' );
		}

		$user_id = absint( $args['user_id'] );
		if ( $user_id > 0 ) {
			$filter->where[] = $this->wpdb->prepare( 'AND k.user_id = %d', $user_id );
		}

		$order_by_map     = array(
			'description' => 'k.description',
			'user'        => 'u.display_name',
			'permissions' => 'k.permissions',
			'last_access' => 'k.last_access',
			'call_count'  => 'k.call_count',
			'created_at'  => 'k.created_at',
		);
		$filter->order_by = $order_by_map[ $args['orderby'] ] ?? $order_by_map['created_at'];
		$filter->order    = 'ASC' === strtoupper( (string) $args['order'] ) ? LP_Filter::ORDER_ASC : LP_Filter::ORDER_DESC;

		$total_items = 0;
		$items       = LP_Database::getInstance()->execute( $filter, $total_items );

		return array(
			'items'    => is_array( $items ) ? $items : array(),
			'total'    => $total_items,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Return users currently owning MCP API keys.
	 *
	 * @return array<int, object>
	 */
	public function users_with_keys(): array {
		$filter                   = new LP_Filter();
		$filter->collection       = $this->users_table;
		$filter->collection_alias = 'u';
		$filter->only_fields      = array(
			'u.ID',
			'u.user_login',
			'u.display_name',
		);
		$filter->join[]           = "INNER JOIN {$this->table} k ON u.ID = k.user_id";
		$filter->group_by         = 'u.ID';
		$filter->order_by         = 'u.display_name';
		$filter->order            = LP_Filter::ORDER_ASC;
		$filter->limit            = -1;
		$filter->run_query_count  = false;

		$total_rows = 0;
		$rows       = LP_Database::getInstance()->execute( $filter, $total_rows );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Hash plaintext consumer key for storage/lookup.
	 *
	 * @param string $consumer_key Plaintext consumer key.
	 *
	 * @return string
	 */
	public static function hash_consumer_key( string $consumer_key ): string {
		return hash_hmac( 'sha256', $consumer_key, 'lp-mcp-api' );
	}

	/**
	 * Hash plaintext consumer secret for storage/verification.
	 *
	 * @param string $consumer_secret Plaintext consumer secret.
	 *
	 * @return string
	 */
	public static function hash_consumer_secret( string $consumer_secret ): string {
		return hash_hmac( 'sha256', $consumer_secret, 'lp-mcp-secret' );
	}

	/**
	 * Normalize description for DB storage.
	 *
	 * @param string $description Raw key description.
	 *
	 * @return string
	 */
	protected function normalize_description( string $description ): string {
		$description = LP_Helper::sanitize_params_submitted( $description );

		return function_exists( 'mb_substr' ) ? mb_substr( $description, 0, 200 ) : substr( $description, 0, 200 );
	}

	/**
	 * Normalize requested permission to a supported value.
	 *
	 * @param string $permissions Raw requested permission.
	 *
	 * @return string
	 */
	protected function normalize_permissions( string $permissions ): string {
		$permissions = LP_Helper::sanitize_params_submitted( $permissions, 'key' );

		if ( ! in_array( $permissions, self::PERMISSIONS, true ) ) {
			$permissions = 'read';
		}

		return $permissions;
	}

	/**
	 * Generate token in ck_/cs_ format.
	 *
	 * @param string $prefix Token prefix (`ck_` or `cs_`).
	 *
	 * @return string
	 */
	protected function generate_token( string $prefix ): string {
		try {
			$hex = bin2hex( random_bytes( 20 ) );
		} catch ( Exception $e ) {
			$hex = substr( hash( 'sha256', wp_generate_password( 64, true, true ) . microtime( true ) ), 0, 40 );
		}

		return $prefix . $hex;
	}

	/**
	 * Validate user existence via LearnPress DB + Filter classes.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function is_valid_user_id( int $user_id ): bool {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return false;
		}

		$filter              = new UserFilter();
		$filter->ID          = $user_id;
		$filter->limit       = 1;
		$filter->only_fields = array( 'u.ID' );
		$filter->field_count = 'u.ID';

		$total_rows = 0;
		$rows       = UserDB::getInstance()->get_users( $filter, $total_rows );

		return is_array( $rows ) && ! empty( $rows );
	}
}
