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

	/**
	 * Calculate result of quiz.
	 *
	 * @param bool $force - Optional. Force to refresh cache.
	 *
	 * @return array|bool|mixed
	 */
	public function get_result( $force = false ) {
		$quiz      = learn_press_get_quiz( $this->get_item_id() );
		$cache_key = sprintf( 'quiz-%d-%d-%d', $this->get_user_id(), $this->get_course_id(), $this->get_item_id() );
		if ( false === ( $result = wp_cache_get( $cache_key, 'lp-quiz-result' ) ) || $force ) {
			$result = array(
				'questions'         => array(),
				'mark'              => $quiz->get_mark(),
				'user_mark'         => 0,
				'question_count'    => 0,
				'question_empty'    => 0,
				'question_answered' => 0,
				'question_wrong'    => 0,
				'question_correct'  => 0
			);
			if ( $questions = $quiz->get_questions() ) {
				foreach ( $questions as $question_id ) {
					$question = LP_Question::get_question( $question_id );
					$answered = $this->get_question_answer( $question_id );
					$check    = $question->check( $answered );

					$check['type']     = $question->get_type();
					$check['answered'] = $answered !== false;

					if ( $check['correct'] ) {
						$result['question_correct'] ++;
						$result['user_mark'] += array_key_exists( 'mark', $check ) ? floatval( $check['mark'] ) : $question->get_mark();
					} else {
						if ( false === $answered ) {
							$result['question_empty'] ++;
						} else {
							$result['question_wrong'] ++;
						}
					}

					$result['questions'][ $question_id ] = $check;
					if ( $check['answered'] ) {
						$result['question_answered'] ++;
					}
				}

				$result['question_count'] = sizeof( $questions );
			}
			wp_cache_set( $cache_key, $result, 'lp-quiz-result' );
		}

		return $result;
	}

	public function is_answered( $question_id ) {
		$result = $this->get_result();
		if ( ! empty( $result['questions'][ $question_id ] ) ) {
			return $result['questions'][ $question_id ]['answered'];
		}

		return false;
	}

	public function is_answered_true( $question_id ) {
		$result = $this->get_result();
		if ( ! empty( $result['questions'][ $question_id ] ) ) {
			return $result['questions'][ $question_id ]['correct'];
		}

		return false;
	}

	/**
	 * Get questions user has answered.
	 *
	 * @param bool $percent - Optional. TRUE will return by percentage with total questions.
	 *
	 * @return float|int|mixed
	 */
	public function get_questions_answered( $percent = false ) {
		$result = $this->get_result();
		if ( $percent ) {
			$return = $result['question_answered'] ? ( $result['question_answered'] / $result['question_count'] ) * 100 : 0;
		} else {
			$return = $result['question_answered'];
		}

		return $return;
	}

	/**
	 * Get total mark user achieved.
	 *
	 * @param bool $percent - Optional. TRUE will return by percentage with total mark.
	 *
	 * @return float|int|mixed
	 */
	public function get_mark( $percent = false ) {
		$result = $this->get_result();
		if ( $percent ) {
			$return = $result['mark'] ? ( $result['user_mark'] / $result['mark'] ) * 100 : 0;
		} else {
			$return = $result['user_mark'];
		}

		return $return;
	}

	public function get_total_questions() {
		$quiz      = learn_press_get_quiz( $this->get_item_id() );
		$questions = $quiz->get_questions();

		return sizeof( $questions );
	}

	public function get_quiz_mark() {
		$result = $this->get_result();

		return $result['mark'];
	}

	public function get_time_remaining() {
		$quiz          = learn_press_get_quiz( $this->get_item_id() );
		$quiz_duration = $quiz->get_duration();
		$diff          = false;
		if ( $quiz_duration && $quiz_duration->get_seconds() >= 0 ) {
			$diff = current_time( 'timestamp' ) - $this->get_start_time()->getTimestamp();
			$diff = $quiz_duration->diff( $diff )->get_seconds();
			if ( $diff <= 0 ) {
				$diff = 0;
			}
		}

		return apply_filters( 'learn-press/quiz/time-remaining', $diff !== false ? new LP_Duration( $diff ) : false, $this->get_item_id(), $this->get_course_id() );
	}
}