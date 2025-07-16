<?php

namespace LearnPress\Models\Question;

/**
 * Class QuestionPostFIBModel
 * Question type Fill in the Blank
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.8.8
 */
class QuestionPostFIBModel extends QuestionPostModel {
	public $question_type = 'fill_in_blanks';

	public function create_default_answers() {
	}
}
