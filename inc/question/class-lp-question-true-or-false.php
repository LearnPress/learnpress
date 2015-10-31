<?php

/**
 * LP_Question_True_Or_False
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 * @extends LP_Question
 */
class LP_Question_True_Or_False extends LP_Question {
	/**
	 * Constructor
	 *
	 * @param null $the_question
	 * @param null $args
	 */
	function __construct( $the_question = null, $args = null ) {
		parent::__construct( $the_question, $args );
		add_filter( 'learn_press_question_answers', array( $this, 'limit_answers' ), 10, 2 );
	}

	function limit_answers( $answers = array(), $question ){
		if( $question->type == $this->type ){
			$answers = array_splice( $answers, 0, 2 );
		}
		return $answers;
	}

	function save( $post_data = null ) {
		parent::save( $post_data );
	}

	function submit_answer( $quiz_id, $answer ) {
		$questions = learn_press_get_question_answers( null, $quiz_id );
		if ( !is_array( $questions ) ) $questions = array();
		$questions[$quiz_id][$this->get( 'ID' )] = is_array( $answer ) ? reset( $answer ) : $answer;
		learn_press_save_question_answer( null, $quiz_id, $this->get( 'ID' ), is_array( $answer ) ? reset( $answer ) : $answer );
	}

	function get_default_answers( $answers = false ) {
		if ( !$answers ) {
			$answers = array(
				array(
					'is_true' => 'yes',
					'value'   => 'true',
					'text'    => __( 'True', 'learn_press' )
				),
				array(
					'is_true' => 'no',
					'value'   => 'false',
					'text'    => __( 'False', 'learn_press' )
				)
			);
		}
		return $answers;
	}

	function admin_interface( $args = array() ) {
		ob_start();
		$view = learn_press_get_admin_view( 'meta-boxes/question/single-choice-options.php' );
		include $view;
		$output = ob_get_clean();

		if ( !isset( $args['echo'] ) || ( isset( $args['echo'] ) && $args['echo'] === true ) ) {
			echo $output;
		}
		return $output;
	}

	function render( $args = array() ) {
		$unique_name = uniqid( 'lp_question_answer_' . $this->get( 'ID' ) . '_' );
		$answer      = null;
		$view = learn_press_locate_template( 'question/types/single-choice.php' );
		include $view;
	}

	function save_post_action() {

		if ( $post_id = $this->get( 'ID' ) ) {
			$post_data    = isset( $_POST[LP()->question_post_type] ) ? $_POST[LP()->question_post_type] : array();
			$post_answers = array();
			$post_explain = $post_data[$post_id]['explaination'];
			if ( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ) {

				//if( LP()->question_post_type != get_post_type( $post_id ) ){
				try {
					$ppp = wp_update_post(
						array(
							'ID'         => $post_id,
							'post_title' => $post_data['text'],
							'post_type'  => LP()->question_post_type
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

	function check( $args = false ) {
		$answer = false;
		is_array( $args ) && extract( $args );
		$return = array(
			'correct' => false,
			'mark'    => 0
		);

		if ( is_numeric( $answer ) ) {
			if ( $this->get( 'options.answer.' . $answer . '.is_true' ) ) {
				$return['correct'] = true;
				$return['mark']    = intval( get_post_meta( $this->get( 'ID' ), '_lpr_question_mark', true ) );
			}
		}
		return $return;
	}
}