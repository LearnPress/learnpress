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

		$filter = apply_filters( 'lp/user/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
