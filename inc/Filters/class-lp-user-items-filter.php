<?php

/**
 * Class LP_Post_Type_Filter
 *
 * Filter post type of LP
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @since  4.0.0
 * @version 1.0.2
 */
class LP_User_Items_Filter extends LP_Filter {
	const COL_USER_ITEM_ID = 'user_item_id';
	const COL_USER_ID      = 'user_id';
	const COL_ITEM_ID      = 'item_id';
	const COL_START_TIME   = 'start_time';
	const COL_END_TIME     = 'end_time';
	const COL_ITEM_TYPE    = 'item_type';
	const COL_STATUS       = 'status';
	const COL_GRADUATION   = 'graduation';
	const COL_REF_ID       = 'ref_id';
	const COL_REF_TYPE     = 'ref_type';
	const COL_PARENT_ID    = 'parent_id';
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		self::COL_USER_ITEM_ID,
		self::COL_USER_ID,
		self::COL_ITEM_ID,
		self::COL_START_TIME,
		self::COL_END_TIME,
		self::COL_ITEM_TYPE,
		self::COL_STATUS,
		self::COL_GRADUATION,
		self::COL_REF_ID,
		self::COL_REF_TYPE,
		self::COL_PARENT_ID,
	];
	/**
	 * @var int
	 */
	public $user_item_id = 0;
	/**
	 * @var int
	 */
	public $user_id = false;
	/**
	 * @var array int
	 */
	public $user_ids = [];
	/**
	 * @var int
	 */
	public $item_id = 0;
	/**
	 * @var array int
	 */
	public $item_ids = [];
	/**
	 * @var string
	 */
	public $status = '';
	/**
	 * @var string
	 */
	public $graduation = '';
	/**
	 * @var string
	 */
	public $item_type = '';
	/**
	 * @var int
	 */
	public $ref_id = 0;
	/**
	 * @var string
	 */
	public $ref_type = '';
	/**
	 * @var string
	 */
	public $start_time = '';
	/**
	 * @var string
	 */
	public $end_time = '';
	/**
	 * @var int
	 */
	public $parent_id = 0;
	/**
	 * @var int[]
	 */
	public $user_item_ids = [];
	/**
	 * @var string
	 */
	public $field_count = 'user_item_id';
}
