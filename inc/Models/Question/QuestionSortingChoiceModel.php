<?php

namespace LearnPress\Models\Question;

/**
 * Class QuestionSortingChoiceModel
 * To replace class LP_Question_Sorting_Choice
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.9
 */
class QuestionSortingChoiceModel extends QuestionPostModel {
	public $question_type = 'sorting_choice';

	/**
	 * Get sorting choice default answers.
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
	 * Get answer options with sorting property and shuffle
	 * Extends parent method to add sorting_choice specific logic
	 *
	 * @param array $args Arguments for filtering (exclude, map, answer)
	 * @return array Array of stdClass objects with sorting property
	 * @since 4.2.9
	 */
	public function get_answer_options( $args = [] ): array {
		// Get base options from parent
		$options = parent::get_answer_options( $args );

		if ( empty( $options ) ) {
			return [];
		}

		// Parse exclude parameter to check if 'is_true' should be excluded
		if ( isset( $args['exclude'] ) && is_string( $args['exclude'] ) ) {
			$exclude = array_map( 'trim', explode( ',', $args['exclude'] ) );
		} else {
			$exclude = $args['exclude'] ?? [];
		}

		$exclude_flip = array_flip( $exclude );

		// Add sorting property to each option
		$i = 0;
		foreach ( $options as $key => $option ) {
			// Only add sorting if 'is_true' is not in exclude list
			if ( ! isset( $exclude_flip['is_true'] ) ) {
				$options[ $key ]->sorting = $i;
			}

			// Remove uid to prevent students from seeing the answer order
			// (uid would sort low to high, revealing correct order)
			if ( isset( $options[ $key ]->uid ) ) {
				unset( $options[ $key ]->uid );
			}

			$i++;
		}

		// Shuffle options until we get a different order than original
		// This ensures the question is not displayed in the correct order
		do {
			$new_options = $this->shuffle( $options );
		} while ( implode( '', array_keys( $options ) ) === implode( '', array_keys( $new_options ) ) );

		return $new_options;
	}

	/**
	 * Shuffle array while preserving keys
	 * Used to randomize answer order for students
	 *
	 * @param array $options Options to shuffle
	 * @return array Shuffled options with preserved keys
	 * @since 4.2.9
	 */
	public function shuffle( array $options ): array {
		$keys = array_keys( $options );
		shuffle( $keys );

		$new_options = [];
		foreach ( $keys as $key ) {
			$new_options[ $key ] = $options[ $key ];
		}

		return $new_options;
	}

	/**
	 * Check user answer for sorting choice question
	 * Answer is correct only if all items are in the correct order
	 *
	 * @param mixed $user_answer Array of answer values in user's order
	 * @return array Array with 'correct' (bool) and 'mark' (float) keys
	 * @since 4.2.9
	 */
	public function check( $user_answer = null ): array {
		// Ensure user_answer is an array
		if ( ! is_array( $user_answer ) ) {
			$user_answer = (array) $user_answer;
		}

		// Initialize return array
		$return = [
			'correct' => true,
			'mark'    => 0,
		];

		// Get original answers in correct order
		$answers = $this->get_answer_option();

		if ( empty( $answers ) ) {
			return [
				'correct' => false,
				'mark'    => 0,
			];
		}

		// Check each answer position
		$position = 0;
		foreach ( $answers as $answer ) {
			$answer_value = $answer->value ?? '';

			// If the value at this position doesn't match, answer is incorrect
			if ( ! isset( $user_answer[ $position ] ) || $answer_value !== $user_answer[ $position ] ) {
				$return['correct'] = false;
				break;
			}

			$position++;
		}

		// Award full mark if correct, zero if incorrect
		if ( $return['correct'] ) {
			$return['mark'] = $this->get_mark();
		} else {
			$return['mark'] = 0;
		}

		return $return;
	}
}

