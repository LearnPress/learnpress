<?php

namespace LearnPress\Filters;

/**
 * Class UserItemsFilter
 *
 * Filter query for learnpress_user_items table
 *
 * @since  4.2.9.3
 * @version 1.0.0
 */
class UserItemsFilter extends FilterBase {
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
	public array $all_fields = [
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
	public $user_item_id;
	/**
	 * @var int
	 */
	public $user_id;
	/**
	 * @var array int
	 */
	public $user_ids = [];
	/**
	 * @var int
	 */
	public $item_id;
	/**
	 * @var array int
	 */
	public $item_ids = [];
	/**
	 * @var string
	 */
	public $status;
	/**
	 * @var string[]
	 *
	 * @since 4.2.8.2
	 */
	public $statues = [];
	/**
	 * @var string
	 */
	public $graduation;
	/**
	 * @var string[]
	 *
	 * @since 4.2.8.2
	 */
	public $graduations = [];
	/**
	 * @var string
	 */
	public $item_type;
	/**
	 * @var int
	 */
	public $ref_id;
	/**
	 * @var string
	 */
	public $ref_type;
	/**
	 * @var string
	 */
	public $start_time;
	/**
	 * @var string
	 */
	public $end_time;
	/**
	 * @var int
	 */
	public $parent_id;
	/**
	 * @var int[]
	 */
	public $user_item_ids = [];
	/**
	 * @var string
	 */
	public $field_count = self::COL_USER_ITEM_ID;
}
