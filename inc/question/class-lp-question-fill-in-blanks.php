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
			//$this->add_support('answer-options');
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
					if ( ( $option['is_true'] == 'yes' ) && ( $option['value'] == $user_answer ) ) {
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

