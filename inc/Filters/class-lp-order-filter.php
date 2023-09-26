<?php
/**
 * Class LP_Order_Filter
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 1.0.0
 * @since 4.1.4
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Order_Filter' ) ) {
	return;
}

class LP_Order_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = 'lp_order';
	/**
	 * @var int
	 */
	public $order_item_id = 0;
	/**
	 * @var int[]
	 */
	public $order_item_ids = [];
}
