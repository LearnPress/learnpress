<?php
/**
 * Class LP_Question_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 3.2.8
 * @author tungnx
 * @version 1.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Question_Filter' ) ) {
	return;
}

class LP_Question_Filter extends LP_Post_Type_Filter {
	/**
	 * @var string
	 */
	public $post_type = 'lp_question';
}
