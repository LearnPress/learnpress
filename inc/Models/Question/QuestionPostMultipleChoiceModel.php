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

	public function get_default_answers(): array {
		return array(
			array(
				'is_true' => 'yes',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'First option', 'learnpress' ),
			),
			array(
				'is_true' => '',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'Second option', 'learnpress' ),
			),
			array(
				'is_true' => '',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'Third option', 'learnpress' ),
			),
			array(
				'is_true' => '',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'Four option', 'learnpress' ),
			),
		);
	}
}
