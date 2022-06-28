<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Section_DB
 */

class LP_Section_DB extends LP_Database {
	public static $_instance;

	public function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get sections
	 *
	 * @throws Exception
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function get_sections( LP_Section_Filter $filter ) {
		$default_fields = $this->get_cols_of_table( $this->tb_lp_sections );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 's';
		}

		$this->execute( $filter );
	}

	/**
	 * Get course id by section id
	 *
	 * @param int $section_Id
	 *
	 * @return mixed
	 */
	public function get_course_id_by_section_id( int $section_Id = 0 ) {

	}

	/**
	 * Get section ids by course id
	 *
	 * @param int $course_id
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 * @return array
	 */
	public function get_section_ids_by_course( int $course_id ): array {
		$query = $this->wpdb->prepare(
			"SELECT section_id
			FROM $this->tb_lp_sections
			WHERE section_course_id = %d
			",
			$course_id
		);

		return $this->wpdb->get_col( $query );
	}

	/**
	 * Remove rows IN user_item_ids
	 *
	 * @param LP_Section_Filter $filter $filter->section_ids, $filter->author_id_course
	 *
	 * @throws Exception
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	public function delete_section( LP_Section_Filter $filter ) {
		// Check valid user.
		if ( ! is_user_logged_in() || ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $filter->author_id_course ) ) {
			throw new Exception( __FUNCTION__ . ': User invalid!' );
		}

		if ( empty( $filter->section_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND section_id IN(' . LP_Helper::db_format_array( $filter->section_ids, '%d' ) . ')',
			$filter->section_ids
		);

		return $this->wpdb->query(
			"DELETE FROM $this->tb_lp_sections
			$where
			"
		);
	}

	/**
	 * Remove rows IN user_item_ids
	 *
	 * @param LP_Section_Filter $filter $filter->section_ids, $filter->author_id_course
	 *
	 * @throws Exception
	 * @since 4.1.4.1
	 * @version 1.0.0
	 */
	public function delete_section_items( LP_Section_Filter $filter ) {
		// Check valid user.
		if ( ! is_user_logged_in() || ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $filter->author_id_course ) ) {
			throw new Exception( __FUNCTION__ . ': User invalid!' );
		}

		if ( empty( $filter->section_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND section_id IN(' . LP_Helper::db_format_array( $filter->section_ids, '%d' ) . ')',
			$filter->section_ids
		);

		return $this->wpdb->query(
			"DELETE FROM $this->tb_lp_section_items
			$where
			"
		);
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

	public function get_course_id_by_section( int $section_id ) : int {
		static $output;

		global $wpdb;

		if ( empty( $section_id ) ) {
			return false;
		}

		if ( ! isset( $output ) ) {
			$output = $wpdb->get_var( $wpdb->prepare( "SELECT section_course_id FROM {$wpdb->learnpress_sections} WHERE section_id = %d ORDER BY section_id DESC LIMIT 1", $section_id ) );
		}

		if ( $output ) {
			return absint( $output );
		}

		return false;
	}

	public function get_section_id_by_item_id( $item_id ) {
		global $wpdb;

		if ( empty( $item_id ) ) {
			return false;
		}

		$section_id = $wpdb->get_var( $wpdb->prepare( "SELECT section_id FROM {$wpdb->learnpress_section_items} WHERE item_id = %d ORDER BY section_id DESC LIMIT 1", $item_id ) );

		if ( $section_id ) {
			return absint( $section_id );
		}

		return false;
	}
}

LP_Section_DB::getInstance();

