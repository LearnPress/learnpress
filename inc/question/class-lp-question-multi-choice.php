<?php

/**
 * Class LP_Question_Multi_Choice
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 * @extend  LP_Question
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();


if ( ! class_exists( 'LP_Question_Multi_Choice' ) ) {
	/**
	 * Class LP_Question_Multi_Choice
	 */
	class LP_Question_Multi_Choice extends LP_Question {

		/**
		 * Type of this question.
		 *
		 * @var string
		 */
		protected $_question_type = 'multi_choice';

		/**
		 * Construct
		 *
		 * @param mixed
		 * @param array
		 */
		public function __construct( $the_question = null, $options = null ) {
			parent::__construct( $the_question, $options );
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
			return parent::get_default_answers( $answers );
		}

		/**
		 * Display admin UI
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function admin_interface( $args = array() ) {
			return parent::admin_interface( $args );
		}

		/**
		 * @return string
		 */
		public function get_icon() {
			return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/multiple-choice.png' ), $this ) . '">';
		}

		/**
		 * @return bool
		 */
		public function can_check_answer() {
			return true;
		}

		/**
		 * @param null $user_answer
		 *
		 * @return array
		 */
		public function check( $user_answer = null ) {
			$return = array(
				'correct' => true,
				'mark'    => floatval( $this->get_mark() )
			);
			settype( $user_answer, 'array' );
			if ( $answers = $this->get_answers() ) {
				foreach ( $answers as $k => $answer ) {
					if ( $answer['is_true'] == 'yes' ) {
						$correct = $this->is_selected_option( $answer, $user_answer );
					} else {
						$correct = ! $this->is_selected_option( $answer, $user_answer );
					}

					// if the option is TRUE but user did not select it => WRONG
					// or, if the option is FALSE but user selected it => WRONG
					if ( ! $correct ) {
						$return['correct'] = false;
						$return['mark']    = 0;
						break;
					}
				}

			}

			return $return;
		}

	}
}
