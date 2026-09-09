<?php

namespace LearnPress\Models\Question;

use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuestionPostFIBModel
 * Question type Fill in the Blank
 *
 * @package LearnPress/Classes
 * @version 1.0.1
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
	 * Get FIB ids from content.
	 *
	 * @param string $content
	 *
	 * @return array
	 */
	public function fib_get_ids( string $content ): array {
		$output = array();

		if ( ! empty( $content ) ) {
			preg_match_all(
				'/' . get_shortcode_regex( array( 'fib' ) ) . '/',
				$content,
				$all_shortcode,
				PREG_SET_ORDER
			);

			if ( ! empty( $all_shortcode ) ) {
				foreach ( $all_shortcode as $shortcode ) {
					$atts = shortcode_parse_atts( $shortcode[0] );

					if ( empty( $atts['id'] ) ) {
						$ida = explode( '=', str_replace( ']', '', $atts[1] ) );
						$ids = isset( $ida[1] ) ? str_replace( '"', '', $ida[1] ) : '';
					} else {
						$ids = $atts['id'];
					}

					$output[] = $ids;
				}
			}
		}

		return $output;
	}

	/**
	 * Match shortcode API for FIB.
	 *
	 * @param string $content
	 * @param int $answer_id
	 * @param bool $show_answer
	 * @param mixed $answered
	 *
	 * @return string
	 */
	public function match_shortcode_api( string $content, int $answer_id, bool $show_answer = false, $answered = '' ): string {
		if ( ! empty( $content ) ) {
			preg_match_all(
				'/' . get_shortcode_regex( array( 'fib' ) ) . '/',
				$content,
				$all_shortcode,
				PREG_SET_ORDER
			);

			if ( ! empty( $all_shortcode ) ) {
				foreach ( $all_shortcode as $shortcode ) {
					$atts = shortcode_parse_atts( $shortcode[0] );

					if ( empty( $atts['id'] ) ) {
						$ida = explode( '=', str_replace( ']', '', $atts[1] ) );
						$ids = isset( $ida[1] ) ? str_replace( '"', '', $ida[1] ) : '';
					} else {
						$ids = $atts['id'];
					}

					$new_str = ' {{FIB_' . esc_attr( $ids ) . '}} ';
					$content = str_replace( $shortcode[0], $new_str, $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Get answer data for FIB.
	 *
	 * @param string $content
	 * @param int $answer_id
	 * @param mixed $answered
	 *
	 * @return array
	 */
	public function get_answer_data( string $content, int $answer_id, $answered = '' ): array {
		$output = array();

		if ( ! empty( $content ) ) {
			preg_match_all(
				'/' . get_shortcode_regex( array( 'fib' ) ) . '/',
				$content,
				$all_shortcode,
				PREG_SET_ORDER
			);

			if ( ! empty( $all_shortcode ) ) {
				foreach ( $all_shortcode as $shortcode ) {
					$atts = shortcode_parse_atts( $shortcode[0] );

					if ( empty( $atts['id'] ) ) {
						$ida = explode( '=', str_replace( ']', '', $atts[1] ) );
						$ids = isset( $ida[1] ) ? str_replace( '"', '', $ida[1] ) : '';
					} else {
						$ids = $atts['id'];
					}

					$fill = $atts['fill'] ?? '';

					if ( is_array( $answered ) ) {
						$answer = $answered[ $ids ] ?? '';
					} else {
						$answer = '';
					}

					$is_correct = false;
					$blanks     = learn_press_get_question_answer_meta( $answer_id, '_blanks', true );

					if ( ! empty( $blanks ) ) {
						$is_correct = $this->check_answer( $blanks[ $ids ] ?? array(), $answer );
					}

					$output[ $ids ]['is_correct'] = $is_correct;
					$output[ $ids ]['answer']     = $answer;
					$output[ $ids ]['correct']    = $fill;
				}
			}
		}

		return $output;
	}

	/**
	 * Get answer options for FIB.
	 *
	 * @param array $args
	 *
	 * @return array
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

		$options = parent::get_answer_options();

		if ( $options ) {
			foreach ( $options as $k => $option ) {
				$answer_id = $option['question_answer_id'] ?? 0;
				$title     = $option['title'];

				$option['ids']       = $this->fib_get_ids( $title );
				$option['title_api'] = wp_strip_all_tags( $this->match_shortcode_api( $title, $answer_id, true, $args['answer'] ) );
				$option['answers']   = $this->get_answer_data( $title, $answer_id, $args['answer'] );

				if ( $args['exclude'] && is_string( $args['exclude'] ) ) {
					$exclude = array_map( 'trim', explode( ',', $args['exclude'] ) );
				} else {
					$exclude = $args['exclude'];
				}

				$exclude = array_flip( $exclude );

				foreach ( $args['map'] as $k_map => $v_map ) {
					if ( array_key_exists( $k_map, $option ) ) {
						$option[ $v_map ]  = $option[ $k_map ];
						$exclude[ $k_map ] = 1;
					}
				}

				$options[ $k ] = array_diff_key( $option, $exclude );
			}
		}

		return apply_filters( 'learn-press/question/answer-options', $options, $this->get_id() );
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
				foreach ( $answer_models as $answer_model ) {
					$blanks = $answer_model->meta_data;
					if ( ! is_array( $blanks ) ) {
						$blanks = array();
					}

					$return['blanks']   = array();
					$return['answered'] = array();

					if ( ! empty( $blanks ) ) {
						$total_field_input    = count( $blanks );
						$total_answer_correct = 0;

						foreach ( $user_answer as $answer_id => $answer_value ) {
							$answer_id = trim( $answer_id );

							foreach ( $blanks as $blank ) {
								if ( $answer_id === $blank['id'] ) {
									$user_fill     = trim( $answer_value );
									$blank_correct = $this->check_answer( $blank, $answer_value );

									$return['blanks'][ $answer_id ]   = $blank_correct;
									$return['answered'][ $answer_id ] = $user_fill;

									if ( $blank_correct ) {
										++$total_answer_correct;
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
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $return;
	}

	/**
	 * Check answer fill in blank.
	 *
	 * @param array $blank
	 * @param mixed $user_fill
	 *
	 * @return bool
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
					$words     = array_map( 'trim', explode( ',', $fill ) );
					$words     = array_map( 'floatval', $words );
					$user_fill = floatval( $user_fill );

					if ( count( $words ) == 2 ) {
						$blank_correct = $words[0] <= $user_fill && $user_fill <= $words[1];
					}
				}
				break;
			case 'any':
				$words = array_map( 'trim', explode( ',', $fill ) );

				if ( ! $match_case ) {
					$words     = array_map( 'strtolower', $words );
					$user_fill = strtolower( $user_fill );
				}

				$blank_correct = in_array( $user_fill, $words, true );
				break;
			default:
				if ( $match_case ) {
					$blank_correct = strcmp( $user_fill, $fill ) == 0;
				} else {
					$blank_correct = strcasecmp( mb_strtolower( $user_fill, 'UTF-8' ), mb_strtolower( $fill, 'UTF-8' ) ) == 0;
				}
		}

		$data = compact( 'blank', 'user_fill' );

		return apply_filters( 'learn-press/question/fill-in-blank/check_answer', $blank_correct, $data );
	}
}
