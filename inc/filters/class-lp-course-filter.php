<?php
/**
 * Class LP_Question_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 3.2.9
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Question_Filter' ) ) {
	return;
}

class LP_Course_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = LP_COURSE_CPT;
	/**
	 * @var string Level of Course
	 */
	public $levels = [];
}
