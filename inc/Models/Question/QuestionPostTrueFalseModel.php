<?php

namespace LearnPress\Models\Question;

use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuestionPostTrueFalseModel
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.1
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
	 * @param mixed $user_answer
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function check( $user_answer = null ): array {
		$return = parent::check( $user_answer );

		try {
			$answer_models = $this->get_answer_option();
			if ( $answer_models ) {
				foreach ( $answer_models as $option ) {
					if ( $option->is_true === 'yes'
						&& $option->value == $user_answer ) {
						$return = [
							'correct' => true,
							'mark'    => floatval( $this->get_mark() ),
						];
						break;
					}
				}
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $return;
	}
}
