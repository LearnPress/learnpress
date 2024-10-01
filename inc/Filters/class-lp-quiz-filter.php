<?php
/**
 * Class LP_Quiz_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @since  4.2.7.1
 * @author vuxminhthanh
 * @version 1.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Quiz_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = LP_QUIZ_CPT;
}
