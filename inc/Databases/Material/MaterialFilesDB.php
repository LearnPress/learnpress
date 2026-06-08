<?php
/**
 * Material Files DB
 * @version 1.0.0
 */

namespace LearnPress\Databases\Material;

use LearnPress\Databases\DataBase;
use LearnPress\Filters\MaterialFilter;
use LP_Helper;

defined( 'ABSPATH' ) || exit();

class MaterialFilesDB extends DataBase {
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance(): self {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get files
	 *
	 * @param MaterialFilter $filter
	 * @param int $total_rows
	 * @return array|int|string|null
	 */
	public function get_files( MaterialFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_files;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'f';
		}

		$ca = $filter->collection_alias;

		// file_id
		if ( ! empty( $filter->file_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.file_id = %d", $filter->file_id );
		}

		// file_name (LIKE)
		if ( ! empty( $filter->file_name ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.file_name LIKE %s", '%' . $filter->file_name . '%' );
		}

		// file_type
		if ( ! empty( $filter->file_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.file_type = %s", $filter->file_type );
		}

		// item_id
		if ( ! empty( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.item_id = %d", $filter->item_id );
		}

		// item_ids (IN)
		if ( ! empty( $filter->item_ids ) ) {
			$item_ids_format = LP_Helper::db_format_array( $filter->item_ids, '%d' );
			$filter->where[] = $this->wpdb->prepare( "$ca.item_id IN (" . $item_ids_format . ')', $filter->item_ids );
		}

		// item_type
		if ( ! empty( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.item_type = %s", $filter->item_type );
		}

		// method
		if ( ! empty( $filter->method ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.method = %s", $filter->method );
		}

		// file_path (LIKE)
		if ( ! empty( $filter->file_path ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.file_path LIKE %s", '%' . $filter->file_path . '%' );
		}

		// orders
		if ( ! empty( $filter->orders ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.orders = %d", $filter->orders );
		}

		// created_at range
		if ( ! empty( $filter->created_at ) ) {
			$filter->where[] = $this->wpdb->prepare( "$ca.created_at >= %s", $filter->created_at );
		}

		$filter = apply_filters( 'lp/files/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
