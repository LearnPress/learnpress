<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Quiz_Questions_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @since  4.1.6
 * @author tungnx
 * @version 1.0.2
 */
class LP_Question_Answers_Filter extends LP_Filter {
	const COL_QUESTION_ANSWER_ID = 'question_answer_id';
	const COL_QUESTION_ID        = 'question_id';
	const COL_TITLE              = 'title';
	const COL_VALUE              = 'value';
	const COL_ORDER              = 'order';
	const COL_IS_TRUE            = 'is_true';
	/**
	 * @var string[]
	 */
	public $all_fields = [
		self::COL_QUESTION_ANSWER_ID,
		self::COL_QUESTION_ID,
		self::COL_TITLE,
		self::COL_VALUE,
		self::COL_ORDER,
		self::COL_IS_TRUE,
	];
	/**
	 * @var string
	 */
	public $field_count = 'question_answer_id';
	/**
	 * @var int
	 */
	public $question_answer_id;
	/**
	 * @var int
	 */
	public $question_id;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $value;
	/**
	 * @var int
	 */
	public $order;
	/**
	 * @var string
	 */
	public $is_true;
	/**
	 * @var int[]
	 */
	public $question_answer_ids = [];
	/**
	 * @var int[]
	 */
	public $question_ids = [];
}
