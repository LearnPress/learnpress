<?php
/**
 * LP_Question_Single_Choice
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
		 * LP_Question_Single_Choice constructor.
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
		 * Check user answer.
		 *
		 * @param null $user_answer
		 *
		 * @return array
		 */
		public function check( $user_answer = null ) {
			$return = parent::check();

			if ( $answers = $this->get_answers() ) {
				foreach ( $answers as $key => $option ) {
					if ( ( $option['is_true'] == 'yes' ) && ( $this->is_selected_option( $option, $user_answer ) ) ) {
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
