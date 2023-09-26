<?php
/**
 * Class LP_Quiz_Questions_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @since  4.1.6
 * @author tungnx
 * @version 1.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Question_Filter' ) ) {
	return;
}

class LP_Question_Answermeta_Filter extends LP_Filter {
	/**
	 * @var int
	 */
	public $question_answer_id = 0;
	/**
	 * @var string
	 */
	public $meta_key = '';
	/**
	 * @var string
	 */
	public $meta_value = '';
}
