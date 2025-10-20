<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\UserItemsFilter;
use LP_Helper;

defined( 'ABSPATH' ) || exit();

/**
 * Class UserItemsDB
 *
 * @since 4.2.9.3
 * @version 1.0.0
 */
class UserItemsDB extends DataBase {
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
	 * Get users items
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 * @since 4.1.6.9
	 * @version 1.0.4
	 */
	public function get_user_items( UserItemsFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_user_items;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'ui';
		}

		if ( isset( $filter->ref_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.ref_id = %d', $filter->ref_id );
		}

		if ( isset( $filter->ref_type ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.ref_type = %s', $filter->ref_type );
		}

		if ( isset( $filter->user_item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.user_item_id = %d', $filter->user_item_id );
		}

		// Get by user_item_ids
		if ( ! empty( $filter->user_item_ids ) ) {
			$user_item_ids_format = LP_Helper::db_format_array( $filter->user_item_ids );
			$filter->where[]      = $this->wpdb->prepare( 'AND ui.user_item_id IN (' . $user_item_ids_format . ')', $filter->user_item_ids );
		}

		if ( isset( $filter->user_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.user_id = %d', $filter->user_id );
		}

		if ( isset( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.item_type = %s', $filter->item_type );
		}

		if ( ! empty( $filter->item_ids ) ) {
			$item_ids_format = LP_Helper::db_format_array( $filter->item_ids );
			$filter->where[] = $this->wpdb->prepare( 'AND ui.item_id IN (' . $item_ids_format . ')', $filter->item_ids );
		}

		if ( isset( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.item_id = %s', $filter->item_id );
		}

		if ( isset( $filter->status ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.status = %s', $filter->status );
		}

		if ( ! empty( $filter->statues ) ) {
			$statues_format  = LP_Helper::db_format_array( $filter->statues );
			$filter->where[] = $this->wpdb->prepare( 'AND ui.status IN (' . $statues_format . ')', $filter->statues );
		}

		if ( isset( $filter->graduation ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.graduation = %s', $filter->graduation );
		}

		if ( ! empty( $filter->graduations ) ) {
			$graduations_format = LP_Helper::db_format_array( $filter->graduations );
			$filter->where[]    = $this->wpdb->prepare( 'AND ui.graduation IN (' . $graduations_format . ')', $filter->graduations );
		}

		if ( isset( $filter->parent_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND ui.parent_id = %s', $filter->parent_id );
		}

		$filter = apply_filters( 'lp/user_items/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
