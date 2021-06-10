<?php

/**
 * Class LP_Course_No_Required_Enroll.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.8
 */
defined( 'ABSPATH' ) || exit();
if ( ! class_exists( 'LP_Course' ) ) {
	class LP_Course_No_Required_Enroll {
		/**
		 * LP_Course_No_Required_Enroll constructor.
		 */
		public function __construct() {}
		/**
		 * @param string $prop
		 * @param false $force
		 * @param int $quiz_id
		 * @param null $answered
		 * @param int $course_id
		 *
		 * @return array|mixed|string
		 */
		public function guest_quiz_get_results( $prop = 'result', $force = false, $quiz_id = 0, $answered = null, $course_id = 0 ) {

			$result                   = $this->guest_calculate_results_quiz( $quiz_id, $answered, $course_id );
			$result['user_item_id']   = '';
			$result['interval']       = array();
			$result['graduation']     = '';
			$result['graduationText'] = '';
			return $prop ? $result[ $prop ] : $result;
		}

		/**
		 * @param $quiz_id
		 * @param $answered
		 * @param $course_id
		 *
		 * @return array
		 */
		public function guest_calculate_results_quiz( $quiz_id, $answered, $course_id ) {
			$quiz         = learn_press_get_quiz( $quiz_id );
			$course       = learn_press_get_course( $course_id );
			$last_results = array();

			$questions = array_fill_keys( $quiz->get_question_ids(), array() );
			$result    = array(
				'questions'         => array(),
				'mark'              => $quiz->get_mark(),
				'user_mark'         => 0,
				'question_count'    => 0,
				'question_empty'    => 0,
				'question_answered' => 0,
				'question_wrong'    => 0,
				'question_correct'  => 0,
				'status'            => 'completed',
				'result'            => 0,
				'time_spend'        => 0,
				'passing_grade'     => $quiz->get_passing_grade(),
			);
			if ( $questions ) {
				foreach ( $questions as $question_id => $last_checked ) {
					$question = LP_Question::get_question( $question_id );
					$answer   = $answered[ $question_id ] ?? false;
					$check             = apply_filters( 'learn-press/quiz/check-question-result', $question->check( $answer ), $question_id );
					if ( $answer && $check['correct'] ) {
						$result['question_correct'] ++;
						$result['user_mark'] += array_key_exists( 'mark', $check ) ? floatval( $check['mark'] ) : $question->get_mark();
					} else {
						$negative_marking = apply_filters( 'learn-press/get-negative-marking-value', floatval( $question->get_mark() ), $question_id, $quiz->get_id() );

						// If answered is empty consider user has skipped question
						if ( ! $answer ) {
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

					if ( $answer ) {
						$result['question_answered'] ++;
					}
				}

				$result['user_mark'] = ( $result['user_mark'] >= 0 ) ? $result['user_mark'] : 0;

				$percent          = $result['mark'] ? ( $result['user_mark'] / $result['mark'] ) * 100 : 0;
				$result['result'] = $percent;
				$grade            = '';

				$grade = $percent >= $quiz->get_passing_grade() ? 'passed' : 'failed';

				$result['question_count'] = count( $questions );

				learn_press_update_user_item_field(
					array(
						'graduation' => $grade,
					),
					array(
						'user_item_id' => '',
					)
				);
			}
			return $result;
		}

		/**
		 * @param $answered
		 * @param $quiz_id
		 *
		 * @return array
		 */
		public function guest_get_quiz_answered( $answered, $quiz_id ) {
			$quiz         = learn_press_get_quiz( $quiz_id );
			$question_ids = $quiz->get_question_ids();
			foreach ( $question_ids as $question_id ) {
				$question       = learn_press_get_question( $question_id );
				$result[ $question_id ] = array(
					'correct'  => $question->show_correct_answers(),
					'mark'     => $question->get_mark(),
					'answered' => $answered[ $question_id ] ?? array(),
				);
			}
			return $result;
		}

		/**
		 * @param $quiz_id
		 * @param $answered
		 * @param $course_id
		 *
		 * @return array
		 */
		public function guest_quiz_get_attempts( $quiz_id, $answered, $course_id ) {
			$conclude = $this->guest_calculate_results_quiz( $quiz_id, $answered, $course_id );
			// object initialization
			$result_obj = new StdClass();
			// push value to obj
			$result_obj->mark              = $conclude['mark'];
			$result_obj->user_mark         = $conclude['user_mark'];
			$result_obj->question_count    = $conclude['question_count'];
			$result_obj->question_empty    = $conclude['question_empty'];
			$result_obj->question_answered = $conclude['question_answered'];
			$result_obj->question_wrong    = $conclude['question_wrong'];
			$result_obj->question_correct  = $conclude['question_correct'];
			$result_obj->status            = $conclude['status'];
			$result_obj->result            = $conclude['result'];
			$result_obj->time_spend        = $conclude['time_spend'];
			$result_obj->passing_grade     = $conclude['passing_grade'];
			$result                        = array();
			$result[]                      = $result_obj;
			return $result;
		}

		/**
		 * @param $question_id
		 * @param null $answered
		 *
		 * @return array|false
		 */
		public function guest_check_question( $question_id, $answered = null ) {
			$checked = false;
			if ( isset( $question_id ) && isset( $answered ) ) {
				$question            = learn_press_get_question( $question_id );
				$checked             = $question->check( $answered );
				$checked['answered'] = $answered;
			}
			return $checked;
		}
		public function guest_start_quiz( $course_id, $item_id ) {
			$success  = true;
			$response = array(
				'success' => $success,
				'message' => __( 'Success!', 'learnpress' ),
			);

			if ( $success ) {
				$course              = LP_Course::get_course( $course_id );
				$quiz                = LP_Quiz::get_quiz( $item_id );
				$show_hint           = $quiz->get_show_hint();
				$show_check          = $quiz->get_show_check_answer();
				$duration            = $quiz->get_duration();
				$show_correct_review = $quiz->get_show_correct_review();

				$status            = 'started';
				$checked_questions = '';
				$hinted_questions  = '';
				$quiz_results      = '';

				$question_ids = $quiz->get_question_ids();
				$answered     = '';

				$questions = learn_press_rest_prepare_user_questions(
					$question_ids,
					array(
						'instant_hint'        => $show_hint,
						'instant_check'       => $show_check,
						'quiz_status'         => $status,
						'checked_questions'   => $checked_questions,
						'hinted_questions'    => $hinted_questions,
						'answered'            => $answered,
						'show_correct_review' => $show_correct_review,
					)
				);

				$results = array(
					'question_ids' => $question_ids,
					'questions'    => $questions,
				);

				// Error get_start_time when ajax call.
				if ( isset( $total_time ) ) {
					$expiration            = '';
					$results['total_time'] = '';
					$results['end_time']   = '';
				}

				$results['duration'] = $duration ? $duration->get() : false;
				$results['answered'] = '';
				$results['status']   = 'started';
				$results['results']  = '';
				$results['retaken']  = absint( $quiz->get_retake_count() );

				$results['attempts']     = '';
				$results['user_item_id'] = '';

				$response['results'] = $results;
			}
			return $response;
		}
	}
}

