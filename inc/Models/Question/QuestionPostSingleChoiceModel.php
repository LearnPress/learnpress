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
	 * @return array[]
	 */
	public function get_default_answers(): array {
		return array(
			array(
				'is_true' => 'yes',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'First option', 'learnpress' ),
			),
			array(
				'is_true' => '',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Second option', 'learnpress' ),
			),
			array(
				'is_true' => '',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Third option', 'learnpress' ),
			),
		);
	}
}
