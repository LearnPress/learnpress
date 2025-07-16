<?php

namespace LearnPress\Models\Question;

/**
 * Class QuestionPostTrueFalseModel
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.8.8
 */
class QuestionPostTrueFalseModel extends QuestionPostModel {
	public $question_type = 'true_or_false';

	/**
	 * Create default answers for question
	 *
	 * @return void
	 */
	public function create_default_answers() {
		// Implement logic to create default answers for true/false question.
	}
}
