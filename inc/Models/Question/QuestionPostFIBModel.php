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

	/**
	 * Create default answers for question
	 *
	 * @return array[]
	 */
	public function get_default_answers(): array {
		return array(
			array(
				'value' => $this->random_value(),
				'title' => esc_html__( 'FIB', 'learnpress' ),
			),
		);
	}
}
