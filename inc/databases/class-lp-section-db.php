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
}

LP_Section_DB::getInstance();

