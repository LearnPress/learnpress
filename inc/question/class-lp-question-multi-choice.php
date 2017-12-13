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
		 *
		 * @throws Exception
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
			ob_start();
			echo "[[[[[[[[[[$this->get_id()";
			settype( $user_answer, 'array' );

			if ( $answers = $this->get_answers() ) {
				$correct = true;
				foreach ( $answers as $key => $option ) {

					// Consider option is true
					$correct  = true;
					$selected = $this->is_selected_option( $option, $user_answer );

					echo 'xxxxx';learn_press_debug($selected, $option, $user_answer);

					// If the option is FALSE but user selected => WRONG
					if ( $selected && $option['is_true'] !== 'yes' ) {
						$correct = false;
						echo "AAAAA";
					} // If option is TRUE but user did not select => WRONG
					elseif ( ! $selected && $option['is_true'] === 'yes' ) {
						$correct = false;
						echo "bbbbbb";
					}

					// Only one option is selected wrong
					if ( ! $correct ) {
						break;
					}
				}
				if ( $correct ) {
					$return = array(
						'correct' => true,
						'mark'    => floatval( $this->get_mark() )
					);
				}
			}
			$output = ob_start();
			if($this->get_id()==10460){
				echo $output;die();
			}
			return $return;
		}

	}
}
