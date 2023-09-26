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
