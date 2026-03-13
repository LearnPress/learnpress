<?php

namespace LearnPress\MCP\Auth;

use LP_Database;

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

	public function __construct() {
		global $wpdb;

		$this->wpdb        = $wpdb;
		$this->table       = LP_Database::getInstance()->tb_lp_mcp_api_keys;
		$this->users_table = $wpdb->users;
	}

	/**
	 * Create a new API key and return plaintext credentials once.
	 *
	 * @param int    $user_id User ID that owns the key.
	 * @param string $description Optional key description.
	 * @param string $permissions Key permission.
	 *
	 * @return array<string, mixed>|null
	 */
	public function create_key( int $user_id, string $description = '', string $permissions = 'read' ): ?array {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 || ! get_user_by( 'id', $user_id ) ) {
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
	 */
	public function update_key_meta( int $key_id, int $user_id, string $description, string $permissions ): bool {
		$key_id  = absint( $key_id );
		$user_id = absint( $user_id );

		if ( $key_id <= 0 || $user_id <= 0 || ! get_user_by( 'id', $user_id ) ) {
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
	 */
	public function revoke_keys( array $key_ids ): int {
		$key_ids = array_values( array_filter( array_map( 'absint', $key_ids ) ) );
		if ( empty( $key_ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $key_ids ), '%d' ) );
		$sql          = $this->wpdb->prepare(
			"DELETE FROM {$this->table} WHERE key_id IN ({$placeholders})",
			$key_ids
		);

		$deleted = $this->wpdb->query( $sql );

		return $deleted > 0 ? (int) $deleted : 0;
	}

	/**
	 * Find key row by plaintext consumer key.
	 */
	public function find_by_consumer_key( string $consumer_key ) {
		$consumer_key = sanitize_text_field( wp_unslash( $consumer_key ) );
		if ( '' === $consumer_key ) {
			return null;
		}

		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE consumer_key = %s LIMIT 1",
			self::hash_consumer_key( $consumer_key )
		);

		return $this->wpdb->get_row( $sql );
	}

	/**
	 * Get a key row by key ID.
	 */
	public function get_key( int $key_id ) {
		$key_id = absint( $key_id );
		if ( $key_id <= 0 ) {
			return null;
		}

		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table} WHERE key_id = %d LIMIT 1",
			$key_id
		);

		return $this->wpdb->get_row( $sql );
	}

	/**
	 * Check whether raw secret matches stored secret hash.
	 */
	public function verify_secret_hash( string $stored_hash, string $provided_secret ): bool {
		$provided_hash = self::hash_consumer_secret( $provided_secret );

		return hash_equals( $stored_hash, $provided_hash );
	}

	/**
	 * Update key usage metrics.
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
		$offset   = ( $page - 1 ) * $per_page;

		$where        = array( '1=1' );
		$where_values = array();

		$search = sanitize_text_field( wp_unslash( (string) $args['search'] ) );
		if ( '' !== $search ) {
			$where[]        = 'k.description LIKE %s';
			$where_values[] = '%' . $this->wpdb->esc_like( $search ) . '%';
		}

		$user_id = absint( $args['user_id'] );
		if ( $user_id > 0 ) {
			$where[]        = 'k.user_id = %d';
			$where_values[] = $user_id;
		}

		$where_sql = implode( ' AND ', $where );

		$order_by_map = array(
			'description' => 'k.description',
			'user'        => 'u.display_name',
			'permissions' => 'k.permissions',
			'last_access' => 'k.last_access',
			'call_count'  => 'k.call_count',
			'created_at'  => 'k.created_at',
		);
		$orderby      = $order_by_map[ $args['orderby'] ] ?? $order_by_map['created_at'];
		$order        = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$count_sql = "SELECT COUNT(*) FROM {$this->table} k WHERE {$where_sql}";
		if ( ! empty( $where_values ) ) {
			$count_sql = $this->wpdb->prepare( $count_sql, $where_values );
		}
		$total_items = (int) $this->wpdb->get_var( $count_sql );

		$list_sql = "SELECT k.*, u.display_name AS user_display_name, u.user_login
			FROM {$this->table} k
			LEFT JOIN {$this->users_table} u ON u.ID = k.user_id
			WHERE {$where_sql}
			ORDER BY {$orderby} {$order}
			LIMIT %d OFFSET %d";

		$list_values = array_merge( $where_values, array( $per_page, $offset ) );
		$list_sql    = $this->wpdb->prepare( $list_sql, $list_values );
		$items       = $this->wpdb->get_results( $list_sql );

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
		$sql = "SELECT DISTINCT u.ID, u.user_login, u.display_name
			FROM {$this->table} k
			INNER JOIN {$this->users_table} u ON u.ID = k.user_id
			ORDER BY u.display_name ASC";

		$rows = $this->wpdb->get_results( $sql );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Hash plaintext consumer key for storage/lookup.
	 */
	public static function hash_consumer_key( string $consumer_key ): string {
		return hash_hmac( 'sha256', $consumer_key, 'lp-mcp-api' );
	}

	/**
	 * Hash plaintext consumer secret for storage/verification.
	 */
	public static function hash_consumer_secret( string $consumer_secret ): string {
		return hash_hmac( 'sha256', $consumer_secret, 'lp-mcp-secret' );
	}

	/**
	 * Normalize description for DB storage.
	 */
	protected function normalize_description( string $description ): string {
		$description = sanitize_text_field( wp_unslash( $description ) );

		return function_exists( 'mb_substr' ) ? mb_substr( $description, 0, 200 ) : substr( $description, 0, 200 );
	}

	/**
	 * Normalize requested permission to a supported value.
	 */
	protected function normalize_permissions( string $permissions ): string {
		$permissions = sanitize_key( $permissions );

		if ( ! in_array( $permissions, self::PERMISSIONS, true ) ) {
			$permissions = 'read';
		}

		return $permissions;
	}

	/**
	 * Generate token in ck_/cs_ format.
	 */
	protected function generate_token( string $prefix ): string {
		try {
			$hex = bin2hex( random_bytes( 20 ) );
		} catch ( \Exception $e ) {
			$hex = substr( hash( 'sha256', wp_generate_password( 64, true, true ) . microtime( true ) ), 0, 40 );
		}

		return $prefix . $hex;
	}
}
