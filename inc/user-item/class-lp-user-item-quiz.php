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
			$answers = learn_press_get_user_item_meta( $this->get_user_item_id(), $k, true );

			if ( $answers ) {
				$this->_answers = $answers;
				break;
			}
		}
	}

			/**
			 * Add user answer to DB.
			 *
			 * @param int|array $id
			 * @param mixed     $values
			 *
			 * @return array|bool|LP_Quiz_Results|mixed
			 */
			public function add_question_answer( $id, $values = null ) {
				$results = $this->get_results( '' );

				if ( ! $results ) {
					return false;
				}

				$questions = $results->get( 'questions', array() );

				if ( is_numeric( $id ) ) {
					$values = array( $id => $values );
				} else {
					$values = (array) $id;
				}

				foreach ( $values as $id => $answer ) {
					if ( ! $this->has_checked_question( $id ) ) {
						if ( ! empty( $questions[ $id ] ) ) {
							$questions[ $id ]['answered'] = $answer;
						} else {
							$questions[ $id ] = array( 'answered' => $answer );
						}
					}
				}

				$results['questions'] = $questions;

				LP_User_Items_Result_DB::instance()->update( $this->get_user_item_id(), wp_json_encode( $results->get() ) );

				$this->calculate_results();

				$cache_key = sprintf( 'quiz-%d-%d-%d', $this->get_user_id(), $this->get_course_id(), $this->get_item_id() );

				LP_Object_Cache::set( $cache_key, false, 'learn-press/quiz-result' );

				return $this->get_results( '' );
			}

	public function get_question_answer( $id ) {
		$results = $this->get_results( '' );

		if ( ! $results ) {
			return false;
		}

		$questions = $results->get( 'questions', false );

		if ( $questions && is_array( $questions[ $id ] ) ) {
			return $questions[ $id ]['answered'];
		}

		return false;
	}

	/**
	 * Update data to database
	 *
	 * @param bool $force
	 * @param bool $wp_error
	 *
	 * @return bool|mixed
	 */
	public function update( $force = false, $wp_error = false ) {
		$return = parent::update( $force, $wp_error );
		$this->calculate_results();

		return $return;
	}

	/**
	 * Get list of data to update to database
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public function get_mysql_data() {
		$columns = parent::get_mysql_data();

		return apply_filters( 'learn-press/update-user-item-quiz-data', $columns, $this->get_item_id(), $this->get_course_id(), $this->get_user_id() );
	}

	/**
	 * @return bool|LP_Quiz
	 */
	public function get_quiz() {
		return learn_press_get_quiz( $this->get_item_id() );
	}

	public function get_status_label( $status = '' ) {
		$statuses = array(
			'started'     => __( 'In Progress', 'learnpress' ),
			'in-progress' => __( 'In Progress', 'learnpress' ),
			'completed'   => __( 'Completed', 'learnpress' ),
			'passed'      => __( 'Passed', 'learnpress' ),
			'failed'      => __( 'Failed', 'learnpress' ),
		);

		if ( ! $status ) {
			$status = $this->get_status();
		}

		return ! empty( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'Not Started', 'learnpress' );
	}

	/**
	 * Get current question ID (quiz).
	 *
	 * @param string $return - Optional.
	 *
	 * @return int|LP_Question
	 */
	public function get_current_question( $return = '' ) {
		_deprecated_function( sprintf( '%s::%s', __CLASS__, __FUNCTION__ ), '4.0.0' );

		learn_press_error_log( sprintf( 'Deprecated %s::%s', __CLASS__, __FUNCTION__ ) );
	}

	/**
	 * Get ID of the course that this item assigned to.
	 *
	 * @return array|mixed
	 */
	public function get_course_id() {
		return $this->get_data( 'ref_id' );
	}

	/**
	 * @deprecated
	 *
	 * @param string $prop
	 * @param bool   $force
	 *
	 * @return array|bool|mixed
	 */
	public function get_result( $prop = 'result', $force = false ) {
		return $this->get_results( $prop, $force );
	}

	/**
	 * Calculate result of quiz.
	 *
	 * @param string $prop
	 * @param bool   $force - Optional. Force to refresh cache.
	 *
	 * @return LP_Quiz_Results|array|bool
	 */
	public function get_results( $prop = 'result', $force = false ) {
		if ( in_array( $this->get_status(), array( '', 'viewed' ) ) ) {
			return false;
		}

		$cache_key = sprintf( 'quiz-%d-%d-%d', $this->get_user_id(), $this->get_course_id(), $this->get_item_id() );
		$result    = LP_Object_Cache::get( $cache_key, 'learn-press/quiz-result' );

		if ( false === $result || $force ) {
			$result = $this->_get_results();

			if ( false === $result ) {
				$result = $this->calculate_results();
			}

			LP_Object_Cache::set( $cache_key, $result, 'learn-press/quiz-result' );
		}

		$result['user_item_id']   = $this->get_user_item_id();
		$result['interval']       = array( $this->get_start_time(), $this->get_end_time() );
		$result['graduation']     = $this->get_graduation();
		$result['graduationText'] = $this->get_graduation_text();
		$result                   = new LP_Quiz_Results( $result );

		return $prop ? $result[ $prop ] : $result;
	}

	/**
	 * Get user quiz graduation. [passed, failed, null]
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_graduation() {
		return apply_filters( 'learn-press/user-quiz-graduation', $this->get_data( 'graduation' ), $this->get_item_id(), $this->get_course_id(), $this->get_user() );
	}

	/**
	 * Get user quiz graduation text for displaying purpose. [Passed, Failed, null]
	 *
	 * @since 4.0.0
	 *
	 * @return mixed
	 */
	public function get_graduation_text() {
		$graduation = $this->get_graduation();

		return apply_filters( 'learn-press/user-quiz-graduation-text', learn_press_get_graduation_text( $graduation ) );
	}

	/**
	 * Get all attempts of a quiz.
	 *
	 * @param string $args
	 *
	 * @return array
	 */
	public function get_attempts( $limit = 3 ) {
		$limit = isset( $limit ) ? $limit : 3;

		$limit = absint( apply_filters( 'lp/quiz/get-attempts/limit', $limit ) );

		$results = LP_User_Items_Result_DB::instance()->get_results( $this->get_user_item_id(), $limit, true );
		$output = array();

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( $result && is_string( $result ) ) {
					$result = json_decode( $result );

					unset( $result->questions );

					$output[] = $result;
				}
			}
		}

		return $output;
	}

	/**
	 * Get question ids user has started inside quiz.
	 *
	 * @since 4.0.0
	 *
	 * @return bool|array
	 */
	public function get_questions() {
		$ids = $this->get_meta( 'questions' );

		if ( $ids === false ) {
			$quiz = learn_press_get_quiz( $this->get_item_id() );
			$ids  = $quiz->get_question_ids();
		}

		return apply_filters( 'learn-press/user-item-quiz-questions', $ids, $this->get_user_id(), $this );
	}

	/**
	 * Calculate results of quiz.
	 *
	 * @version 4.0.0
	 *
	 * @return array
	 */
	public function calculate_results() {
		$quiz         = learn_press_get_quiz( $this->get_item_id() );
		$last_results = LP_User_Items_Result_DB::instance()->get_result( $this->get_user_item_id() );

		if ( ! $last_results ) {
			$last_results = array();
		}

		$questions = isset( $last_results['questions'] ) ? $last_results['questions'] : array_fill_keys( $quiz->get_question_ids(), array() );

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
			'result'            => 0,
			'time_spend'        => $this->get_time_interval( 'display' ),
			'passing_grade'     => $quiz->get_passing_grade(),
		);

		if ( $questions ) {
			foreach ( $questions as $question_id => $last_checked ) {
				$question          = LP_Question::get_question( $question_id );
				$answered          = array_key_exists( 'answered', $last_checked ) ? $last_checked['answered'] : '';
				$check             = apply_filters( 'learn-press/quiz/check-question-result', $question->check( $answered ), $question_id, $this );
				$check['answered'] = $answered;

				if ( $check['answered'] && $check['correct'] ) {
					$result['question_correct'] ++;
					$result['user_mark'] += array_key_exists( 'mark', $check ) ? floatval( $check['mark'] ) : $question->get_mark();
				} else {
					$negative_marking = apply_filters( 'learn-press/get-negative-marking-value', floatval( $question->get_mark() ), $question_id, $quiz->get_id() );

					// If answered is empty consider user has skipped question
					if ( ! $check['answered'] ) {
						if ( $quiz->get_negative_marking() && $quiz->get_minus_skip_questions() ) {
							$result['user_mark'] -= $negative_marking;
						}
						$result['question_empty'] ++;
					} else {
						if ( $quiz->get_negative_marking() ) {
							$result['user_mark'] -= $negative_marking;
						}
						$result['question_wrong'] ++;
					}
				}

				$result['questions'][ $question_id ] = apply_filters( 'learn-press/question-results-data', $last_checked ? array_merge( $last_checked, $check ) : $check, $question_id, $quiz->get_id() );

				if ( $check['answered'] ) {
					$result['question_answered'] ++;
				}
			}

			$result['user_mark'] = ( $result['user_mark'] >= 0 ) ? $result['user_mark'] : 0;

			$percent          = $result['mark'] ? ( $result['user_mark'] / $result['mark'] ) * 100 : 0;
			$result['result'] = $percent;
			$grade            = '';

			if ( $this->get_status() === 'completed' ) {
				$grade = $percent >= $this->get_quiz()->get_data( 'passing_grade' ) ? 'passed' : 'failed';
			}

			$result['question_count'] = count( $questions );

			learn_press_update_user_item_field(
				array(
					'graduation' => $grade,
				),
				array(
					'user_item_id' => $this->get_user_item_id(),
				)
			);
		}

		LP_User_Items_Result_DB::instance()->update( $this->get_user_item_id(), wp_json_encode( $result ) );

		return $result;
	}

	protected function _get_results() {
		return LP_User_Items_Result_DB::instance()->get_result( $this->get_user_item_id() );
	}

	public function is_passed() {
		return $this->get_graduation();
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

	/**
	 * Return TRUE if user has pressed SKIP on this question
	 *
	 * @param int $question_id
	 *
	 * @return bool
	 */
	public function is_skipped( $question_id ) {
		return $this->get_question_answer( $question_id ) === '__SKIPPED__';
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
		$result = $this->get_results( '' );

		if ( $percent ) {
			if ( $result['question_count'] ) {
				$return = 0;
			} else {
				$return = $result['question_answered'] ? ( $result['question_answered'] / $result['question_count'] ) * 100 : 0;
			}
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
	 * @param string $return - Optional.
	 *
	 * @return LP_Duration
	 */
	public function get_time_remaining( $return = 'object' ) {
		$time = parent::get_time_remaining( $return );

		return apply_filters( 'learn-press/quiz/time-remaining', $time, $this->get_item_id(), $this->get_course_id(), $this->get_user_id() );
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

	public function add_checked_question( $id ) {
		settype( $id, 'array' );
		$checked = $this->get_checked_questions();
		$checked = array_merge( $checked, $id );

		$this->update_meta( '_lp_question_checked', $checked );

		return $checked;
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
	 * @param int   $question_id
	 * @param mixed $answered
	 *
	 * @return mixed
	 */
	public function check_question( $question_id, $answered = null ) {
		$checked = false;

		try {
			if ( $this->can_check_answer( $question_id ) ) {
				$this->add_question_answer( $question_id, $answered );
				$this->add_checked_question( $question_id );

				$question            = learn_press_get_question( $question_id );
				$checked             = $question->check( $answered );
				$checked['answered'] = $answered;
			} else {
				throw new Exception( __( 'Cannot check answer the question.', 'learnpress' ), 1010 );
			}
		} catch ( Exception $ex ) {
			return new WP_Error( $ex->getCode(), $ex->getMessage() );
		}

		return $checked;
	}

	/**
	 * @param int $question_id
	 *
	 * @return int
	 */
	public function hint( $question_id ) {
		$remain = $this->can_hint_answer();

		if ( $remain ) {
			if ( ! $this->has_hinted_question( $question_id ) ) {
				$checked   = $this->get_hint_questions();
				$checked[] = $question_id;
				$this->set_meta( '_lp_question_hint', $checked );
			}

			$count = $this->get_count_hint();
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
		return count( $this->get_checked_questions() );
	}

	/**
	 * @param int $question_id - Added since 3.3.0
	 *
	 * @return bool
	 */
	public function can_check_answer( $question_id = null ) {
		$quiz = learn_press_get_quiz( $this->get_item_id() );
		$can  = false;

		if ( $quiz->get_instant_check() && $this->get_status() === 'started' ) {
			$can = ! $this->has_checked_question( $question_id );
		}

		return apply_filters( 'learn-press/can-instant-check-question', $can, $question_id, $this->get_item_id(), $this->get_course_id() );
	}

	/**
	 * Get number retaken count.
	 *
	 * @return integer
	 */
	public function get_retaken_count(): int {
		return absint( learn_press_get_user_item_meta( $this->get_user_item_id(), '_lp_retaken_count' ) );
	}

	/**
	 * Update learnpress_user_itemmeta retaken
	 *
	 * @return void
	 */
	public function update_retake_count() {
		$count = $this->get_retaken_count();
		$count ++;

		return $this->update_meta( '_lp_retaken_count', $count );
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
		return count( $this->get_hint_questions() );
	}

	/**
	 * Return true if check answer is enabled.
	 *
	 * @return bool
	 */
	public function can_hint_answer() {
		return apply_filters( 'learn-press/user-quiz/can-hint-answer', true, $this->get_id(), $this->get_course_id() );
	}

	public function complete( $status = 'completed' ) {
		parent::complete( $status );

		$this->update();
	}

	/**
	 * @deprecated
	 */
	public function finish() {
		$this->complete( 'completed' );

		// Force to re-calculate quiz results and update cache.
		$r = $this->get_results( '', true );
	}

	public function is_review_questions() {
		return LP_Global::quiz_question() && ( $this->get_status() === 'completed' );
	}
}
