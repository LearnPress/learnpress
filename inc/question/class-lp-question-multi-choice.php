<?php

/**
 * Class LP_Question_Multi_Choice
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 * @extend  LP_Question
 */

defined( 'ABSPATH' ) || exit();

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

	public function admin_script() {
		parent::admin_script();
		?>
        <script type="text/html" id="tmpl-multi-choice-question-answer">
            <tr class="lpr-disabled">
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <td class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]"
                           value="0"/>
                    <input type="checkbox" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]"
                           value="1"/>
                </td>
                <td>
                    <input class="lpr-answer-text" type="text"
                           name="lpr_question[{{data.question_id}}][answer][text][__INDEX__]" value=""/>
                </td>
                <td align="center" class="lpr-remove-answer">
                    <span class=""><i class="dashicons dashicons-trash"></i></span></td>
            </tr>
        </script>
		<?php
	}

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

	public function get_icon() {
		return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/multiple-choice.png' ), $this ) . '">';
	}

	public function can_check_answer() {
		return true;
	}

	public function show_answer() {

	}

	public function render( $args = null ) {
		$args     = wp_parse_args(
			$args,
			array(
				'answered'   => null,
				'history_id' => 0,
				'quiz_id'    => 0,
				'course_id'  => 0
			)
		);
		$answered = ! empty( $args['answered'] ) ? $args['answered'] : null;
		if ( null === $answered ) {
			$answered = $this->get_user_answered( $args );
		}
		learn_press_get_template( 'content-question/multi-choice/answer-options.php', array( 'question' => $this ) );
	}

	public function check( $user_answer = null ) {
		$return = array(
			'correct' => true,
			'mark'    => floatval( $this->mark )
		);
		settype( $user_answer, 'array' );
		if ( $answers = $this->answers ) {
			foreach ( $answers as $k => $answer ) {
				$correct = false;
				if ( $answer['is_true'] == 'yes' ) {
					/**if( in_array( $answer['value'], $user_answer ) ){
					 * $correct = true;
					 * }*/
					$correct = $this->is_selected_option( $answer, $user_answer );
				} else {
					/*if( ! in_array( $answer['value'], $user_answer ) ){
						$correct = true;
					}*/
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


	/**
	 * Print js template
	 *
	 * @param string $args
	 *
	 * @return mixed
	 */
	public static function admin_js_template( $args = '' ) {
		$args = wp_parse_args( $args, array( 'echo' => true, 'type' => 'multi_choice' ) );
		parent::admin_js_template( $args );
	}

}