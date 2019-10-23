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

	/**
	 * Add user answer to DB.
	 *
	 * @param int|array $id
	 * @param mixed     $values
	 *
	 * @return array|bool|LP_Quiz_Results|mixed
	 */
	public function add_question_answer( $id, $values = null ) {
		if ( ! $results = $this->get_results( '' ) ) {
			return false;
		}

		$questions = $results->get( 'questions', array() );

		if ( is_numeric( $id ) ) {
			$values = array( $id => $values );
		} else {
			$values = (array) $id;
		}

		foreach ( $values as $id => $answer ) {
			// User has not used checking answer feature
			if ( ! $this->has_checked_question( $id ) ) {
				if ( ! empty( $questions[ $id ] ) ) {
					$questions[ $id ]['answered'] = $answer;
				} else {
					$questions[ $id ] = array( 'answered' => $answer );
				}
			} else {
			}
		}

		$results['questions'] = $questions;

		// Update to DB
		$this->update_meta( 'results', $results->get() );
		$this->calculate_results();

		// Clear cache to apply new changes
		$cache_key = sprintf( 'quiz-%d-%d-%d', $this->get_user_id(), $this->get_course_id(), $this->get_item_id() );
		LP_Object_Cache::set( $cache_key, false, 'learn-press/quiz-result' );

		return $this->get_results( '' );
	}

	public function get_question_answer( $id ) {
		if ( ! $results = $this->get_results( '' ) ) {
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
		//learn_press_update_user_item_meta( $this->get_user_item_id(), '_question_answers', $this->_answers );
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
//		$columns['_question_answers'] = false;
//		$columns['_grade']            = false;
//		$columns['results']           = false;

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
			'failed'      => __( 'Failed', 'learnpress' )
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
		$question_id = $this->get_meta( '_current_question', true );
		$question    = false;
		if ( learn_press_get_post_type( $question_id ) === LP_QUESTION_CPT ) {
			$question = learn_press_get_question( $question_id );
		}

		if ( ! $question || ! $question->is_publish() ) {
			if ( $questions = $this->get_quiz()->get_questions() ) {
				$question_id = reset( $questions );
				$this->set_meta( '_current_question', $question_id );
				$this->update_meta();
			} else {
				$question_id = 0;
			}
		}

		if ( $question_id ) {
			if ( $return == 'object' ) {
				return learn_press_get_question( $question_id );
			}
		}

		return $question_id;
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
	 * @return mixed|array|LP_Quiz_Results
	 */
	public function get_results( $prop = 'result', $force = false ) {
		/**
		 * Do nothing if user is not started quiz
		 */
		if ( in_array( $this->get_status(), array( '', 'viewed' ) ) ) {
			return false;
		}

		$cache_key = sprintf( 'quiz-%d-%d-%d', $this->get_user_id(), $this->get_course_id(), $this->get_item_id() );

		if ( false === ( $result = LP_Object_Cache::get( $cache_key, 'learn-press/quiz-result' ) ) || $force ) {
			if ( false === ( $result = $this->_get_results() ) ) {
				$result = $this->calculate_results();
			}

			LP_Object_Cache::set( $cache_key, $result, 'learn-press/quiz-result' );
		}
		$result['user_item_id'] = $this->get_user_item_id();
		$result['interval'] = [$this->get_start_time(), $this->get_end_time()];
		$result                 = new LP_Quiz_Results( $result );

		return $prop ? $result[ $prop ] : $result;
	}

	/**
	 * Get all attempts of a quiz.
	 *
	 * @param string $args
	 *
	 * @return array
	 */
	public function get_attempts( $args = '' ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'evaluation_questions' => false,
				'limit'                => - 1,
				'offset'               => '',
				'paged'                => ''
			)
		);

		$limit  = isset( $args['limit'] ) && $args['limit'] > 0 ? $args['limit'] : false;
		$offset = isset( $args['offset'] ) && $args['offset'] > 0 ? $args['offset'] : 0;
		$paged  = isset( $args['paged'] ) && $args['paged'] > 0 ? $args['paged'] : false;

		if ( ! $offset && $paged && $limit ) {
			$offset = ( $paged - 1 ) * $limit;
		}

		$attempts = array();
		$query    = $wpdb->prepare( "
			SELECT * 
			FROM {$wpdb->learnpress_user_items}
			WHERE parent_id = %d AND item_type = %s
			AND status = %s
			AND item_id = %d
			ORDER BY user_item_id DESC
			" . ( $limit ? "LIMIT {$offset}, {$limit}" : '' ) . "
		", $this->get_parent_id(), LP_QUIZ_CPT, 'completed', $this->get_item_id() );

		$quiz = $this->get_quiz();

		if ( $rows = $wpdb->get_results( $query ) ) {

			foreach ( $rows as $row ) {
				if ( $results = learn_press_get_user_item_meta( $row->user_item_id, 'results', true ) ) {
					$evaluation_questions = $results['questions'];

					if ( ! $args['evaluation_questions'] ) {
						unset( $results['questions'] );
					}

					if ( ! array_key_exists( 'passing_grade', $results ) ) {
						$results['passing_grade'] = $quiz->get_passing_grade();
					}
				} else {
					$results = array();
				}

				$attempts[] = array_merge(
					array(
						'id'              => absint( $row->user_item_id ),
						'start_time'      => $row->start_time,
						'end_time'        => $row->end_time,
						'expiration_time' => $row->expiration_time,
						'grade'           => learn_press_get_user_item_meta( $row->user_item_id, 'grade', true )
					),
					$results
				);
			}
		}

		return $attempts;
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
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public function calculate_results() {
		$quiz        = learn_press_get_quiz( $this->get_item_id() );
		$lastResults = $this->get_meta( 'results' );

		if ( ! $lastResults ) {
			$lastResults = array();
		}

		$questions = isset( $lastResults['questions'] ) ? $lastResults['questions'] : array_fill_keys( $quiz->get_question_ids(), array() );
		//$answered = isset( $lastResults['answered'] ) ? $lastResults['questions'] : array();

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
			'retake_count'      => 0,
			'passing_grade'     => $quiz->get_passing_grade()
		);

		//$question_ids = $this->get_questions();

		if ( $questions ) {

			foreach ( $questions as $question_id => $lastChecked ) {

				$question = LP_Question::get_question( $question_id );
				$answered = array_key_exists( 'answered', $lastChecked ) ? $lastChecked['answered'] : '';// $this->get_question_answer( $question_id );
				$check    = apply_filters( 'learn-press/quiz/check-question-result', $question->check( $answered ), $question_id, $this );
				//$check['type']     = ! isset( $check['type'] ) || ! $check['type'] ? $question->get_type() : $check['type'];
				//$check['answered'] = ! isset( $check['answered'] ) ? $answered !== null : $check['answered'];
				$check['answered'] = $answered;

				if ( $check['answered'] && $check['correct'] ) {
					$result['question_correct'] ++;
					$result['user_mark'] += array_key_exists( 'mark', $check ) ? floatval( $check['mark'] ) : $question->get_mark();
				} else {
					$negativeMarking = apply_filters( 'learn-press/get-negative-marking-value', floatval( $question->get_mark() ), $question_id, $quiz->get_id() );

					// If answered is empty consider user has skipped question
					if ( ! $check['answered'] ) {
						// TODO: check this again!!!
						if ( $quiz->get_negative_marking() ) {
							// minus for each wrong, empty question
							//$result['user_mark'] -= $negativeMarking;
						}
						$result['question_empty'] ++;
					} else {
						// minus for each wrong, empty question
						$result['user_mark'] -= $negativeMarking;
						$result['question_wrong'] ++;
					}
				}

				$result['questions'][ $question_id ] = apply_filters( 'learn-press/question-results-data', $lastChecked ? array_merge( $lastChecked, $check ) : $check, $question_id, $quiz->get_id() );

				if ( $check['answered'] ) {
					$result['question_answered'] ++;
				}
			}

			// make sure user mark greater than 0
			$result['user_mark'] = ( $result['user_mark'] >= 0 ) ? $result['user_mark'] : 0;

			$percent          = $result['mark'] ? ( $result['user_mark'] / $result['mark'] ) * 100 : 0;
			$result['result'] = $percent;

			if ( $this->get_status() === 'completed' ) {
				$result['grade']      = $percent >= $this->get_quiz()->get_data( 'passing_grade' ) ? 'passed' : 'failed';
				$result['grade_text'] = ( $result['grade'] == 'passed' ) ? __( 'passed', 'learnpress' ) : __( 'failed', 'learnpress' );
			} else {
				$result['grade']      = 'ungraded';
				$result['grade_text'] = __( 'Ungraded', 'learnpress' );
			}
			$result['question_count'] = sizeof( $questions );

			if ( $result['grade'] != learn_press_get_user_item_meta( $this->get_user_item_id(), 'grade', true ) ) {
				learn_press_update_user_item_meta( $this->get_user_item_id(), 'grade', $result['grade'] );
			}

		}

		$this->update_meta( 'results', $result );

		return $result;
	}

	protected function _get_results() {
		if ( metadata_exists( 'learnpress_user_item', $this->get_user_item_id(), 'results' ) ) {
			return $this->get_meta( 'results' );
		}

		return false;
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
		$result = $this->get_results();
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

//		$quiz          = learn_press_get_quiz( $this->get_item_id() );
//		$quiz_duration = $quiz->get_duration();
//		$diff          = false;
//		if ( $quiz_duration && $quiz_duration->get_seconds() >= 0 ) {
//			$diff = current_time( 'timestamp' ) - $this->get_start_time()->getTimestamp();
//			$diff = $quiz_duration->diff( $diff )->get_seconds();
//			if ( $diff <= 0 ) {
//				$diff = 0;
//			}
//		}
//
//		$remaining = $diff !== false ? new LP_Duration( $diff ) : false;
//
//		return apply_filters( 'learn-press/quiz/time-remaining', $remaining, $this->get_item_id(), $this->get_course_id() );
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
				throw new Exception( __( 'You have already checked this question.', 'learnpress' ), 1010 );
			}
		}
		catch ( Exception $ex ) {
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

	/**
	 * @param int $question_id - Added since 4.x.x
	 *
	 * @return bool
	 */
	public function can_check_answer( $question_id = null ) {
		$quiz = learn_press_get_quiz( $this->get_item_id() );
		$can  = false;

		if ( $quiz->get_instant_check() ) {
			$can = ! $this->has_checked_question( $question_id );
		}

		//return apply_filters( 'learn-press/user-quiz/can-check-answer', $can, $this->get_item_id(), $this->get_course_id() );
		return apply_filters( 'learn-press/can-instant-check-question', $can, $question_id, $this->get_item_id(), $this->get_course_id() );
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
		$this->get_results( '', true );
	}

	public function is_review_questions() {
		return LP_Global::quiz_question() && ( $this->get_status() === 'completed' );
	}

	public function can_retake_quiz() {
		return $this->get_user()->can_retake_quiz( $this->get_id(), $this->get_course() );
	}
}