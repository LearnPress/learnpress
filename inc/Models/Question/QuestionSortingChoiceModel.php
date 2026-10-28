<?php

namespace LearnPress\Models\Question;

use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class QuestionSortingChoiceModel
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 4.2.9
 */
class QuestionSortingChoiceModel extends QuestionPostModel {
	public $question_type = 'sorting_choice';

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
				'title'   => esc_html__( 'First option', 'learnpress' ),
			],
			[
				'is_true' => 'yes',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Second option', 'learnpress' ),
			],
			[
				'is_true' => 'yes',
				'value'   => $this->random_value(),
				'title'   => esc_html__( 'Third option', 'learnpress' ),
			],
		];
	}

	/**
	 * Check user answer.
	 * User answer is list values of options by order user sorted,
	 * correct when same order of answers (order ASC).
	 *
	 * @param mixed $user_answer
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function check( $user_answer = null ): array {
		$return = parent::check( $user_answer );
		settype( $user_answer, 'array' );
		$user_answer = array_values( $user_answer );

		try {
			$answer_models = $this->get_answers_for_legacy_type();
			// Answers read from config are array, map to model to sort by order.
			if ( ! empty( $answer_models ) && is_array( reset( $answer_models ) ) ) {
				$answer_models = $this->normalize_config_answers_to_model_legacy( $answer_models );
			}

			if ( empty( $answer_models ) || count( $answer_models ) !== count( $user_answer ) ) {
				return $return;
			}

			$position = 0;
			foreach ( $answer_models as $option ) {
				if ( ! $option instanceof QuestionAnswerModel || ! is_scalar( $user_answer[ $position ] ) ) {
					return $return;
				}

				if ( (string) $option->value !== (string) $user_answer[ $position ] ) {
					return $return;
				}

				++$position;
			}

			$return = [
				'correct' => true,
				'mark'    => floatval( $this->get_mark() ),
			];
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $return;
	}

	/**
	 * Get answer options as array, random order.
	 * Add key 'sorting' (correct position) when include is_true, JS use it to show correct/incorrect.
	 * Same as LP_Question_Sorting_Choice::get_answer_options() old.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_answer_options( $args = [] ): array {
		$options = array_values( parent::get_answer_options( $args ) );
		if ( empty( $options ) ) {
			return $options;
		}

		$exclude = $args['exclude'] ?? '';
		if ( is_string( $exclude ) ) {
			$exclude = array_map( 'trim', explode( ',', $exclude ) );
		}
		$include_sorting = ! in_array( 'is_true', (array) $exclude, true );

		foreach ( $options as $position => $option ) {
			if ( $include_sorting ) {
				$options[ $position ]['sorting'] = $position;
			}

			// Student can know correct order by uid (answer id sort low to high), so remove it.
			unset( $options[ $position ]['uid'] );
		}

		return $this->shuffle_options( $options );
	}

	/**
	 * Random order of options, different from original order when has more than one option.
	 *
	 * @param array $options
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function shuffle_options( array $options ): array {
		$count = count( $options );
		if ( $count < 2 ) {
			return $options;
		}

		$new_options = $options;
		for ( $i = 0; $i < 10 && $new_options === $options; $i++ ) {
			shuffle( $new_options );
		}

		// Options same value (rare), shuffle can't change order, move first option to last.
		if ( $new_options === $options ) {
			$new_options[] = array_shift( $new_options );
		}

		return $new_options;
	}

	/*public function check_answers_valid_before_save( $answers ) {

	}*/
}
