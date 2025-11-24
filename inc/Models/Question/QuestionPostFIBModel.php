<?php

namespace LearnPress\Models\Question;

/**
 * Class QuestionPostFIBModel
 * Question type Fill in the Blank
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.9
 */
class QuestionPostFIBModel extends QuestionPostModel {
	public $question_type = 'fill_in_blanks';

	/**
	 * Create default answers for question
	 *
	 * @return array[]
	 */
	public function get_default_answers(): array {
		return array(
			array(
				'value' => $this->random_value(),
				'title' => '',
			),
		);
	}

	/**
	 * Convert content to format [fib fill="" id="" ]
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function convert_content_from_editor_to_db( string $content ): string {
		$pattern = '#<span class="lp-question-fib-input" data-id="([^"]+)">([^<]+)<\/span>#';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) {
				$id   = $matches[1];
				$fill = $matches[2];
				return '[fib fill="' . $fill . '" id="' . $id . '" ]';
			},
			$content
		);
	}

	/**
	 * Get FIB IDs from content
	 *
	 * @param string $content Content with [fib] shortcodes
	 *
	 * @return array Array of fib IDs
	 * @since 4.2.9
	 */
	public function fib_get_ids( string $content ): array {
		$shortcodes = $this->parse_fib_shortcodes( $content );
		return array_column( $shortcodes, 'id' );
	}

	/**
	 * Replace FIB shortcodes with placeholder text
	 *
	 * @param string $content Content with [fib] shortcodes
	 * @param int    $answer_id Question answer ID
	 * @param bool   $show_answer Whether to show the answer
	 * @param mixed  $answered User's answered data
	 *
	 * @return string Content with shortcodes replaced
	 * @since 4.2.9
	 */
	public function match_shortcode_api( string $content, int $answer_id, bool $show_answer = false, $answered = '' ): string {
		$shortcodes = $this->parse_fib_shortcodes( $content );
		
		foreach ( $shortcodes as $shortcode ) {
			$new_str = ' {{FIB_' . esc_attr( $shortcode['id'] ) . '}} ';
			$content = str_replace( $shortcode['raw'], $new_str, $content );
		}
		
		return $content;
	}

	/**
	 * Get answer data for FIB question
	 *
	 * @param string $content Content with [fib] shortcodes
	 * @param int    $answer_id Question answer ID
	 * @param mixed  $answered User's answered data
	 *
	 * @return array Answer data with is_correct, answer, and correct keys
	 * @since 4.2.9
	 */
	public function get_answer_data( string $content, int $answer_id, $answered = '' ): array {
		$output     = [];
		$shortcodes = $this->parse_fib_shortcodes( $content );
		$blanks     = learn_press_get_question_answer_meta( $answer_id, '_blanks', true );

		foreach ( $shortcodes as $shortcode ) {
			$id   = $shortcode['id'];
			$fill = $shortcode['fill'];

			// Get user's answer
			if ( is_array( $answered ) ) {
				$answer = $answered[ $id ] ?? '';
			} else {
				$answer = '';
			}

			// Check if answer is correct
			$is_correct = false;
			if ( ! empty( $blanks ) ) {
				$is_correct = $this->check_answer( $blanks[ $id ] ?? [], $answer );
			}

			$output[ $id ]['is_correct'] = $is_correct;
			$output[ $id ]['answer']     = $answer;
			$output[ $id ]['correct']    = $fill;
		}

		return $output;
	}

	/**
	 * Get answer options of the question with FIB-specific processing
	 *
	 * @param array $args Optional. Arguments for filtering and mapping answer options.
	 *
	 * @return array Array of stdClass objects with FIB-specific data
	 * @since 4.2.9
	 */
	public function get_answer_options( $args = [] ): array {
		$args = wp_parse_args(
			$args,
			[
				'exclude' => '',
				'map'     => '',
				'answer'  => '',
			]
		);

		// Parse exclude parameter
		$exclude = $this->parse_exclude_param( $args['exclude'] );
		$map     = $args['map'];

		// Get raw answer options from parent
		$options = $this->get_answer_option();

		// Return empty array if no options
		if ( empty( $options ) || ! is_array( $options ) ) {
			return [];
		}

		// Convert to stdClass objects (same as parent)
		$processed_options = $this->convert_to_stdclass_objects( $options );

		// Apply FIB-specific processing
		if ( ! empty( $processed_options ) ) {
			foreach ( $processed_options as $option ) {
				if ( ! $option instanceof \stdClass ) {
					continue;
				}

				$is_true            = true; // Will be updated if 'is_true' is excluded
				$fib_answer         = ! empty( $args['answer'] ) ? $args['answer'] : '';
				$title              = $option->title ?? '';
				$question_answer_id = $option->question_answer_id ?? 0;

				// Apply FIB-specific transformations as dynamic properties
				$option->title     = apply_filters( 'learn-press/question/fib/regex-content', $title, $question_answer_id, $is_true, $fib_answer );
				$option->ids       = $this->fib_get_ids( $title );
				$option->title_api = wp_strip_all_tags( $this->match_shortcode_api( $title, $question_answer_id, $is_true, $fib_answer ) );
				$option->answers   = $is_true ? $this->get_answer_data( $title, $question_answer_id, $fib_answer ) : [];
			}
		}

		// Apply mapping and exclusion if needed (same as parent)
		if ( ! empty( $exclude ) || ! empty( $map ) ) {
			$processed_options = $this->apply_mapping_and_exclusion_to_objects( $processed_options, $map, $exclude );
		}

		return apply_filters( 'learn-press/question/answer-options', $processed_options, $this->get_id() );
	}

