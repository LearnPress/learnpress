<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class CourseSectionFilter
 *
 * Refactor of LP_Section_Filter
 *
 * @version 4.1.4.2
 */
class CourseSectionFilter extends FilterBase {
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

	public $section_id;
	/**
	 * @var int
	 */
	public $section_course_id;
	/**
	 * @var string
	 */
	public $section_name;
	/**
	 * @var string
	 */
	public $section_description;
	/**
	 * @var int
	 */
	public $section_order;
	/**
	 * @var int[]
	 */
	public $section_ids = [];
}
