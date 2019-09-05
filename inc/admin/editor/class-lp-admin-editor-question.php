<?php

/**
 * Class LP_Admin_Editor_Question
 *
 * @since 3.0.2
 */
class LP_Admin_Editor_Question extends LP_Admin_Editor {

	/**
	 * @var LP_Question
	 */
	protected $question = null;

	/**
	 * @var LP_Question_CURD
	 */
	protected $question_curd = null;

	/**
	 * LP_Admin_Editor_Question constructor.
	 */
	public function __construct() {
	}

	public function dispatch() {
		check_ajax_referer( 'learnpress_admin_question_editor', 'nonce' );

		$args        = wp_parse_args( $_REQUEST, array( 'id' => false, 'type' => '' ) );
		$question_id = $args['id'];
		$question    = LP_Question::get_question( $question_id );

		if ( ! $question ) {
			return false;
		}

		$this->question      = $question;
		$this->question_curd = new LP_Question_CURD();
		$this->result        = array( 'status' => false );

		$this->call( $args['type'], array( $args ) );

		return $this->get_result();
	}

	/**
	 * Get question data in admin question editor.
	 *
	 * @since 3.0.0
	 *
	 * @param $question
	 * @param $object | if true, input in question object, do not need init LP_Question::get_question()
	 *
	 * @return array
	 */
	public function get_question_data_to_question_editor( $question, $object = false ) {

		if ( ! $object ) {
			if ( get_post_type( $question ) !== LP_QUESTION_CPT ) {
				return array();
			}

			// get question
			$question = LP_Question::get_question( $question );
		}

		if ( ! $question ) {
			return array();
		}

		// question id
		$question_id = $question->get_id();

		$data = array(
			'id'      => $question_id,
			'open'    => false,
			'title'   => get_the_title( $question_id ),
			'type'    => array(
				'key'   => $question->get_type(),
				'label' => $question->get_type_label()
			),
			'answers' => is_array( $question->get_data( 'answer_options' ) ) ? array_values( $question->get_data( 'answer_options' ) ) : array()
		);

		return $data;
	}

	/**
	 * Draft question.
	 *
	 * @since 3.0.0
	 *
	 * @param       $question_id
	 * @param array $args
	 *
	 * @return bool|int|LP_Question
	 */
	private function draft_question( $question_id, $args = array() ) {

		if ( get_post_status( $question_id ) != 'auto-draft' ) {
			return false;
		}

		$curd = new LP_Question_CURD();

		$args = wp_parse_args( $args, array(
			'id'             => $question_id,
			'title'          => __( 'New Question', 'learnpress' ),
			'content'        => '',
			'status'         => 'draft',
			'create_answers' => false
		) );

		$question = $curd->create( $args );

		if ( ! $question ) {
			return false;
		}

		return $question;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function change_question_type( $args = array() ) {
		$type = ! empty( $args['question_type'] ) ? $args['question_type'] : false;

		if ( ! $type ) {
			return false;
		}

		$question = $this->question;
		// draft question args
		$args = $args['draft_question'] ? $args['draft_question'] : '';

		if ( $args ) {
			$args  = (array) ( json_decode( wp_unslash( $args ), '' ) );
			$draft = $this->draft_question( $this->question->get_id(), $args );

			// check if draft question false or question exist
			if ( $draft ) {
				$question = $draft;
			}
		}

		if ( isset( $question ) ) {
			// change question type
			$question     = $this->question_curd->change_question_type( $question, $type );
			$this->result = $this->get_question_data_to_question_editor( $question, true );

			return true;
		}

		return false;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function sort_answer( $args = array() ) {
		// answers order
		$order = ! empty( $args['order'] ) ? $args['order'] : false;

		if ( ! $order ) {
			return false;
		}

		// sort answers
		$this->question = $this->question_curd->sort_answers( $this->question->get_id(), $order );

		$this->result = array_values( $this->question->get_data( 'answer_options' ) );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function update_answer_title( $args = array() ) {
		// answers
		$answer = ! empty( $args['answer'] ) ? $args['answer'] : false;
		$answer = json_decode( wp_unslash( $answer ), true );

		if ( ! $answer ) {
			return false;
		}

		// update answer title
		$this->result = $this->question_curd->update_answer_title( $this->question->get_id(), $answer );

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function change_correct( $args = array() ) {
		// correct answer
		$correct = ! empty( $args['correct'] ) ? $args['correct'] : false;
		$correct = json_decode( wp_unslash( $correct ), true );

		if ( ! $correct ) {
			return false;
		}

		// update correct answer
		$this->question = $this->question_curd->change_correct_answer( $this->question, $correct );

		$this->result = $this->_get_answers();

		return true;
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function delete_answer( $args = array() ) {
		// answer id
		$answer_id = ! empty( $args['answer_id'] ) ? $args['answer_id'] : false;

		if ( ! $answer_id ) {
			return false;
		}

		// delete answer
		$this->question_curd->delete_answer( $this->question->get_id(), $answer_id );

		$this->result = $this->_get_answers();

		return true;
	}

	public function _get_answers() {
		$answers = $this->question->get_data( 'answer_options' );

		return $answers ? array_values( $answers ) : array();
	}

	/**
	 * @param array $args
	 *
	 * @return bool
	 */
	public function new_answer( $args = array() ) {
		// new answer
		$answer = LP_Question::get_default_answer();
		// add new
		$this->question_curd->new_answer( $this->question->get_id(), $answer );

		$this->result = $this->_get_answers();

		return true;
	}
}