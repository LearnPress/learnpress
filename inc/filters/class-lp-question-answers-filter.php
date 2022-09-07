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

class LP_Question_Answers_Filter extends LP_Filter {
	public $field_count = 'question_answer_id';
	/**
	 * @var array
	 */
	public $question_answer_ids = [];
	/**
	 * @var array
	 */
	public $question_ids = [];
	/**
	 * @var string
	 */
	public $title = '';
	/**
	 * @var string
	 */
	public $value = '';
	/**
	 * @var int
	 */
	public $order = 0;
}
