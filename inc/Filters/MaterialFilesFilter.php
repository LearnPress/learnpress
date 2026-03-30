<?php

namespace LearnPress\Filters;

/**
 * Class MaterialFilesFilter
 *
 * Filter query for learnpress_files table
 *
 * @since 4.2.2
 * @version 1.0.0
 */
class MaterialFilesFilter extends FilterBase {
	const COL_FILE_ID    = 'file_id';
	const COL_FILE_NAME  = 'file_name';
	const COL_FILE_TYPE  = 'file_type';
	const COL_ITEM_ID    = 'item_id';
	const COL_ITEM_TYPE  = 'item_type';
	const COL_METHOD     = 'method';
	const COL_FILE_PATH  = 'file_path';
	const COL_ORDERS     = 'orders';
	const COL_CREATED_AT = 'created_at';

	/**
	 * @var string[] all fields of table
	 */
	public array $all_fields = [
		self::COL_FILE_ID,
		self::COL_FILE_NAME,
		self::COL_FILE_TYPE,
		self::COL_ITEM_ID,
		self::COL_ITEM_TYPE,
		self::COL_METHOD,
		self::COL_FILE_PATH,
		self::COL_ORDERS,
		self::COL_CREATED_AT,
	];

	/**
	 * @var int
	 */
	public $file_id;

	/**
	 * @var int[]
	 */
	public $file_ids = [];

	/**
	 * @var int
	 */
	public $item_id;

	/**
	 * @var int[]
	 */
	public $item_ids = [];

	/**
	 * @var string upload|external
	 */
	public $item_type;

	/**
	 * @var string upload|external
	 */
	public $method;

	/**
	 * When true and item_id is a course, include files from lessons within the course sections.
	 * Maps to the frontend (non-admin) view of get_material_by_item_id.
	 *
	 * @var bool
	 */
	public bool $include_course_items = false;

	/**
	 * @var string
	 */
	public $field_count = self::COL_FILE_ID;
}
