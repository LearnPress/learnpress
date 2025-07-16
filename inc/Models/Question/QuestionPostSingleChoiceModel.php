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
class QuestionPostSingleChoiceModel extends QuestionPostModel {
	public $question_type = 'single_choice';

	/**
	 * Create default answers for question
	 *
	 * @return void
	 */
	public function create_default_answers() {
		// Implement logic to create default answers for single choice question.
	}
}
