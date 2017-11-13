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
			if ( ! has_filter( 'learn-press/question/load-answer-options' ) ) {
				add_filter( 'learn-press/question/load-answer-options', array(
					$this,
					'validate_answer_options'
				), 10, 2 );
			}
			parent::__construct( $the_question, $args );
		}

		/**
		 * Validate answer options for this question type.
		 * This question should have 2 options in any case.
		 *
		 * @param array $answer_options
		 * @param int $id
		 *
		 * @return array
		 */
		public function validate_answer_options( $answer_options, $id ) {

			remove_filter( 'learn-press/question/load-answer-options', array( $this, 'validate_answer_options' ), 10 );

			if ( get_post_meta( $id, '_lp_type', true ) == $this->get_type() ) {
				$size_of_options = $answer_options ? sizeof( $answer_options ) : 0;
				switch ( $size_of_options ) {
					case 0:
					case 1:
						settype( $answer_options, 'array' );
						$answer_options = array_filter( $answer_options );
						for ( $n = 2 - $size_of_options, $i = 0; $i < $n; $i ++ ) {
							$answer_options[] = apply_filters(
								'learn-press/question/default-answer-option-data',
								array(
									'text'               => '',
									'value'              => learn_press_uniqid(),
									'answer_order'       => $i + 1,
									'question_answer_id' => - 1 //fake id
								),
								$id
							);
						}
						break;
					case 2:
						// Great! Do nothing here
						break;
					default:
						$temp           = $answer_options;
						$answer_options = array();
						foreach ( $temp as $k => $v ) {
							$answer_options[ $k ] = $v;
							if ( sizeof( $answer_options ) == 2 ) {
								break;
							}
						}
				}
			}

			return $answer_options;
		}

		/**
		 * @param bool $answers
		 *
		 * @return array|bool
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