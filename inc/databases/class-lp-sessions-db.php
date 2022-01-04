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

	/**
	 * Get rows in sections table
	 *
	 * WP_Error || array
	 *
	 * @author Nhamdv <email@email.com>
	 */
	public function get_sections_by_course_id( LP_Section_Filter $filter ) {
		if ( empty( $filter->section_course_id ) ) {
			return new WP_Error( 'no_course_id', __( 'No course id', 'learnpress' ), array( 'status' => 404 ) );
		}

		$where = 'WHERE 1=1';

		$where .= $this->wpdb->prepare( ' AND section_course_id = %d', $filter->section_course_id );

		// SEARCH
		if ( $filter->search_section ) {
			$where .= $this->wpdb->prepare( ' AND section_name LIKE %s ', '%' . $filter->search_section . '%' );
		}

		if ( $filter->section_ids ) {
			$section_ids = LP_Helper::db_format_array( $filter->section_ids, '%d' );
			$where      .= $this->wpdb->prepare( " AND section_id IN (" . $section_ids . ")", $filter->section_ids ); // phpcs:ignore
		}

		if ( $filter->section_not_ids ) {
			$section_not_ids = LP_Helper::db_format_array( $filter->section_not_ids, '%d' );
			$where          .= $this->wpdb->prepare( " AND section_id NOT IN (" . $section_not_ids . ")", $filter->section_not_ids ); // phpcs:ignore
		}

		$orderby = ' ORDER BY section_order ' . $filter->order ?? 'ASC';

		// PER_PAGE
		$limit = '';
		if ( $filter->limit != -1 ) {
			$offset = $filter->limit * ( $filter->page - 1 );
			$limit  = $this->wpdb->prepare( ' LIMIT %d, %d', $offset, $filter->limit );
		}

		$query = "SELECT * FROM {$this->tb_lp_sections} {$where} {$orderby} {$limit}";

		$results = $this->wpdb->get_results( $query, ARRAY_A );

		$total = 0;

		if ( $results ) {
			$query_total = "SELECT COUNT(*) FROM {$this->tb_lp_sections} {$where}";

			$total = $this->wpdb->get_var( $query_total );
		}

		return array(
			'results' => $results,
			'total'   => $total,
			'pages'   => (int) ceil( $total / (int) $filter->limit ),
		);
	}

	/**
	 * Get items in section_items table
	 *
	 * WP_Error || array
	 *
	 * @author Nhamdv <email@email.com>
	 */
	public function get_section_items_by_section_id( LP_Section_Items_Filter $filter ) {
		if ( empty( $filter->section_id ) ) {
			return new WP_Error( 'no_section_id', __( 'No section id', 'learnpress' ), array( 'status' => 404 ) );
		}

		$where = 'WHERE 1=1';

		$select = "SELECT post_title, post_type, post_name, post_status, post_date, post_author, ID, post_content FROM {$this->wpdb->posts} AS p";

		$inner_join = "INNER JOIN {$this->tb_lp_section_items} AS si ON p.ID = si.item_id";

		$where .= $this->wpdb->prepare( ' AND si.section_id = %d', $filter->section_id );

		// Check item type is avaliable( Assignments , H5P )
		$types    = learn_press_get_block_course_item_types();
		$db_types = LP_Helper::db_format_array( $types, '%s' );
		$where      .= $this->wpdb->prepare( " AND si.item_type IN (" . $db_types . ")", $types ); // phpcs:ignore

		// PER_PAGE
		$limit = '';
		if ( $filter->limit != -1 ) {
			$offset = $filter->limit * ( $filter->page - 1 );
			$limit  = $this->wpdb->prepare( ' LIMIT %d, %d', $offset, $filter->limit );
		}

		// SEARCH
		if ( $filter->search_title ) {
			$where .= $this->wpdb->prepare( ' AND p.post_title LIKE %s ', '%' . $filter->search_title . '%' );
		}

		// INCLUDE
		if ( $filter->item_ids ) {
			$item_ids = LP_Helper::db_format_array( $filter->item_ids, '%d' );
			$where   .= $this->wpdb->prepare( " AND p.ID IN (" . $item_ids . ")", $filter->item_ids ); // phpcs:ignore
		}

		// EXCLUDE
		if ( $filter->item_not_ids ) {
			$item_not_ids = LP_Helper::db_format_array( $filter->item_not_ids, '%d' );
			$where       .= $this->wpdb->prepare( " AND section_id NOT IN (" . $item_not_ids . ")", $filter->item_not_ids ); // phpcs:ignore
		}

		$orderby = ' ORDER BY si.item_order ' . $filter->order ?? 'ASC';

		$query = "{$select} {$inner_join} {$where} {$orderby} {$limit}";

		$results = $this->wpdb->get_results( $query, ARRAY_A );

		$total = 0;

		if ( $results ) {
			$query_total = "SELECT COUNT(*) FROM {$this->wpdb->posts} AS p {$inner_join} {$where}";

			$total = $this->wpdb->get_var( $query_total );
		}

		return array(
			'results' => $results,
			'total'   => $total,
			'pages'   => (int) ceil( $total / (int) $filter->limit ),
		);
	}
}

LP_Sessions_DB::getInstance();

