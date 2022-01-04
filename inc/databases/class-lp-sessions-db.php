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
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
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

LP_Sessions_DB::getInstance();

