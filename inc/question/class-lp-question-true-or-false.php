<?php

/**
 * LP_Question_True_Or_False
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

if ( ! class_exists( 'LP_Question_True_Or_False ' ) ) {

	/**
	 * Class LP_Question_True_Or_False
	 */
	class LP_Question_True_Or_False extends LP_Question {
		/**
		 * Type of this question.
		 *
		 * @var string
		 */
		protected $_question_type = 'true_or_false';

		/**
		 * Constructor
		 *
		 * @param null $the_question
		 * @param null $args
		 */
		public function __construct( $the_question = null, $args = null ) {
			parent::__construct( $the_question, $args );
		}

		/**
		 * Get true or false default answers.
		 *
		 * @return array
		 */
		public function get_default_answers() {
			$answers = array(
				array(
					'is_true' => 'yes',
					'value'   => 'true',
					'text'    => __( 'True', 'learnpress' )
				),
				array(
					'is_true' => 'no',
					'value'   => 'false',
					'text'    => __( 'False', 'learnpress' )
				)
			);

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
					if ( ( $answer['is_true'] == 'yes' ) && ( $answer['value'] == $user_answer ) ) {
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