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

if ( class_exists( 'LP_Section_Filter' ) ) {
	return;
}

class LP_Section_Filter extends LP_Filter {
	const COL_SECTION_ID          = 'section_id';
	const COL_SECTION_NAME        = 'section_name';
	const COL_SECTION_COURSE_ID   = 'section_course_id';
	const COL_SECTION_ORDER       = 'section_order';
	const COL_SECTION_DESCRIPTION = 'section_description';

	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		self::COL_SECTION_ID,
		self::COL_SECTION_NAME,
		self::COL_SECTION_COURSE_ID,
		self::COL_SECTION_ORDER,
		self::COL_SECTION_DESCRIPTION,
	];

	/**
	 * @var int
	 */
	public $author_id_course = 0;
	/**
	 * @var int
	 */
	public $section_course_id = 0;
	/**
	 * @var int[]
	 */
	public $section_ids = [];
	/**
	 * @var int[]
	 */
	public $section_not_ids = [];
	/**
	 * @var string
	 */
	public $search_section = '';
}
