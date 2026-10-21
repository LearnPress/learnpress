<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuestionAnswerConfigFilter
 *
 * Filter for table learnpress_question_answer_config
 *
 * @since 4.5.0
 * @version 1.0.0
 */
class QuestionAnswerConfigFilter extends FilterBase {
	const COL_QUESTION_ANSWER_ID = 'question_answer_id';
	const COL_QUESTION_ID        = 'question_id';
	const COL_ANSWERS            = 'answers';
	/**
	 * @var string[]
	 */
	public $all_fields = [
		self::COL_QUESTION_ANSWER_ID,
		self::COL_QUESTION_ID,
		self::COL_ANSWERS,
	];

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
	 * @var int[]
	 */
	public $question_ids = [];
	/**
	 * @var int[]
	 */
	public $question_answer_ids = [];
}
