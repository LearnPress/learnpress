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
class QuestionPostTrueFalseModel extends QuestionPostModel {
	public $question_type = 'true_or_false';

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
				'title'   => esc_html__( 'True', 'learnpress' ),
				'order'   => 1,
			],
			[
				'is_true' => '',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'False', 'learnpress' ),
				'order'   => 2,
			],
		];
	}
}
