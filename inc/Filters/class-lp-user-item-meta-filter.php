<?php
/**
 * Class LP_User_Item_Meta_Filter
 *
 * Filter value on table learnpress_user_itemmeta
 *
 * @version 1.0.0
 * @since 4.2.5
 */

class LP_User_Item_Meta_Filter extends LP_Filter {
	const COL_META_ID                 = 'meta_id';
	const COL_LEARNPRESS_USER_ITEM_ID = 'learnpress_user_item_id';
	const COL_META_KEY                = 'meta_key';
	const COL_META_VALUE              = 'meta_value';
	const COL_EXTRA_VALUE             = 'extra_value';
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		self::COL_META_ID,
		self::COL_LEARNPRESS_USER_ITEM_ID,
		self::COL_META_KEY,
		self::COL_META_VALUE,
		self::COL_EXTRA_VALUE,
	];
	/**
	 * @var int
	 */
	public $meta_id = 0;
	/**
	 * @var int foreign key, join to table learnpress_user_items
	 */
	public $learnpress_user_item_id = 0;
	/**
	 * @var string meta key (VARCHAR 255)
	 */
	public $meta_key = '';
	/**
	 * @var string meta value (VARCHAR 255)
	 */
	public $meta_value = '';
	/**
	 * @var array string (LONGTEXT)
	 */
	public $extra_value = '';
	/**
	 * @var string column count.
	 */
	public $field_count = self::COL_META_ID;
}


