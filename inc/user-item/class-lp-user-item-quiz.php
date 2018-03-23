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
		$this->_curd = new LP_Quiz_CURD();
		parent::__construct( $data );

		$this->_parse_answers();
	}

	/**
	 *
	 */
	protected function _parse_answers() {
		foreach ( array( '_question_answers', 'question_answers' ) as $k ) {
			if ( $answers = learn_press_get_user_item_meta( $this->get_user_item_id(), $k, true ) ) {
				$this->_answers = $answers;
				break;
			}
		}
	}

	public function add_question_answer( $id, $values = null ) {
		if ( is_array( $id ) ) {
			foreach ( $id as $k => $v ) {
				if ( is_object( $v ) ) {
					$v = array( $v );
				}
				$this->add_question_answer( $k, $v );
			}
		} else {
			// User has not used checking answer feature
			if ( ! $this->get_user()->has_checked_answer( $id, $this->get_id(), $this->get_course_id() ) ) {
				$this->_answers[ $id ] = $values;
				$this->set_meta( '_question_answers', $this->_answers );
			}
		}
	}

	public function get_question_answer( $id ) {
		return ! empty( $this->_answers[ $id ] ) ? $this->_answers[ $id ] : false;
	}

	public function update() {
		parent::update();

		learn_press_update_user_item_meta( $this->get_user_item_id(), '_question_answers', $this->_answers );
	}

	/**
	 * @return bool|LP_Quiz
	 */
	public function get_quiz() {
		return learn_press_get_quiz( $this->get_item_id() );
	}

	/**
	 * Get current question ID (quiz).
	 *
	 * @param string $return - Optional.
	 *
	 * @return int|LP_Question
	 */
	public function get_current_question( $return = '' ) {
		$question = $this->get_meta( '_current_question', true );
		if ( ! $question ) {
			if ( $questions = $this->get_quiz()->get_questions() ) {
				$question = reset( $questions );
				$this->set_meta( '_current_question', $question );
				$this->update_meta();
			}
		}
		if ( $question ) {
			if ( $return == 'object' ) {
				$question = learn_press_get_question( $question );
			}
		}

		return $question;
	}

	/**
	 * Get ID of the course that this item assigned to.
	 *
	 * @return array|mixed
	 */
	public function get_course_id() {
		return $this->get_data( 'ref_id' );
	}

	public function get_result( $prop = 'result', $force = false ) {
		return $this->get_results( $prop, $force );
	}

	/**
	 * Calculate result of quiz.
	 *
	 * @param string $prop
	 * @param bool   $force - Optional. Force to refresh cache.
	 *
	 * @return array|bool|mixed
	 */
	public function get_results( $prop = 'result', $force = false ) {
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
				'question_correct'  => 0,
				'status'            => $this->get_status(),
				'grade'             => '',
				'result'            => 0,
				'time_spend'        => $this->get_time_interval( 'display' ),
				'retake_count'      => 0
			);

			if ( $questions = $quiz->get_questions() ) {
				foreach ( $questions as $question_id ) {
					$question = LP_Question::get_question( $question_id );
					$answered = $this->get_question_answer( $question_id );

					$check = apply_filters( 'learn-press/quiz/check-question-result', $question->check( $answered ), $question_id, $this );

					$check['type']     = $question->get_type();
					$check['answered'] = $answered !== false;

					if ( false !== $answered && $check['correct'] ) {
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
				$percent          = $result['mark'] ? ( $result['user_mark'] / $result['mark'] ) * 100 : 0;
				$result['result'] = $percent;
				$result['grade']  = $this->get_status() === 'completed' ? ( $percent >= $this->get_quiz()->get_data( 'passing_grade' ) ? 'passed' : 'failed' ) : '';

				$result['question_count'] = sizeof( $questions );

				if ( false === learn_press_get_user_item_meta( $this->get_user_item_id(), 'grade', true ) ) {
					learn_press_update_user_item_meta( $this->get_user_item_id(), 'grade', $result['grade'] );
				}
			}
			wp_cache_set( $cache_key, $result, 'lp-quiz-result' );
		}

		return $prop && $result && array_key_exists( $prop, $result ) ? $result[ $prop ] : $result;
	}

	public function is_passed() {
		return $this->get_results( 'grade' ) === 'passed';
	}

	public function get_percent_result( $decimal = 1 ) {
		return apply_filters( 'learn-press/user/quiz-percent-result', sprintf( '%s%%', round( $this->get_results( 'result' ), $decimal ), $this->get_user_id(), $this->get_item_id() ) );
	}

	public function get_time_interval( $context = '' ) {
		$interval = parent::get_time_interval();
		if ( $context == 'display' ) {
			$quiz = $this->get_quiz();
			if ( $interval && $quiz->get_duration() ) {
				$interval = new LP_Duration( $interval );
				$interval = $interval->to_timer();
			} else {
				$interval = '--:--';
			}
		}

		return $interval;
	}


	public function is_answered( $question_id ) {
		$result = $this->get_results();
		if ( ! empty( $result['questions'][ $question_id ] ) ) {
			return $result['questions'][ $question_id ]['answered'];
		}

		return false;
	}

	public function is_answered_true( $question_id ) {
		$result = $this->get_results( false );

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
		$result = $this->get_results();
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
		$result = $this->get_results();
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
		$result = $this->get_results();

		return $result['mark'];
	}

	/**
	 * Return time remaining.
	 *
	 * @return LP_Duration
	 */
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

		$remaining = $diff !== false ? new LP_Duration( $diff ) : false;

		return apply_filters( 'learn-press/quiz/time-remaining', $remaining, $this->get_item_id(), $this->get_course_id() );
	}

	/**
	 * Get all questions user has already used "Check"
	 *
	 * @return array
	 */
	public function get_checked_questions() {
		$value = $this->get_meta( '_lp_question_checked', true );
		if ( $value ) {
			$value = (array) $value;
		} else {
			$value = array();
		}

		return $value;
	}

	/**
	 * Return true if user has already checked a question.
	 *
	 * @param int $question_id
	 *
	 * @return bool
	 */
	public function has_checked_question( $question_id ) {
		return in_array( $question_id, $this->get_checked_questions() );
	}

	/**
	 * @param int $question_id
	 *
	 * @return int|WP_Error
	 */
	public function check_question( $question_id ) {
		try {
			$checked = false;
			if ( ( $remain = $this->can_check_answer() ) && ( ! $checked = $this->has_checked_question( $question_id ) ) ) {
				$checked   = $this->get_checked_questions();
				$checked[] = $question_id;
				$this->read_meta();
				$this->set_meta( '_lp_question_checked', $checked );
				$this->update_meta();
				$remain --;
			} else {
				if ( ! $remain ) {
					throw new Exception( __( 'Check question has reached limit.', 'learnpress' ), 1000 );
				} elseif ( $checked ) {
					throw new Exception( __( 'You have already checked this question.', 'learnpress' ), 1010 );
				}
			}
		}
		catch ( Exception $ex ) {
			return new WP_Error( $ex->getCode(), $ex->getMessage() );
		}

		return $remain;
	}

	/**
	 * @param int $question_id
	 *
	 * @return int
	 */
	public function hint( $question_id ) {
		if ( ( $remain = $this->can_hint_answer() ) /*&& ! $this->has_hint_question( $question_id ) */ ) {
			if ( ! $this->has_hinted_question( $question_id ) ) {
				$checked   = $this->get_hint_questions();
				$checked[] = $question_id;
				$this->set_meta( '_lp_question_hint', $checked );
			}
			$count = $this->get_count_hint();
			//$this->set_meta( '_lp_hint_count', ++ $count );
			$this->update_meta();
			$remain --;
		} else {
			return false;
		}

		return $remain;
	}

	/**
	 * Return true if user has already checked a question.
	 *
	 * @param int $question_id
	 *
	 * @return bool
	 */
	public function has_hinted_question( $question_id ) {
		return in_array( $question_id, $this->get_hint_questions() );
	}

	public function get_check_answer_count() {
		return @sizeof( $this->get_checked_questions() );// absint( $this->get_meta( '_lp_check_answer_count' ) );
	}

	public function can_check_answer() {
		$quiz = learn_press_get_quiz( $this->get_item_id() );

		$value = $quiz->get_show_check_answer();
		if ( ! is_numeric( $value ) ) {
			$can = $value === 'yes';
		} else {
			$value = intval( $value );
			if ( $value == 0 ) {
				$can = false;
			} elseif ( $value < 0 ) {
				$can = true;
			} else {
				$checked = $this->get_check_answer_count();
				$can     = $value - $checked;
			}
		}

		return apply_filters( 'learn-press/user-quiz/can-check-answer', $can, $this->get_item_id(), $this->get_course_id() );
	}

	/**
	 * Get all questions user has already used "Check"
	 *
	 * @return array
	 */
	public function get_hint_questions() {
		$value = $this->get_meta( '_lp_question_hint', true );
		if ( $value ) {
			$value = (array) $value;
		} else {
			$value = array();
		}

		return $value;
	}

	public function get_count_hint() {
		return @sizeof( $this->get_hint_questions() );// intval( $this->get_meta( '_lp_hint_count' ) );
	}

	/**
	 * Return true if check answer is enabled.
	 *
	 * @return bool
	 */
	public function can_hint_answer() {
		$quiz = learn_press_get_quiz( $this->get_item_id() );

		$value = $quiz->get_show_hint();
		if ( ! is_numeric( $value ) ) {
			$can = ( $value === 'yes' );
		} else {
			$value = intval( $value );
			if ( $value == 0 ) {
				$can = false;
			} elseif ( $value < 0 ) {
				$can = true;
			} else {
				$hint = $this->get_count_hint();
				$can  = $value - $hint;
			}
		}

		return apply_filters( 'learn-press/user-quiz/can-hint-answer', $can, $this->get_id(), $this->get_course_id() );
	}

	public function finish() {
		$this->set_end_time( current_time( "mysql" ) );
		$this->set_status( 'completed' );
		$this->update();
	}

	public function is_review_questions() {
		return LP_Global::quiz_question() && ( $this->get_status() === 'completed' );
	}

	public function can_retake_quiz() {
		return $this->get_user()->can_retake_quiz( $this->get_id(), $this->get_course() );
	}

	public function redo() {
		$course_id = $this->_get_course( $course_id );

		$user_quiz  = $this->get_item_data( $quiz_id, $course_id );
		$start_time = new LP_Datetime( current_time( 'mysql' ) );

		$return = learn_press_update_user_item_field(
			array(
				'user_id'        => learn_press_get_current_user_id(),
				'item_id'        => $quiz_id,
				'ref_id'         => $course_id,
				'start_time'     => $start_time,
				'start_time_gmt' => $start_time->toSql( false ),
				'item_type'      => 'lp_quiz',
				'status'         => 'started',
				'ref_type'       => 'lp_course',
				'parent_id'      => $user_quiz ? $user_quiz->get_parent() : 0
			)
		);
		if ( $return ) {

			// Update user quiz meta data
			learn_press_update_user_item_meta( $return, 'questions', $questions );
			learn_press_update_user_item_meta( $return, 'current_question', $question );
			learn_press_update_user_item_meta( $return, 'question_answers', array() );
			LP_Cache::flush();
			$response = $this->get_quiz_results( $quiz_id, $course_id, true );
		}
		do_action( 'learn_press_user_retake_quiz', $response, $quiz_id, $course_id, $this->get_id() );
	}
}