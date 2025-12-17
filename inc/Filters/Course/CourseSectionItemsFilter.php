<?php

namespace LearnPress\Filters\Course;

use LearnPress\Filters\FilterBase;

defined( 'ABSPATH' ) || exit();

/**
 * Class CourseSectionItemsFilter
 *
 * Convert from LP_Section_Items_Filter
 *
 * @package LearnPress/Filters/Course
 * @version 4.3.2
 */
class CourseSectionItemsFilter extends FilterBase {
	const COL_SECTION_ITEM_ID = 'section_item_id';
	const COL_SECTION_ID      = 'section_id';
	const COL_ITEM_ID         = 'item_id';
	const COL_ITEM_ORDER      = 'item_order';
	const COL_ITEM_TYPE       = 'item_type';

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
	public $section_item_id;
	/**
	 * @var int
	 */
	public $section_id;
	/**
	 * @var int
	 */
	public $item_id;
	/**
	 * @var int
	 */
	public $item_order;
	/**
	 * @var string
	 */
	public $item_type;
	/**
	 * @var int
	 */
	public $search_title;
	/**
	 * @var int[]
	 */
	public $item_ids = [];
	/**
	 * @var int[]
	 */
	public $item_not_ids = [];
}