	/**
	 * Check user answer.
	 *
	 * @param mixed $user_answer User's answer to check (array of blank_id => answer_value)
	 *
	 * @return array Array with 'correct' (bool), 'mark' (float), 'blanks' (array), and 'answered' (array) keys
	 * @since 4.2.9
	 */
	public function check( $user_answer = null ): array {
		$return = parent::check();
		settype( $user_answer, 'array' );
		$answers = $this->get_answer_option();

		if ( $answers ) {
			foreach ( $answers as $option ) {
				// Get blanks metadata - stored in the answer's metadata
				$blanks             = null;
				$return['blanks']   = [];
				$return['answered'] = [];

				// Get blanks from QuestionAnswerModel
				if ( $option instanceof QuestionAnswerModel ) {
					$option->get_all_metadata();
					$blanks = $option->meta_data;
				} elseif ( is_object( $option ) && isset( $option->question_answer_id ) ) {
					$blanks = learn_press_get_question_answer_meta( $option->question_answer_id, '_blanks', true );
				} elseif ( is_array( $option ) && isset( $option['question_answer_id'] ) ) {
					$blanks = learn_press_get_question_answer_meta( $option['question_answer_id'], '_blanks', true );
				}

				if ( ! empty( $blanks ) ) {
					$total_field_input    = is_array( $blanks ) ? count( $blanks ) : 0;
					$total_answer_correct = 0;

					foreach ( $user_answer as $answer_id => $answer_value ) {
						$answer_id = trim( $answer_id );

						foreach ( $blanks as $blank ) {
							if ( $answer_id === $blank['id'] ) {
								$user_fill     = trim( $answer_value );
								$blank_correct = $this->check_answer( $blank, $user_fill );

								$return['blanks'][ $answer_id ]   = $blank_correct;
								$return['answered'][ $answer_id ] = $user_fill;

								if ( $blank_correct ) {
									$total_answer_correct++;
								}
							}
						}
					}

					if ( $total_answer_correct === $total_field_input ) {
						$return['correct'] = true;
						$return['mark']    = $this->get_mark();
					}
				}
			}
		}

		$question = $this;
		$data     = compact( 'user_answer', 'answers', 'question' );

		return apply_filters( 'learn-press/question/fill-in-blank/check', $return, $data );
	}

	/**
	 * Check answer fill in blank
	 *
	 * @param array $blank Blank configuration array
	 * @param mixed $user_fill User's fill value
	 *
	 * @return bool True if the answer is correct
	 * @since 4.2.9
	 */
	public function check_answer( array $blank, $user_fill ): bool {
		$fill       = trim( $blank['fill'] ?? '' );
		$comparison = ! empty( $blank['comparison'] ) ? $blank['comparison'] : false;
		$match_case = ! empty( $blank['match_case'] );
		$user_fill  = trim( $user_fill );

		$blank_correct = false;

		switch ( $comparison ) {
			case 'range':
				if ( is_numeric( $user_fill ) ) {
					$fill      = explode( ',', $fill );
					$words     = array_map( 'trim', $fill );
					$words     = array_map( 'floatval', $fill );
					$user_fill = floatval( $user_fill );

					if ( count( $words ) === 2 ) {
						$blank_correct = $words[0] <= $user_fill && $user_fill <= $words[1];
					}
				}
				break;

			case 'any':
				$fill  = explode( ',', $fill );
				$words = array_map( 'trim', $fill );

				if ( ! $match_case ) {
					$words     = array_map( 'strtolower', $words );
					$user_fill = strtolower( $user_fill );
				}

				$blank_correct = in_array( $user_fill, $words );
				break;

			default:
				if ( $match_case ) {
					$blank_correct = strcmp( $user_fill, $fill ) === 0;
				} else {
					$blank_correct = strcasecmp( mb_strtolower( $user_fill, 'UTF-8' ), mb_strtolower( $fill, 'UTF-8' ) ) === 0;
				}
		}

		$data = compact( 'blank', 'user_fill' );

		return apply_filters( 'learn-press/question/fill-in-blank/check_answer', $blank_correct, $data );
	}

	/**
	 * Parse FIB shortcodes from content.
	 *
	 * @param string $content Content with [fib] shortcodes.
	 *
	 * @return array Array of parsed shortcodes with keys: id, fill, raw.
	 * @since 4.2.9
	 */
	private function parse_fib_shortcodes( string $content ): array {
		$parsed = [];

		if ( empty( $content ) ) {
			return $parsed;
		}

		preg_match_all(
			'/' . get_shortcode_regex( [ 'fib' ] ) . '/',
			$content,
			$all_shortcode,
			PREG_SET_ORDER
		);

		if ( empty( $all_shortcode ) ) {
			return $parsed;
		}

		foreach ( $all_shortcode as $shortcode ) {
			$atts = shortcode_parse_atts( $shortcode[0] );

			// Extract ID using consistent logic
			if ( empty( $atts['id'] ) ) {
				$ida = explode( '=', str_replace( ']', '', $atts[1] ) );
				$id  = isset( $ida[1] ) ? str_replace( '"', '', $ida[1] ) : '';
			} else {
				$id = $atts['id'];
			}

			$parsed[] = [
				'id'   => $id,
				'fill' => $atts['fill'] ?? '',
				'raw'  => $shortcode[0],
			];
		}

		return $parsed;
	}
}
