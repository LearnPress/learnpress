<?php

namespace LearnPress\Databases\Order;

use Exception;
use LearnPress\Databases\DataBase;
use LearnPress\Filters\Order\OrderItemsFilter;

defined( 'ABSPATH' ) || exit();

/**
 * Class LPOrderItemsDB
 *
 * @author tungnx
 * @since 4.3.2
 * @version 1.0.0
 */
class LPOrderItemsDB extends DataBase {
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
	 * Get order items by filter
	 *
	 * @throws Exception
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function get_items( OrderItemsFilter $filter, int &$total_rows = 0 ) {
		$default_fields = $filter->all_fields;
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_order_items;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'oi';
		}

		$ca = $filter->collection_alias;

		foreach ( $filter->fields as $k => $field ) {
			$filter->fields[ $k ] = "$ca.$field";
		}

		if ( isset( $filter->order_item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.order_item_id = %d", $filter->order_item_id );
		}

		if ( isset( $filter->order_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.order_id = %d", $filter->order_id );
		}

		if ( isset( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.item_id = %d", $filter->item_id );
		}

		if ( isset( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.item_type = %s", $filter->item_type );
		}
		return $this->execute( $filter, $total_rows );
	}
}
