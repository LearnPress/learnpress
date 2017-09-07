<?php

/**
 * Class LP_User_Item_Quiz
 */
class LP_User_Item_Quiz extends LP_User_Item {
	protected $_answers = array();

	public function __construct( $data ) {
		parent::__construct( $data );

		$this->_parse_answers();
	}

	protected function _parse_answers() {
		if ( $answers = learn_press_get_user_item_meta( $this->get_user_item_id(), '_question_answers', true ) ) {

		}
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