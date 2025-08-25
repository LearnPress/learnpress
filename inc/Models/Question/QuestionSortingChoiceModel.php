<?php

namespace LearnPress\Models\Question;

/**
 * Class QuestionPostTrueFalseModel
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.9
 */
class QuestionSortingChoiceModel extends QuestionPostModel {
	public $question_type = 'sorting_choice';

	/**
	 * Get true or false default answers.
	 *
	 * @return array
	 */
	public function get_default_answers(): array {
		return [
			[
				'is_true' => 'yes',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'First option', 'learnpress' ),
			],
			[
				'is_true' => 'yes',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Second option', 'learnpress' ),
			],
			[
				'is_true' => 'yes',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Third option', 'learnpress' ),
			],
		];
	}
}
