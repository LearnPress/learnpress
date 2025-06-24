<?php

namespace LearnPress\Filters\Order;

use LP_Filter;

defined( 'ABSPATH' ) || exit();

/**
 * Class LPOrderItemsDB
 *
 * @author tungnx
 * @since 4.2.8.8
 * @version 1.0.0
 */
class LPOrderItemsFilter extends LP_Filter {
	const COL_ORDER_ITEM_ID = 'order_item_id';
	const COL_ORDER_ID      = 'order_id';
	const COL_ITEM_ID       = 'item_id';
	const COL_ITEM_NAME     = 'order_item_name';
	const COL_ITEM_TYPE     = 'item_type';
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		self::COL_ORDER_ITEM_ID,
		self::COL_ORDER_ID,
		self::COL_ITEM_ID,
		self::COL_ITEM_NAME,
		self::COL_ITEM_TYPE,
	];
	/**
	 * @var int
	 */
	public $order_item_id;
	/**
	 * @var int
	 */
	public $order_id;
	/**
	 * @var int
	 */
	public $item_id;
	/**
	 * @var string
	 */
	public $item_type;
	public $field_count = self::COL_ORDER_ITEM_ID;
}
