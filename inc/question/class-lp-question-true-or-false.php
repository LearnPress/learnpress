<?php

/**
 * LP_Question_True_Or_False
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 * @extends LP_Question
 */

defined( 'ABSPATH' ) || exit();

class LP_Question_True_Or_False extends LP_Abstract_Question {
	/**
	 * Constructor
	 *
	 * @param null $the_question
	 * @param null $args
	 */
	public function __construct( $the_question = null, $args = null ) {
		parent::__construct( $the_question, $args );
		add_filter( 'learn_press_question_answers', array( $this, 'limit_answers' ), 10, 2 );
	}

	public function limit_answers( $answers = array(), $question ) {
		if( $question->type == $this->type ){
			$answers = array_splice( $answers, 0, 2 );
		}
		return $answers;
	}

	public function save( $post_data = null ) {
		parent::save( $post_data );
	}

	public function submit_answer( $quiz_id, $answer ) {
		$questions = learn_press_get_question_answers( null, $quiz_id );
		if ( !is_array( $questions ) ) $questions = array();
		$questions[$quiz_id][$this->get( 'ID' )] = is_array( $answer ) ? reset( $answer ) : $answer;
		learn_press_save_question_answer( null, $quiz_id, $this->get( 'ID' ), is_array( $answer ) ? reset( $answer ) : $answer );
	}

	public function get_default_answers( $answers = false ) {
		if ( !$answers ) {
			if( $this->id && $this->post->post_status !=='auto-draft' ){
				global $wpdb;
				$sql = $wpdb->prepare( "SELECT * FROM $wpdb->learnpress_question_answers "
						. " WHERE question_id = %d"
						. " ORDER BY `answer_order`", $this->id );
				$question_answers = $wpdb->get_results( $sql );
				$answers = array();
				foreach ( $question_answers as $qa ){
					$answers[]=unserialize( $qa->answer_data );
				}
			}
			if( !empty( $answers ) ) {
				return $answers;
			}
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
		}
		return $answers;
	}

	public function admin_interface( $args = array() ) {
		ob_start();
		$view = learn_press_get_admin_view( 'meta-boxes/question/single-choice-options.php' );
		include $view;
		$output = ob_get_clean();

		if ( !isset( $args['echo'] ) || ( isset( $args['echo'] ) && $args['echo'] === true ) ) {
			echo $output;
		}
		return $output;
	}

	public function render( $args = array() ) {
		$args     = wp_parse_args(
			$args,
			array(
				'answered'   => null,
				'history_id' => 0,
				'quiz_id'    => 0,
				'course_id'  => 0
			)
		);
		$answered = !empty( $args['answered'] ) ? $args['answered'] : null;
		if ( null === $answered ) {
			$answered = $this->get_user_answered( $args );
		}
		$view     = learn_press_locate_template( 'content-question/single-choice/answer-options.php' );
		include $view;
	}

	public function save_post_action() {

		if ( $post_id = $this->get( 'ID' ) ) {
			$post_data    = isset( $_POST[LP_QUESTION_CPT] ) ? $_POST[LP_QUESTION_CPT] : array();
			$post_answers = array();
			$post_explain = $post_data[$post_id]['explaination'];
			if ( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ) {

				//if( LP_QUESTION_CPT != get_post_type( $post_id ) ){
				try {
					$ppp = wp_update_post(
						array(
							'ID'         => $post_id,
							'post_title' => $post_data['text'],
							'post_type'  => LP_QUESTION_CPT
						)
					);
				} catch ( Exception $ex ) {
					echo "ex:";
					print_r( $ex );
				}

				// }else{

				// }

				$index = 0;

				foreach ( $post_data['answer']['text'] as $k => $txt ) {
					$post_answers[$index ++] = array(
						'text'    => $txt,
						'is_true' => $post_data['answer']['is_true'][$k]
					);
				}

			}
			$post_data['answer']       = $post_answers;
			$post_data['type']         = $this->get_type();
			$post_data['explaination'] = $post_explain;
			update_post_meta( $post_id, '_lpr_question', $post_data );
			//print_r($post_data);
		}
		return $post_id;
		// die();
	}

	public function check( $user_answer = null ) {
		$return = array(
			'correct' => false,
			'mark'    => 0
		);
		if ( $answers = $this->answers ) {
			foreach ( $answers as $k => $answer ) {
				if( ( $answer['is_true'] == 'yes' ) && ( $answer['value'] == $user_answer ) ){
					$return['correct'] = true;
					$return['mark'] = floatval( $this->mark );
					break;
				}
			}
		}
		return $return;
	}

	public function admin_js_template() {
		ob_start();
		?>
		<tr class="lp-list-option lp-list-option-new lp-list-option-empty <# if(data.id){ #>lp-list-option-{{data.id}}<# } #>" data-id="{{data.id}}">
			<td>
				<input class="lp-answer-text no-submit key-nav" type="text" name="learn_press_question[{{data.question_id}}][answer][text][]" value="{{data.text}}" />
			</td>
			<th class="lp-answer-check">
				<input type="hidden" name="learn_press_question[{{data.question_id}}][answer][value][]" value="{{data.value}}" />
				<input type="radio" name="learn_press_question[{{data.question_id}}][checked][]" {{data.checked}} value="{{data.value}}" />
			</th>
			<td class="lp-list-option-actions lp-move-list-option open-hand">
				<i class="dashicons dashicons-sort"></i>
			</td>
		</tr>
		<?php
		return apply_filters( 'learn_press_question_true_or_false_answer_option_template', ob_get_clean(), __CLASS__ );
	}
}