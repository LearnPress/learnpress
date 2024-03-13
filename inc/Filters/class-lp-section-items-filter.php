<?php
/**
 * Class LP_Section_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 4.1.4.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Section_Items_Filter' ) ) {
	return;
}

class LP_Section_Items_Filter extends LP_Filter {
	const COL_SECTION_ITEM_ID = 'section_item_id';
	const COL_SECTION_ID = 'section_id';
	const COL_ITEM_ID = 'item_id';
	const COL_ITEM_ORDER = 'item_order';
	const COL_ITEM_TYPE = 'item_type';
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		self::COL_SECTION_ITEM_ID,
		self::COL_SECTION_ID,
		self::COL_ITEM_ID,
		self::COL_ITEM_ORDER,
		self::COL_ITEM_TYPE,
	];
	/**
	 * @var int
	 */
	public $section_item_id = 0;
	/**
	 * @var int
	 */
	public $section_id = 0;
	/**
	 * @var int
	 */
	public $item_order = 0;
	/**
	 * @var string
	 */
	public $item_type = '';
	/**
	 * @var int
	 */
	public $search_title = '';
	/**
	 * @var int[]
	 */
	public $item_ids = [];
	/**
	 * @var int[]
	 */
	public $item_not_ids = [];
}
