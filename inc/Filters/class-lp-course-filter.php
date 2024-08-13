<?php
/**
 * Class LP_Course_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 3.2.9
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Course_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = LP_COURSE_CPT;
	/**
	 * @var string Level of Course
	 */
	public $levels = [];
	/**
	 * @var string
	 */
	public $taxonomy = 'course_category';
}
