<?php

namespace LearnPress\Models\Question;

use Throwable;
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

	/**
	 * Allow check answer.
	 *
	 * @return bool
	 * @since 4.2.9
	 */
	public function can_check_answer(): bool {
		return true;
	}

	/**
	 * Check user answer.
	 *
	 * @param mixed $user_answer User's answer to check
	 *
	 * @return array Array with 'correct' (bool) and 'mark' (float) keys
	 * @since 4.2.9
	 */
	public function check( $user_answer = null ): array {
		$return = parent::check();
		settype( $user_answer, 'array' );
		$answers = $this->get_answer_option();

		if ( $answers ) {
			$correct = true;

			foreach ( $answers as $option ) {
				// Convert QuestionAnswerModel to array for easier access
				$data     = is_object( $option ) ? get_object_vars( $option ) : $option;
				$selected = $this->is_selected_option( $data, $user_answer );
				$is_true  = ( $data['is_true'] == 'yes' || $data['is_true'] === 'yes' );

				if ( $selected && ! $is_true ) {
					$correct = false;
				} elseif ( ! $selected && $is_true ) {
					$correct = false;
				}

				// Only one option is selected wrong will wrong the answer.
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

		return $return;
	}
}
