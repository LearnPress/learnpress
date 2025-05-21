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
	 * @return array|null|int|string
	 * @throws Exception
	 * @version 1.0.2
	 * @since 4.1.6
	 */
	public function get_section_items( LP_Section_Items_Filter $filter ) {
		$default_fields = $filter->all_fields;
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_section_items;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'si';
		}

		if ( ! empty( $filter->section_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.section_id = %d', $filter->section_id );
		}

		if ( ! empty( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.item_id = %d', $filter->item_id );
		}

		if ( ! empty( $filter->item_ids ) ) {
			$filter->where[] = $this->wpdb->prepare(
				'AND si.item_id IN(' . LP_Helper::db_format_array( $filter->item_ids, '%d' ) . ')',
				$filter->item_ids
			);
		}

		if ( ! empty( $filter->section_item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.section_item_id = %d', $filter->section_item_id );
		}

		if ( ! empty( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.item_type = %s', $filter->item_type );
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

	/**
	 * Delete item on section of course not in table posts.
	 *
	 * @param int $course_id
	 *
	 * @throws Exception
	 * @since 4.2.6.4
	 * @version 1.0.0
	 */
	public function delete_item_not_in_tb_post( int $course_id ) {
		$filter_section = $this->wpdb->prepare(
			"DELETE si
			FROM $this->tb_lp_section_items AS si
			INNER JOIN $this->tb_lp_sections AS s ON si.section_id = s.section_id
			AND s.section_course_id = %d
			WHERE item_id NOT IN (SELECT ID FROM $this->tb_posts WHERE post_status = 'publish')
            ",
			$course_id
		);

		$this->wpdb->query( $filter_section );

		$this->check_execute_has_error();
	}

	/**
	 * Insert data
	 *
	 * @param array $data
	 *
	 * @return int
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.2.8.6
	 */
	public function insert_data( array $data ): int {
		$filter = new LP_Section_ITems_Filter();

		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields ) ) {
				unset( $data[ $col_name ] );
			}
		}

		$this->wpdb->insert( $this->tb_lp_section_items, $data );

		$this->check_execute_has_error();

		return $this->wpdb->insert_id;
	}

	/**
	 * Update data
	 *
	 * @param array $data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public function update_data( array $data ): bool {
		if ( empty( $data['section_item_id'] ) ) {
			throw new Exception( __( 'Invalid section_item_id!', 'learnpress' ) . ' | ' . __FUNCTION__ );
		}

		$filter             = new LP_Section_items_Filter();
		$filter->collection = $this->tb_lp_section_items;
		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields ) ) {
				continue;
			}

			if ( is_null( $value ) ) {
				$filter->set[] = $col_name . ' = null';
			} else {
				$filter->set[] = $this->wpdb->prepare( $col_name . ' = %s', $value );
			}
		}

		$filter->where[] = $this->wpdb->prepare( 'AND section_item_id = %d', $data['section_item_id'] );
		$this->update_execute( $filter );

		return true;
	}
}
