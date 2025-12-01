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
class QuestionPostMultipleChoiceModel extends QuestionPostModel {
	public $question_type = 'multi_choice';

	public function get_default_answers(): array {
		return array(
			array(
				'is_true' => 'yes',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'First option', 'learnpress' ),
				'order'   => 1,
			),
			array(
				'is_true' => '',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'Second option', 'learnpress' ),
				'order'   => 2,
			),
			array(
				'is_true' => '',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'Third option', 'learnpress' ),
				'order'   => 3,
			),
			array(
				'is_true' => '',
				'value'   => learn_press_random_value(),
				'title'   => esc_html__( 'Four option', 'learnpress' ),
				'order'   => 4,
			),
		);
	}
}
