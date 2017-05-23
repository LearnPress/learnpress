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

    protected $_type = 'multi_choice';
	/**
	 * Construct
	 *
	 * @param mixed
	 * @param array
	 */
	public function __construct( $the_question = null, $options = null ) {
		parent::__construct( $the_question, $options );
	}

	public function submit_answer( $quiz_id, $answer ) {
		$questions = learn_press_get_question_answers( null, $quiz_id );
		if ( ! is_array( $questions ) ) {
			$questions = array();
		}
		$questions[ $quiz_id ][ $this->id ] = is_array( $answer ) ? reset( $answer ) : $answer;
		learn_press_save_question_answer( null, $quiz_id, $this->id, is_array( $answer ) ? reset( $answer ) : $answer );
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
		if ( ! $answers ) {
			if ( $this->id && $this->post->post_status !== 'auto-draft' ) {
				global $wpdb;
				$sql              = $wpdb->prepare( "SELECT * FROM $wpdb->learnpress_question_answers "
				                                    . " WHERE question_id = %d"
				                                    . " ORDER BY `answer_order`", $this->id );
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

	/**
	 * Display admin UI
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function admin_interface( $args = array() ) {
	    return parent::admin_interface($args);
	}

	public function get_icon() {
		return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/multiple-choice.png' ), $this ) . '">';
	}

	public function can_check_answer() {
		return true;
	}

	public function show_answer() {

	}

	private function _admin_enqueue_script( $enqueue = true ) {
		ob_start();
		$key = 'question_' . $this->id;
		?>
        <script type="text/javascript">
            (function ($) {
                var $form = $('#post');

                $form.unbind('learn_press_question_before_update.<?php echo $key;?>').bind('learn_press_question_before_update.<?php echo $key;?>', function () {
                    var $question = $('.lpr-question-multi-choice[data-id="<?php echo $this->get( 'ID' );?>"]');

                    if ($question.length) {
                        var $input = $('.lpr-is-true-answer input[type="checkbox"]:checked', $question);

                        if (0 == $input.length) {
                            var message = $('.lpr-question-title input', $question).val();
                            message += ": " + '<?php _e( 'No answer added to question or you must select at least one the answer is correct!', 'learnpress' );?>';
                            window.learn_press_before_update_quiz_message.push(message);

                            return false;
                        }
                    }
                });
            })(jQuery);
        </script>
		<?php
		$script = ob_get_clean();
		if ( $enqueue ) {
			$script = preg_replace( '!</?script.*>!', '', $script );
			learn_press_enqueue_script( $script );
		} else {
			echo $script;
		}
	}

	public function save_post_action() {

		if ( $post_id = $this->id ) {
			$post_data    = isset( $_POST[ LP_QUESTION_CPT ] ) ? $_POST[ LP_QUESTION_CPT ] : array();
			$post_answers = array();
			//$post_explain = $post_data[$post_id]['explanation'];
			learn_press_debug( $_POST );
			if ( isset( $post_data[ $post_id ] ) && $post_data = $post_data[ $post_id ] ) {
				$post_args = array(
					'ID'         => $post_id,
					'post_title' => $post_data['text'],
					'post_type'  => LP_QUESTION_CPT
				);
				wp_update_post( $post_args );
				$index = 0;
				if ( ! empty( $post_data['answer']['text'] ) ) {
					foreach ( $post_data['answer']['text'] as $k => $txt ) {
						if ( ! $txt ) {
							continue;
						}
						$post_answers[ $index ] = array(
							'text'    => $txt,
							'is_true' => $post_data['answer']['is_true'][ $k ]
						);
						$index ++;
					}
				}
			}
			$post_data['answer'] = $post_answers;
			$post_data['type']   = $this->get_type();
			//$post_data['explanation']  = $post_explain;
			update_post_meta( $post_id, '_lpr_question', $post_data );
		}

		return intval( $post_id );
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
		$view = learn_press_locate_template( 'content-question/multi-choice/answer-options.php' );
		include $view;
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