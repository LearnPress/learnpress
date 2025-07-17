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
	 * Get true or false default answers.
	 *
	 * @return array
	 */
	public function get_default_answers(): array {
		return [
			[
				'is_true' => 'yes',
				'value'   => 'true',
				'title'   => esc_html__( 'True', 'learnpress' ),
			],
			[
				'is_true' => '',
				'value'   => 'false',
				'title'   => esc_html__( 'False', 'learnpress' ),
			],
		];
	}
}
