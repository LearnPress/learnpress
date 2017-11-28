<?php
/**
 * Class LP_Question_Multi_Choice
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
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
		public $_question_type = 'multi_choice';

		/**
		 * LP_Question_Multi_Choice constructor.
		 *
		 * @param null $the_question
		 * @param null $options
		 */
		public function __construct( $the_question = null, $options = null ) {
			parent::__construct( $the_question, $options );
		}

		/**
		 * Allow check answer.
		 *
		 * @return bool
		 */
		public function can_check_answer() {
			return true;
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
			if ( $answers = $this->get_answers() ) {
				foreach ( $answers as $key => $option ) {
					if ( $option['is_true'] == 'yes' ) {
						$correct = $this->is_selected_option( $option, $user_answer );
					} else {
						$correct = ! $this->is_selected_option( $option, $user_answer );
					}

					// if the option is TRUE but user did not select it => WRONG
					// or, if the option is FALSE but user selected it => WRONG
					if ( $correct ) {
						$return = array(
							'correct' => true,
							'mark'    => floatval( $this->get_mark() )
						);
						break;
					}
				}

			}

			return $return;
		}

	}
}
