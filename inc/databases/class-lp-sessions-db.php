<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Sessions_DB
 *
 * @since 4.1.1
 */
class LP_Sessions_DB extends LP_Database {
	/**
	 * @var LP_Sessions_DB
	 */
	private static $instance;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Instance
	 *
	 * @return LP_Sessions_DB
	 */
	public static function getInstance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get sessions.
	 *
	 * @param LP_Session_Filter $filter
	 *
	 * @return array|int|string|null
	 * @throws Exception
	 */
	public function get_sessions( LP_Session_Filter $filter ) {
		$default_fields = $filter->all_fields;
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_sessions;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'ss';
		}

		if ( empty( $filter->field_count ) ) {
			$filter->field_count = 'session_id';
		}

		// Filter by session_key.
		if ( ! empty( $filter->session_key ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND session_key = %s', $filter->session_key );
		}

		return $this->execute( $filter );
	}

	/**
	 * Get delete rows in session table
	 *
	 * @throws
	 */
	public function delete_rows() {
		$now     = current_time( 'timestamp' );
		$adayago = $now - ( 24 * 60 * 60 );
		$where   = 'WHERE session_expiry < ' . $adayago . '';
		$table   = $this->tb_lp_sessions;
		$limit   = 100;
		$result  = $this->wpdb->query(
			"
			DELETE FROM {$table}
			{$where}
			LIMIT {$limit}
			"
		);

		$this->check_execute_has_error();

		return $result;
	}
	public function count_row_db_sessions() {
		global $wpdb;
		$now     = current_time( 'timestamp' );
		$adayago = $now - ( 24 * 60 * 60 );
		$where   = 'WHERE session_expiry < ' . $adayago . ' AND 0=%d';
		$query   = $wpdb->prepare(
			"
			SELECT count(*)
			FROM $this->tb_lp_sessions
			{$where}
			",
			0
		);

		$result = $wpdb->get_var( $query );
		return $result;
	}
}

