<?php

namespace LearnPress\Models\Question;

use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuestionPostSingleChoiceModel
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 4.2.9
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
				'order'   => 1,
			),
			array(
				'is_true' => '',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Second option', 'learnpress' ),
				'order'   => 2,
			),
			array(
				'is_true' => '',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Third option', 'learnpress' ),
				'order'   => 3,
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

		try {
			$answer_models = $this->get_answer_option();
			if ( $answer_models ) {
				foreach ( $answer_models as $option ) {
					if ( $option->is_true === 'yes' &&
						$option->value == $user_answer ) {
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
