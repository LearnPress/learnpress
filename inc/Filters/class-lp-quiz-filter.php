<?php
/**
 * Class LP_Quiz_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @since 4.2.7.6
 * @version 1.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Quiz_Filter' ) ) {
	return;
}

class LP_Quiz_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = LP_QUIZ_CPT;
}
