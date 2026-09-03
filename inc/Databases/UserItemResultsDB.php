<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\UserItemResultsFilter;

defined( 'ABSPATH' ) || exit();

/**
 * Class UserItemResultsDB
 *
 * Database access for learnpress_user_item_results table.
 *
 * @since 4.5.0
 * @version 1.0.0
 */
class UserItemResultsDB extends DataBase {
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
	 * Get user item results
	 *
	 * @param UserItemResultsFilter $filter
	 * @param int $total_rows
	 *
	 * @return array|object|null|int|string
	 * @throws Exception
	 */
	public function get_user_item_results( UserItemResultsFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_user_item_results;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'uir';
		}

		$alias = $filter->collection_alias;

		if ( ! empty( $filter->id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.id = %d", $filter->id );
		}

		if ( ! empty( $filter->user_item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.user_item_id = %d", $filter->user_item_id );
		}

		if ( ! empty( $filter->user_item_ids ) ) {
			$user_item_ids   = array_map( 'intval', $filter->user_item_ids );
			$filter->where[] = "AND {$alias}.user_item_id IN (" . implode( ',', $user_item_ids ) . ')';
		}

		if ( isset( $filter->user_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.user_id = %d", $filter->user_id );
		}

		if ( ! empty( $filter->user_ids ) ) {
			$user_ids        = array_map( 'intval', $filter->user_ids );
			$filter->where[] = "AND {$alias}.user_id IN (" . implode( ',', $user_ids ) . ')';
		}

		if ( isset( $filter->guest_key ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.guest_key = %s", $filter->guest_key );
		}

		if ( isset( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.item_id = %d", $filter->item_id );
		}

		if ( ! empty( $filter->item_ids ) ) {
			$item_ids        = array_map( 'intval', $filter->item_ids );
			$filter->where[] = "AND {$alias}.item_id IN (" . implode( ',', $item_ids ) . ')';
		}

		if ( isset( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.item_type = %s", $filter->item_type );
		}

		if ( isset( $filter->status ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.status = %s", $filter->status );
		}

		if ( ! empty( $filter->statuses ) ) {
			$statuses        = array_map(
				[ $this->wpdb, 'prepare' ],
				array_fill( 0, count( $filter->statuses ), '%s' ),
				$filter->statuses
			);
			$filter->where[] = "AND {$alias}.status IN (" . implode( ',', $statuses ) . ')';
		}

		if ( isset( $filter->graduation ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.graduation = %s", $filter->graduation );
		}

		if ( ! empty( $filter->graduations ) ) {
			$graduations     = array_map( [ $this->wpdb, 'prepare' ], array_fill( 0, count( $filter->graduations ), '%s' ), $filter->graduations );
			$filter->where[] = "AND {$alias}.graduation IN (" . implode( ',', $graduations ) . ')';
		}

		if ( isset( $filter->ref_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.ref_id = %d", $filter->ref_id );
		}

		if ( isset( $filter->ref_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.ref_type = %s", $filter->ref_type );
		}

		if ( isset( $filter->parent_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$alias}.parent_id = %d", $filter->parent_id );
		}

		$filter = apply_filters( 'lp/user_item_results/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
