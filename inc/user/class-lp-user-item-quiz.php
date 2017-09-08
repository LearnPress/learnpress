<?php

/**
 * Class LP_User_Item_Quiz
 */
class LP_User_Item_Quiz extends LP_User_Item {
	/**
	 * @var array
	 */
	protected $_answers = array();

	/**
	 * LP_User_Item_Quiz constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data ) {
		parent::__construct( $data );

		$this->_parse_answers();
	}

	/**
	 *
	 */
	protected function _parse_answers() {
		if ( $answers = learn_press_get_user_item_meta( $this->get_user_item_id(), '_question_answers', true ) ) {
			$this->_answers = $answers;
		}
	}

	public function add_question_answer( $id, $values = null ) {
		if ( is_array( $id ) ) {
			foreach ( $id as $k => $v ) {
				$this->add_question_answer( $k, $v );
			}
		} else {
			$this->_answers[ $id ] = $values;
		}
	}

	public function get_question_answer( $id ) {
		return ! empty( $this->_answers[ $id ] ) ? $this->_answers[ $id ] : false;
	}

	public function update() {
		learn_press_update_user_item_meta( $this->get_user_item_id(), '_question_answers', $this->_answers );
	}

	/**
	 * Get current question ID (quiz).
	 *
	 * @return int
	 */
	public function get_current_question() {
		return learn_press_get_user_item_meta( $this->get_data( 'user_item_id' ), '_current_question', true );
	}

	public function get_result() {
		$quiz = learn_press_get_quiz( $this->get_item_id() );
		if ( false === ( $result = wp_cache_get( '', '' ) ) ) {
			$result = array(
				'questions' => array()
			);
			if ( $questions = $quiz->get_questions() ) {
				foreach ( $questions as $question_id ) {
					$question = LP_Question_Factory::get_question( $question_id );
					$check    = $question->check();

					$check['type'] = $question->get_type();

					$result['questions'][ $question_id ] = $check;
				}
			}
		}

		return $result;
	}
}