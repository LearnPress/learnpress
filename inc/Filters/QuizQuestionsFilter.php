<?php
namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuizQuestionsFilter
 *
 * @author  ThimPress
 * @since  4.2.9
 * @version 1.0.0
 */
class QuizQuestionsFilter extends FilterBase {
	const COL_QUIZ_QUESTION_ID = 'quiz_question_id';
	const COL_QUIZ_ID          = 'quiz_id';
	const COL_QUESTION_ID      = 'question_id';
	const COL_QUESTION_ORDER   = 'question_order';
	/**
	 * @var string[]
	 */
	public $all_fields = [
		self::COL_QUIZ_QUESTION_ID,
		self::COL_QUIZ_ID,
		self::COL_QUESTION_ID,
		self::COL_QUESTION_ORDER,
	];
	/**
	 * @var int
	 */
	public $quiz_question_id;
	/**
	 * @var int
	 */
	public $quiz_id;
	/**
	 * @var int
	 */
	public $question_id;
	/**
	 * @var int
	 */
	public $question_order;
	/**
	 * @var int[]
	 */
	public $question_ids = array();
	/**
	 * @var int[]
	 */
	public $quiz_ids = array();
}
