<?php
/**
 * LP_Question_Fill_In_Blanks
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 * @extends LP_Question
 */

/**
 * Prevent loading this file directly
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

			add_filter( 'learn-press/question-editor/localize-script', array( $this, 'sanitize_question_answers' ), 1000 );

			add_action( 'learn-press/question/updated-answer-data', array( $this, 'update_question_answer_meta' ), 10, 3 );
			add_action( 'learn-press/before-clear-question', array( $this, 'clear_question_answer_meta' ) );

			add_filter( 'learn-press/quiz-editor/question-answers-data', array( $this, 'admin_editor_question_answers' ), 10, 3 );
			add_filter( 'learn-press/question-editor/question-answers-data', array( $this, 'admin_editor_question_answers' ), 10, 3 );

			add_filter( 'learn-press/question-editor/i18n', array( $this, 'admin_editor_i18n' ) );
			add_filter( 'learn-press/quiz-editor/i18n', array( $this, 'admin_editor_i18n' ) );
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
					$blanks                  = learn_press_get_question_answer_meta( $answer['question_answer_id'], '_blanks', true );
					$answers[ $k ]['blanks'] = $blanks ? array_values( $blanks ) : array();
				}
			}

			return $answers;
		}

		public function admin_editor_i18n( $i18n ) {
			$i18n['confirm_remove_blanks'] = esc_html__( 'Are you sure to remove all blanks?', 'learnpress' );

			return $i18n;
		}

		public function update_question_answer_meta( $question_id, $answer_id, $answer_data ) {
			if ( ! empty( $answer_data['blanks'] ) ) {
				$blanks = $answer_data['blanks'];
			} else {
				$blanks = '';
			}

			if ( is_array( $blanks ) ) {
				$question = LP_Question::get_question( $question_id );

				foreach ( $blanks as $id => $blank ) {
					$question->_blanks[ $blank['id'] ] = $blank;
				}
			}

			learn_press_update_question_answer_meta( $answer_id, '_blanks', $blanks );
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
			if ( $return = $this->_get_checked( $user_answer ) ) {
				return $return;
			}

			$return = parent::check();
			if ( $this->_blanks && ( $answered = $user_answer ) ) {
				$return['blanks'] = array();
				$point_per_blank  = $this->get_mark() / sizeof( $this->_blanks );
				foreach ( $this->_blanks as $blank ) {
					$uid       = $blank['id'];
					$user_fill = ! empty( $answered[ $uid ] ) ? trim( $answered[ $uid ] ) : false;
					$fill      = trim( $blank['fill'] );

					$comparison    = ! empty( $blank['comparison'] ) ? $blank['comparison'] : false;
					$match_case    = ! empty( $blank['match_case'] ) ? ! ! $blank['match_case'] : false;
					$blank_correct = false;
					switch ( $comparison ) {
						case 'range':
							if ( is_numeric( $user_fill ) ) {
								$fill      = explode( ',', $fill );
								$words     = array_map( 'trim', $fill );
								$words     = array_map( 'floatval', $fill );
								$user_fill = floatval( $user_fill );

								if ( sizeof( $words ) == 2 ) {
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
								$blank_correct = strcmp( $user_fill, $blank['fill'] ) == 0;
							} else {
								$blank_correct = strcasecmp( $user_fill, $blank['fill'] ) == 0;
							}
					}

					$return['blanks'][ $uid ] = $blank_correct;
					if ( $blank_correct ) {
						$return['mark'] += $point_per_blank;
					}
				}

				if ( $return['mark'] ) {
					$return['correct'] = true;
				}

				$answered_value = array_values( $answered );
				$value          = array_filter( $answered_value );

				if ( empty( $value ) ) {
					$return['answered'] = false;
				}
			}

			$this->_set_checked( $return, $user_answer );

			return $return;
		}

		public function get_wp_shortcode_regex() {
			return '/' . get_shortcode_regex( array( 'fib' ) ) . '/';
		}
	}
}

