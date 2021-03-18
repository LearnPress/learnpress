<?php
/**
 * Question sorting choice question class.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Question_Sorting_Choice' ) ) {
	class LP_Question_Sorting_Choice extends LP_Question {

		/**
		 * @var string
		 */
		protected $_question_type = 'sorting_choice';

		/**
		 * LP_Question_Sorting_Choice constructor.
		 *
		 * @param null $the_question
		 * @param null $args
		 *
		 * @throws Exception
		 */
		public function __construct( $the_question = null, $args = null ) {
			parent::__construct( $the_question, $args );
		}

		/**
		 * Get default question list answers.
		 *
		 * @return array|bool
		 */
		public function get_default_answers() {
			$answers = array(
				array(
					'is_true' => 'yes',
					'value'   => learn_press_random_value(),
					'title'   => esc_html__( 'First option', 'learnpress' ),
				),
				array(
					'is_true' => 'yes',
					'value'   => learn_press_random_value(),
					'title'   => esc_html__( 'Second option', 'learnpress' ),
				),
				array(
					'is_true' => 'yes',
					'value'   => learn_press_random_value(),
					'title'   => esc_html__( 'Third option', 'learnpress' ),
				),
			);

			return $answers;
		}

		public function get_answer_options( $args = array() ) {
			$options = parent::get_answer_options( $args );

			if ( $args['exclude'] && is_string( $args['exclude'] ) ) {
				$exclude = array_map( 'trim', explode( ',', $args['exclude'] ) );
			} else {
				$exclude = $args['exclude'];
			}

			$exclude = array_flip( $exclude );

			if ( ! empty( $options ) ) {
				$i = 0;

				foreach ( $options as $key => $question_option ) {
					if ( ! isset( $exclude['is_true'] ) ) {
						$options[ $key ]['sorting'] = $i;
					}

					unset( $options[ $key ]['uid'] ); // Student can view uid to answer because uid sort low to high so need delete it.
					$i++;
				}

				do {
					$new_options = $this->shuffle( $options );
				} while ( implode( '', array_keys( $options ) ) === implode( '', array_keys( $new_options ) ) );

			}

			return $new_options;
		}

		/** Random array answer */
		public function shuffle( $options ) {
			$uids = array_keys( $options );

			shuffle( $uids );

			foreach ( $uids as $key ) {
				$new_options[ $key ] = $options[ $key ];
			}

			return $new_options;
		}

		/**
		 * Check user answer.
		 *
		 * @param mixed $user_answer
		 * @author Nhamdv - Code is poetry
		 * @return array
		 */
		public function check( $user_answer = null ) {

			/**
			 * @var LP_Question_Answers       $answers
			 * @var LP_Question_Answer_Option $answer
			 *
			 * Check question is clicked in 'show answer' button
			 */
			// if ( $this->_get_checked( $user_answer ) ) {
			// return $this->_get_checked( $user_answer );
			// }

			settype( $user_answer, 'array' );

			$return  = parent::check();
			$answers = parent::get_answers();

			if ( $answers ) {
				$position          = 0;
				$return['correct'] = true;

				foreach ( $answers as $answer ) {
					$answer_value = $answer->get_value();

					if ( $answer_value !== $user_answer[ $position ] ) {
						$return['correct'] = false;
						break;
					}

					$position ++;
				}
			}

			if ( $return['correct'] ) {
				$return['mark'] = $this->get_mark();
			} else {
				$return['mark'] = 0;
			}

			return $return;
		}
	}
}
