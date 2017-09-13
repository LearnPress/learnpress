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

	public function get_course_id() {
		return $this->get_data( 'ref_id' );
	}

	public function get_result() {
		$quiz      = learn_press_get_quiz( $this->get_item_id() );
		$cache_key = sprintf( 'quiz-%d-%d-%d', $this->get_user_id(), $this->get_course_id(), $this->get_item_id() );
		if ( false === ( $result = wp_cache_get( $cache_key, 'lp-quiz-result' ) ) ) {
			$result = array(
				'questions'        => array(),
				'mark'             => $quiz->get_mark(),
				'user_mark'        => 0,
				'question_wrong'   => 0,
				'question_correct' => 0,
				'question_empty'   => 0
			);
			if ( $questions = $quiz->get_questions() ) {
				foreach ( $questions as $question_id ) {
					$question = LP_Question::get_question( $question_id );
					$answer   = $this->get_question_answer( $question_id );
					$check    = $question->check();

					$check['type'] = $question->get_type();

					if ( $check['correct'] ) {
						$result['question_correct'] ++;
						$result['user_mark'] += array_key_exists( 'mark', $check ) ? floatval( $check['mark'] ) : $question->get_mark();
					} else {
						if ( false === $answer ) {
							$result['question_empty'] ++;
						} else {
							$result['question_wrong'] ++;
						}
					}

					$result['questions'][ $question_id ] = $check;
				}
			}
			wp_cache_set( $cache_key, $result, 'lp-quiz-result' );
		}

		return $result;
	}
}