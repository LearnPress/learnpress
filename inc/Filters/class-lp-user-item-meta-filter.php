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
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		'meta_id',
		'learnpress_user_item_id',
		'meta_key',
		'meta_value',
		'extra_value',
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
}


