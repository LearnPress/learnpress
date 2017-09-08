<?php

/**
 * LP_Question_Single_Choice
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 * @extends LP_Question
 */

defined( 'ABSPATH' ) || exit();

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

	public function can_check_answer() {
		return false;
	}

	public function get_icon() {
		return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/single-choice.png' ), $this ) . '">';
	}

	public function admin_script() {
		parent::admin_script();

		return;
		?>
        <script type="text/html" id="tmpl-single-choice-question-answer">
            <tr class="lpr-disabled">
                <td class="lpr-sortable-handle">
                    <i class="dashicons dashicons-sort"></i>
                </td>
                <td class="lpr-is-true-answer">
                    <input type="hidden" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]"
                           value="0"/>
                    <input type="radio" data-group="lpr-question-answer-{{data.question_id}}"
                           name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="1"/>

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
		if ( ! $answers ) {
			if ( $this->get_id() && get_post_status( $this->get_id() ) !== 'auto-draft' ) {
				global $wpdb;
				$sql              = $wpdb->prepare( "SELECT * FROM $wpdb->learnpress_question_answers "
				                                    . " WHERE question_id = %d"
				                                    . " ORDER BY `answer_order`", $this->get_id() );
				$question_answers = $wpdb->get_results( $sql );
				$answers          = array();
				foreach ( $question_answers as $qa ) {
					$answers[] = unserialize( $qa->answer_data );
				}
			}
			if ( ! empty( $answers ) ) {
				return $answers;
			}
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

	public function admin_interface( $args = array() ) {
		return parent::admin_interface( $args );
	}

	public function render( $args = null ) {
//		$args     = wp_parse_args(
//			$args,
//			array(
//				'answered'   => null,
//				'history_id' => 0,
//				'quiz_id'    => 0,
//				'course_id'  => 0
//			)
//		);
//		$answered = ! empty( $args['answered'] ) ? $args['answered'] : null;
//		if ( null === $answered ) {
//			$answered = $this->get_user_answered( $args );
//		}
		$this->set_data( 'answered', $args );
		learn_press_get_template( 'content-question/single-choice/answer-options.php', array( 'question' => $this ) );
	}

	public function check( $user_answer = null ) {
		$return = array(
			'correct' => false,
			'mark'    => 0
		);
		if ( $answers = $this->get_answers() ) {
			foreach ( $answers as $k => $answer ) {
				if ( ( $answer['is_true'] == 'yes' ) && ( $this->is_selected_option( $answer, $user_answer ) /*$answer['value'] == $user_answer*/ ) ) {
					$return['correct'] = true;
					$return['mark']    = floatval( $this->get_mark() );
					break;
				}
			}
		}

		return $return;
	}
}
///