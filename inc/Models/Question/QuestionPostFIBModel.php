<?php

namespace LearnPress\Models\Question;

use Exception;
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
	 * Get blanks (meta _blanks) from content of editor.
	 * Fill always get from content, because fill client send may be stale (text of blank changed after inserted)
	 * or HTML entities (e.g. H&agrave; nội).
	 * Options match_case, comparison and other keys get from client.
	 * Blank not in content will be removed.
	 *
	 * @param string $content Content of editor, blank format <span class="lp-question-fib-input" data-id="">fill</span>
	 * @param array $client_blanks Blanks client send, key is blank id.
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_blanks_from_editor_content( string $content, array $client_blanks = [] ): array {
		$blanks  = [];
		$pattern = '#<span class="lp-question-fib-input" data-id="([^"]+)">([^<]+)<\/span>#';
		preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$blank_id     = $match[1];
			$client_blank = (array) ( $client_blanks[ $blank_id ] ?? [] );

			$blanks[ $blank_id ] = array_merge(
				$client_blank,
				[
					'id'         => $blank_id,
					'fill'       => html_entity_decode( $match[2], ENT_QUOTES, 'UTF-8' ),
					'match_case' => $client_blank['match_case'] ?? 0,
					'comparison' => ! empty( $client_blank['comparison'] ) ? $client_blank['comparison'] : 'equal',
				]
			);
		}

		return $blanks;
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
	 * @param mixed $blanks Blanks of answer (meta _blanks). Null to read from old table by $answer_id. @since 4.5.0
	 *
	 * @return array
	 */
	public function get_answer_data( string $content, int $answer_id, $answered = '', $blanks = null ): array {
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

					$is_correct    = false;
					$answer_blanks = $blanks ?? learn_press_get_question_answer_meta( $answer_id, '_blanks', true );

					if ( ! empty( $answer_blanks ) ) {
						$is_correct = $this->check_answer( $answer_blanks[ $ids ] ?? array(), $answer );
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
	 * @throws Exception
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

		// Blanks of answers, key same as option question_answer_id (answer id, or value if read from config).
		$blanks_by_answer = [];
		foreach ( $this->get_answers_for_legacy_type() as $answer_model ) {
			if ( $answer_model instanceof QuestionAnswerModel ) {
				if ( $answer_model->is_get_all_metadata ) {
					$blanks_by_answer[ $answer_model->question_answer_id ?? $answer_model->value ] = $answer_model->meta_data;
				}
			} elseif ( is_array( $answer_model ) ) {
				$answer_key = $answer_model['question_answer_id'] ?? $answer_model['value'] ?? '';

				$blanks_by_answer[ $answer_key ] = $this->get_blanks_of_answer( $answer_model );
			}
		}

		if ( $options ) {
			foreach ( $options as $k => $option ) {
				$answer_key = $option['question_answer_id'] ?? 0;
				// Answer read from config has value instead of id.
				$answer_id = is_numeric( $answer_key ) ? (int) $answer_key : 0;
				$title     = $option['title'];
				$blanks    = $blanks_by_answer[ $answer_key ] ?? null;

				$option['ids']       = $this->fib_get_ids( $title );
				$option['title_api'] = wp_strip_all_tags( $this->match_shortcode_api( $title, $answer_id, true, $args['answer'] ) );
				$option['answers']   = $this->get_answer_data( $title, $answer_id, $args['answer'], $blanks );

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
			$answers = $this->get_answers_for_legacy_type();
			if ( $answers ) {
				foreach ( $answers as $answer ) {
					$blanks = $this->get_blanks_of_answer( $answer );
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
	 * Get blanks (meta _blanks) of answer.
	 * Answer read from old tables is QuestionAnswerModel, from config is array.
	 *
	 * @param QuestionAnswerModel|array $answer
	 *
	 * @return mixed
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_blanks_of_answer( $answer ) {
		if ( $answer instanceof QuestionAnswerModel ) {
			return $answer->meta_data;
		}

		if ( is_array( $answer ) ) {
			$meta = (array) ( $answer['meta'] ?? [] );

			return $meta['_blanks'] ?? $answer['meta_data'] ?? null;
		}

		return null;
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
		// Fill may saved as HTML entities (e.g. H&agrave; nội) by TinyMCE, decode to compare with text user typed.
		$fill       = html_entity_decode( (string) ( $blank['fill'] ?? '' ), ENT_QUOTES, 'UTF-8' );
		$fill       = trim( str_replace( "\u{00A0}", ' ', $fill ) );
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
					// mb_strtolower for multibyte chars (e.g. Vietnamese), strtolower only lowers ASCII.
					$words     = array_map(
						function ( $word ) {
							return mb_strtolower( $word, 'UTF-8' );
						},
						$words
					);
					$user_fill = mb_strtolower( $user_fill, 'UTF-8' );
				}

				$blank_correct = in_array( $user_fill, $words, true );
				break;
			default:
				if ( $match_case ) {
					$blank_correct = $user_fill === $fill;
				} else {
					$blank_correct = mb_strtolower( $user_fill, 'UTF-8' ) === mb_strtolower( $fill, 'UTF-8' );
				}
		}

		$data = compact( 'blank', 'user_fill' );

		return apply_filters( 'learn-press/question/fill-in-blank/check_answer', $blank_correct, $data );
	}
}
