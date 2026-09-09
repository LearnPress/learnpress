<?php

namespace LearnPress\Models\Question;

use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class QuestionPostMultipleChoiceModel
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

	/**
	 * Check user answer.
	 *
	 * @param mixed $user_answer
	 *
	 * @return array
	 */
	public function check( $user_answer = null ): array {
		$return = parent::check( $user_answer );
		settype( $user_answer, 'array' );

		try {
			$answer_models = $this->get_answer_option();
			if ( $answer_models ) {
				$correct = true;
				foreach ( $answer_models as $option ) {
					$selected = false;
					if ( in_array( $option->value, $user_answer ) ) {
						$selected = true;
					}

					if ( $selected && $option->is_true !== 'yes' ) {
						$correct = false;
					} elseif ( ! $selected && $option->is_true === 'yes' ) {
						$correct = false;
					}

					if ( ! $correct ) {
						break;
					}
				}

				if ( $correct ) {
					$return = [
						'correct' => true,
						'mark'    => floatval( $this->get_mark() ),
					];
				}
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $return;
	}
}
