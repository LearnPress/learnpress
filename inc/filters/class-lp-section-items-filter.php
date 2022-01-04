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

if ( class_exists( 'LP_Section_Items_Filter' ) ) {
	return;
}

class LP_Section_Items_Filter extends LP_Filter {
	/**
	 * @var int
	 */
	public $search_title = '';
	/**
	 * @var int[]
	 */
	public $item_ids = [];
	/**
	 * @var int[]
	 */
	public $item_not_ids = [];
}
