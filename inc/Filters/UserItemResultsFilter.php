<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class UserItemResultsFilter
 *
 * Filter query for learnpress_user_item_results table.
 *
 * @since 4.5.0
 * @version 1.0.0
 */
class UserItemResultsFilter extends UserItemsFilter {
	const COL_ID         = 'id';
	const COL_GUEST_KEY  = 'guest_key';
	const COL_RESULT     = 'result';
	const COL_EXTRA_DATA = 'extra_data';

	/**
	 * @var string[] all fields of table
	 */
	public array $all_fields = [
		self::COL_ID,
		self::COL_USER_ITEM_ID,
		self::COL_USER_ID,
		self::COL_GUEST_KEY,
		self::COL_ITEM_ID,
		self::COL_ITEM_TYPE,
		self::COL_STATUS,
		self::COL_GRADUATION,
		self::COL_REF_ID,
		self::COL_REF_TYPE,
		self::COL_PARENT_ID,
		self::COL_RESULT,
		self::COL_EXTRA_DATA,
	];

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $guest_key;

	/**
	 * @var string
	 */
	public $result;

	/**
	 * @var string
	 */
	public $extra_data;

	/**
	 * @var string
	 */
	public $field_count = self::COL_ID;
}
