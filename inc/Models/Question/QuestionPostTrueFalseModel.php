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

		try {
			$answers = $this->get_answer_option();

			if ( $answers ) {
				foreach ( $answers as $option ) {
					// Convert QuestionAnswerModel to array for easier access
					$data = is_object( $option ) ? get_object_vars( $option ) : $option;

					if ( ( $data['is_true'] == 'yes' || $data['is_true'] === 'yes' ) && $data['value'] == $user_answer ) {
						$return['correct'] = true;
						$return['mark']    = floatval( $this->get_mark() );
						break;
					}
				}
			}
		} catch ( \Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $return;
	}
}
