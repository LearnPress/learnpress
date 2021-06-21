<?php
/**
 * LP_Question_Fill_In_Blanks
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 * @extends LP_Question
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Question_Fill_In_Blanks' ) ) {

	/**
	 * Class LP_Question_Fill_In_Blanks
	 */
	class LP_Question_Fill_In_Blanks extends LP_Question {
		/**
		 * Type of this question.
		 *
		 * @var string
		 */
		protected $_question_type = 'fill_in_blanks';

		/**
		 * @var bool
		 */
		protected $_answer_options = false;

		/**
		 * LP_Question_Fill_In_Blanks constructor.
		 *
		 * @param null $the_question
		 * @param null $args
		 *
		 * @throws Exception
		 */
		public function __construct( $the_question = null, $args = null ) {
			parent::__construct( $the_question, $args );

			add_filter(
				'learn-press/question-editor/localize-script',
				array( $this, 'sanitize_question_answers' ),
				1000
			);

			add_action( 'learn-press/before-clear-question', array( $this, 'clear_question_answer_meta' ) );

			add_filter(
				'learn-press/quiz-editor/question-answers-data',
				array( $this, 'admin_editor_question_answers' ),
				10,
				3
			);
			add_filter(
				'learn-press/question-editor/question-answers-data',
				array( $this, 'admin_editor_question_answers' ),
				10,
				3
			);

			add_filter( 'learn-press/question/fib/regex-content', array( $this, 'match_shortcode' ), 10, 4 );
		}

		public function match_shortcode( $content, $answer_id, $show_answer = false, $answered = '' ) {
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

						$fill = ! empty( $atts['fill'] ) ? $atts['fill'] : '';

						if ( $show_answer ) {
							$answer = isset( $answered[ $ids ] ) ? $answered[ $ids ] : '';

							$is_correct = '';

							if ( ! empty( $answer ) ) {
								$blanks = learn_press_get_question_answer_meta( $answer_id, '_blanks', true );

								if ( ! empty( $blanks ) ) {
									$is_correct = $this->check_answer( $blanks[ $ids ], $answer ) ? 'correct' : 'fail';
								}
							}

							$answer_html = ( ! empty( $answer ) && $is_correct === 'fail' ) ? '<span class="lp-fib-answered__answer">' . $answer . '</span> &rarr; ' : '';
							$new_str     = '<span class="lp-fib-answered ' . $is_correct . '" id="' . esc_attr( $ids ) . '">' . $answer_html . '<span class="lp-fib-answered__fill">' . $fill . '</span></span>';
						} else {
							$new_str = '<div class="lp-fib-input" style="display: inline-block; width: auto;"><input type="text" data-id="' . esc_attr( $ids ) . '" value="" /></div>';
						}

						$content = str_replace( $shortcode[0], $new_str, $content );
					}
				}
			}

			return $content;
		}

		public function sanitize_question_answers( $data ) {
			global $post;

			if ( $post && ( $post->ID == $this->get_id() ) && isset( $data['root'] ) ) {
				if ( isset( $data['root']['answers'] ) ) {
					$answers     = array();
					$old_answers = reset( $data['root']['answers'] );

					foreach ( $old_answers as $k => $v ) {
						if ( $k == '0' ) {
							$answers['title'] = str_replace( array( '=\"', '\"' ), array( '="', '"' ), $v );
						} else {
							$answers[ $k ] = $v;
						}
					}
					$data['root']['answers'] = array( $answers );
				}
			}

			return $data;
		}

		public function admin_editor_question_answers( $answers, $question_id, $quiz_id ) {
			$question = learn_press_get_question( $question_id );

			if ( ! $question ) {
				return $answers;
			}

			if ( 'fill_in_blanks' !== $question->get_type() ) {
				return $answers;
			}

			if ( $answers ) {
				foreach ( $answers as $k => $answer ) {
					$blanks                  = learn_press_get_question_answer_meta(
						$answer['question_answer_id'],
						'_blanks',
						true
					);
					$answers[ $k ]['blanks'] = $blanks ? array_values( $blanks ) : array();
				}
			}

			return $answers;
		}

		public function clear_question_answer_meta( $question_id ) {
			$question = LP_Question::get_question( $question_id );
			$answers  = $question->get_answers();

			foreach ( $answers as $answer_id ) {
				learn_press_delete_question_answer_meta( $answer_id, '_blanks', '', true );
			}
		}


		public function get_editor_settings() {
			$blanks = $this->get_data( 'answer_options' );

			global $post;

			return array_merge(
				parent::get_editor_settings(),
				array(
					'id'                 => $post->ID,
					'instantParseBlanks' => true,
					'blankOptions'       => $blanks ? array_values( $blanks ) : array(),
					'blankFillsStyle'    => get_post_meta( $post->ID, '_lp_blank_fills_style', true ),
					'blanksStyle'        => get_post_meta( $post->ID, '_lp_blanks_style', true ),
				)
			);
		}

		/**
		 * Get input name.
		 *
		 * @param $fill
		 *
		 * @return string
		 */
		public function get_input_name( $fill ) {
			return '_' . md5( wp_create_nonce( $fill ) );
		}

		/**
		 * Set text format.
		 *
		 * @param $text
		 *
		 * @return string
		 */
		private function _format_text( $text ) {
			return trim( preg_replace( '!\s+!', ' ', $text ) );
		}

		public function get_default_answers() {
			$default = array(
				array(
					'is_true' => 'yes',
					'value'   => '',
					'title'   => '',
				),
			);

			return $default;
		}

		/**
		 * Check user answer.
		 *
		 * @param null $user_answer
		 *
		 * @return array
		 */
		public function check( $user_answer = null ) {
			$return = parent::check();
			settype( $user_answer, 'array' );
			$answers = $this->get_answers();
			$blanks  = array();

			if ( $answers ) {
				foreach ( $answers as $key => $answer ) {
					if ( '' === learn_press_get_question_answer_meta( $answer->get_id(), '_blanks', true ) ) {
						// $blanks = LP_Addon_Fill_In_Blank::instance()->upgrader->upgrade_question( $this->get_id() );
					} else {
						$blanks = $answer->get_meta( '_blanks' );
					}

					$return = array(
						'correct'  => false,
						'mark'     => 0,
						'blanks'   => array(),
						'answered' => array(),
					);

					if ( $blanks ) {
						$point_per_blank = $this->get_mark() / count( $blanks );

						foreach ( $user_answer as $answer_id => $answer_value ) {
							$answer_id     = trim( $answer_id );
							$blank_correct = false;

							foreach ( $blanks as $blank ) {
								if ( $answer_id === $blank['id'] ) {
									$user_fill     = ! empty( $answer_value ) ? trim( $answer_value ) : '';
									$blank_correct = $this->check_answer( $blank, $user_fill );

									$return['blanks'][ $answer_id ]   = $blank_correct;
									$return['answered'][ $answer_id ] = $user_fill;

									if ( $blank_correct ) {
										$return['mark'] += floatval( $point_per_blank );
									}
								}
							}
						}

						if ( floatval( $return['mark'] ) > 0 ) {
							$return['correct'] = true;
						}
					}
				}

				return $return;
			}
		}

		public function check_answer( $blank, $user_fill ) {
			$fill       = ! empty( $blank['fill'] ) ? trim( $blank['fill'] ) : '';
			$comparison = ! empty( $blank['comparison'] ) ? $blank['comparison'] : false;
			$match_case = ! empty( $blank['match_case'] ) ? ! ! $blank['match_case'] : false;
			$user_fill  = ! empty( $user_fill ) ? trim( $user_fill ) : '';

			if ( empty( $user_fill ) ) {
				return;
			}

			$blank_correct = false;

			switch ( $comparison ) {
				case 'range':
					if ( is_numeric( $user_fill ) ) {
						$fill      = explode( ',', $fill );
						$words     = array_map( 'trim', $fill );
						$words     = array_map( 'floatval', $fill );
						$user_fill = floatval( $user_fill );

						if ( count( $words ) == 2 ) {
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
						$blank_correct = strcmp( $user_fill, $fill ) == 0;
					} else {
						$blank_correct = strcasecmp( $user_fill, $fill ) == 0;
					}
			}

			return $blank_correct;
		}
	}
}

