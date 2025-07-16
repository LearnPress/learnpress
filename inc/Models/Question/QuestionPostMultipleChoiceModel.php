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
class QuestionPostMultipleChoiceModel extends QuestionPostModel {
	public $question_type = 'multi_choice';

	public function create_default_answers() {
	}
}
