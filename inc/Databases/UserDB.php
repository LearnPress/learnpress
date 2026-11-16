<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\UserFilter;

/**
 * Class UserDB
 *
 * Refactor of LP_User_DB
 *
 * @since 4.3.6
 * @version 1.0.0
 */
class UserDB extends DataBase {
	/**
	 * @var UserDB|null
	 */
	private static ?UserDB $_instance = null;

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Get instance
	 *
	 * @return UserDB
	 */
	public static function getInstance(): UserDB {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get users
	 *
	 * @param UserFilter $filter
	 * @param int $total_rows
	 *
	 * @return array|object|null|int|string
	 * @throws Exception
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public function get_users( UserFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_users;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'u';
		}

		$ca = $filter->collection_alias;

		// Find ID
		if ( isset( $filter->ID ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID = %d", $filter->ID );
		}

		// Find by IDs
		if ( ! empty( $filter->ids ) ) {
			$ids_format      = implode( ', ', array_fill( 0, count( $filter->ids ), '%d' ) );
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID IN ($ids_format)", $filter->ids );
		}

		// Find by user_nicename
		if ( ! empty( $filter->user_nicename ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.user_nicename LIKE %s", '%' . $filter->user_nicename . '%' );
		}

		// Find by user_email
		if ( ! empty( $filter->user_email ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.user_email = %s", $filter->user_email );
		}

		// Find by user_login
		if ( ! empty( $filter->user_login ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.user_login = %s", $filter->user_login );
		}

		// Find by display_name
		if ( ! empty( $filter->display_name ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.display_name LIKE %s", '%' . $filter->display_name . '%' );
		}

		// Exclude ids
		if ( ! empty( $filter->exclude_ids ) ) {
			$ids_format      = implode( ', ', array_fill( 0, count( $filter->exclude_ids ), '%d' ) );
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID NOT IN ($ids_format)", $filter->exclude_ids );
		}

		// Search keyword
		if ( ! empty( $filter->search ) ) {
			$like            = '%' . $this->wpdb->esc_like( $filter->search ) . '%';
			$filter->where[] = $this->wpdb->prepare(
				"AND ( $ca.user_login LIKE %s OR $ca.user_nicename LIKE %s OR $ca.user_email LIKE %s OR $ca.display_name LIKE %s )",
				$like,
				$like,
				$like,
				$like
			);
		}

		// Find by roles (user has at least one role)
		if ( ! empty( $filter->roles ) ) {
			$filter->join[] = $this->wpdb->prepare(
				"INNER JOIN {$this->wpdb->usermeta} AS um ON um.user_id = $ca.ID AND um.meta_key = %s",
				$this->wpdb->prefix . 'capabilities'
			);

			$role_conditions = [];
			foreach ( $filter->roles as $role ) {
				$role_conditions[] = $this->wpdb->prepare(
					'um.meta_value LIKE %s',
					'%' . $this->wpdb->esc_like( '"' . $role . '"' ) . '%'
				);
			}
			$filter->where[] = 'AND ( ' . implode( ' OR ', $role_conditions ) . ' )';
		}

		// Order by (whitelist columns, `include`, or safe `alias.column`)
		if ( ! empty( $filter->order_by ) ) {
			$columns_order = [ 'ID', 'display_name', 'user_registered', 'user_nicename', 'user_email', 'user_url' ];
			if ( in_array( $filter->order_by, $columns_order, true ) ) {
				$filter->order_by = "$ca.$filter->order_by";
			} elseif ( $filter->order_by === 'include' && ! empty( $filter->ids ) ) {
				$ids_order        = implode( ',', array_map( 'absint', $filter->ids ) );
				$filter->order_by = "FIELD($ca.ID, $ids_order)";
			} elseif ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*\.[A-Za-z_][A-Za-z0-9_]*$/', $filter->order_by ) ) {
				// Only a safe caller-defined `alias.column` (from its own join) is kept as is.
				$filter->order_by = '';
			}
		}

		$filter = apply_filters( 'lp/user/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
