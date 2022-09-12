<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Section_Items_DB
 */

class LP_Section_Items_DB extends LP_Database {
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
	 * Get section items
	 *
	 * @throws Exception
	 * @since 4.1.6
	 * @version 1.0.0
	 * @return array|null|int|string
	 */
	public function get_section_items( LP_Section_Items_Filter $filter ) {
		$default_fields = $this->get_cols_of_table( $this->tb_lp_section_items );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_section_items;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'si';
		}

		return $this->execute( $filter );
	}

	/**
	 * Update table
	 *
	 * @throws Exception
	 */
	public function update( LP_Section_Items_Filter $filter ) {
		$filter->collection = $this->tb_lp_section_items;
		$this->update_execute( $filter );
	}

	/**
	 * Delete items on section
	 *
	 * @param LP_Section_Items_Filter $filter
	 *
	 * @return bool|int|mysqli_result|resource|null
	 * @throws Exception
	 */
	public function delete_items( LP_Section_Items_Filter $filter ) {
		if ( empty( $filter->item_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND item_id IN(' . LP_Helper::db_format_array( $filter->item_ids, '%d' ) . ')',
			$filter->item_ids
		);

		$result = $this->wpdb->query(
			"DELETE FROM $this->tb_lp_section_items
			$where
			"
		);

		$this->check_execute_has_error();

		return $result;
	}

	public function get_last_number_order( int $section_id = 0 ): int {
		$query = $this->wpdb->prepare(
			"SELECT MAX(item_order)
			FROM $this->tb_lp_section_items
			WHERE section_id = %d",
			$section_id
		);

		$number_order = intval( $this->wpdb->get_var( $query ) );

		$this->check_execute_has_error();

		return $number_order;
	}
}

