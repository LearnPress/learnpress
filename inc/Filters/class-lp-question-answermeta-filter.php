<?php
/**
 * Class LP_Quiz_Questions_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @since  4.1.6
 * @author tungnx
 * @version 1.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Question_Answermeta_Filter extends LP_Filter {
	const COL_META_ID            = 'meta_id';
	const COL_QUESTION_ANSWER_ID = 'learnpress_question_answer_id';
	const COL_META_KEY           = 'meta_key';
	const COL_META_VALUE         = 'meta_value';
	/**
	 * @var string[] all fields of table
	 */
	public $all_fields = [
		self::COL_META_ID,
		self::COL_QUESTION_ANSWER_ID,
		self::COL_META_KEY,
		self::COL_META_VALUE,
	];
	/**
	 * @var int
	 */
	public $meta_id = 0;
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
