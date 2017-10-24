<?php

/**
 * LP_Question_Single_Choice
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 * @extends LP_Question
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Question_Single_Choice' ) ) {
	/**
	 * Class LP_Question_Single_Choice
	 */
	class LP_Question_Single_Choice extends LP_Question {

		/**
		 * Type of this question.
		 *
		 * @var string
		 */
		protected $_question_type = 'single_choice';

		/**
		 * Constructor
		 *
		 * @param mixed
		 * @param array
		 */
		public function __construct( $the_question = null, $args = null ) {
			parent::__construct( $the_question, $args );

		}

		/**
		 * @return bool
		 */
		public function can_check_answer() {
			return false;
		}

		/**
		 * @return string
		 */
		public function get_icon() {
			return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/single-choice.png' ), $this ) . '">';
		}

		/**
		 * Question admin script.
		 */
		public function admin_script() {
			return;
		}

		/**
		 * @param bool $answers
		 *
		 * @return array|bool
		 */
		public function get_default_answers( $answers = false ) {
			if ( ! $answers ) {
				$answers = array(
					array(
						'is_true' => 'yes',
						'value'   => 'option_first',
						'text'    => __( 'Option First', 'learnpress' )
					),
					array(
						'is_true' => 'no',
						'value'   => 'option_seconds',
						'text'    => __( 'Option Seconds', 'learnpress' )
					),
					array(
						'is_true' => 'no',
						'value'   => 'option_third',
						'text'    => __( 'Option Third', 'learnpress' )
					)
				);
			}

			return $answers;
		}

		/**
		 * @param array $args
		 *
		 * @return string
		 */
		public function admin_interface( $args = array() ) {
			return parent::admin_interface( $args );
		}

		/**
		 * Check user answer.
		 *
		 * @param null $user_answer
		 *
		 * @return array
		 */
		public function check( $user_answer = null ) {
			$return = array(
				'correct' => false,
				'mark'    => 0
			);
			if ( $answers = $this->get_answers() ) {
				foreach ( $answers as $k => $answer ) {
					if ( ( $answer['is_true'] == 'yes' ) && ( $this->is_selected_option( $answer, $user_answer ) ) ) {
						$return['correct'] = true;
						$return['mark']    = floatval( $this->get_mark() );
						break;
					}
				}
			}

			return $return;
		}
	}
}
