<?php

/**
 * Class LP_Course_No_Required_Enroll.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.9
 */
defined( 'ABSPATH' ) || exit();
if ( ! class_exists( 'LP_Course' ) ) {
	class LP_Course_No_Required_Enroll {
		public $_course;

		/**
		 * LP_Course_No_Required_Enroll constructor.
		 *
		 * @editor tungnx
		 * @modify 4.1.4.1
		 */
		public function __construct( LP_Course $course ) {
			$this->_course = $course;
		}

		/**
		 * Get result do quiz
		 *
		 * @param LP_Quiz $quiz
		 * @param array $answered
		 *
		 * @since 4.0.0
		 * @version 1.0.1
		 * @return array
		 */
		public function get_result_quiz( LP_Quiz $quiz, array $answered = [] ): array {
			$result = array(
				'questions'         => array(),
				'mark'              => $quiz->get_mark(),
				'user_mark'         => 0,
				'question_count'    => 0,
				'question_empty'    => 0,
				'question_answered' => 0,
				'question_wrong'    => 0,
				'question_correct'  => 0,
				'status'            => LP_ITEM_COMPLETED,
				'result'            => 0,
				'time_spend'        => 0,
				'passing_grade'     => $quiz->get_passing_grade(),
				'answered'          => array(),
				'pass'              => 0,
			);

			if ( empty( $answered ) ) {
				return $result;
			}

			$question_ids             = array_keys( $answered );
			$result['question_count'] = count( $question_ids );
			$questions                = learn_press_rest_prepare_user_questions( $question_ids );

			foreach ( $questions as $key => $question_info ) {
				$question_id = $question_info['id'];
				//$question = learn_press_get_question( $question_id );
				$question = $question_info['object'];

				if ( ! $question ) {
					continue;
				}

				$point = floatval( $question->get_mark() );

				$result['questions'][ $question_id ]            = $question_info;
				$result['answered'][ $question_id ]             = [];
				$result['answered'][ $question_id ]['answered'] = $answered[ $question_id ] ?? '';

				if ( isset( $answered[ $question_id ] ) ) { // User's answer
					$result['question_answered']++;

					$check = $question->check( $answered[ $question_id ] );
					if ( $check['correct'] ) {
						$result['question_correct']++;
						$result['user_mark'] += $point;

						$result['answered'][ $question_id ]['correct'] = true;
						$result['answered'][ $question_id ]['mark']    = $point;
					} else {
						if ( $quiz->get_negative_marking() ) {
							$result['user_mark'] -= $point;
						}
						$result['question_wrong']++;

						$result['answered'][ $question_id ]['correct'] = false;
						$result['answered'][ $question_id ]['mark']    = 0;
					}
				} elseif ( ! array_key_exists( 'instant_check', $answered ) ) { // User skip question
					if ( $quiz->get_negative_marking() && $quiz->get_minus_skip_questions() ) {
						$result['user_mark'] -= $point;
					}
					$result['question_empty']++;

					$result['answered'][ $question_id ]['correct'] = false;
					$result['answered'][ $question_id ]['mark']    = 0;
				}

				$can_review_quiz = get_post_meta( $quiz->get_id(), '_lp_review', true ) === 'yes';
				if ( $can_review_quiz && ! array_key_exists( 'instant_check', $answered ) ) {
					$result['questions'][ $question_id ]['explanation'] = $question->get_explanation();
					$result['questions'][ $question_id ]['options']     = learn_press_get_question_options_for_js(
						$question,
						array(
							'include_is_true' => get_post_meta( $quiz->get_id(), '_lp_show_correct_review', true ) === 'yes',
							'answer'          => $answered[ $question_id ] ?? '',
						)
					);
				}
			}

			if ( $result['user_mark'] < 0 ) {
				$result['user_mark'] = 0;
			}

			if ( $result['user_mark'] > 0 && $result['mark'] > 0 ) {
				$result['result'] = round( $result['user_mark'] * 100 / $result['mark'], 2, PHP_ROUND_HALF_DOWN );
			}

			$passing_grade = $quiz->get_data( 'passing_grade', 0 );
			if ( $result['result'] >= $passing_grade ) {
				$result['pass'] = 1;
			} else {
				$result['pass'] = 0;
			}

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

		/**
		 * Start quiz
		 *
		 * @param LP_Quiz $quiz
		 *
		 * @return array
		 * @throws Exception
		 * @editor tungnx
		 * @modify 4.1.4.1
		 */
		public function guest_start_quiz( LP_Quiz $quiz ): array {
			$response = array(
				'status'  => 'error',
				'message' => '',
			);

			$show_check          = $quiz->get_instant_check();
			$duration            = $quiz->get_duration();
			$show_correct_review = $quiz->get_show_correct_review();
			$question_ids        = $quiz->get_question_ids();
			$status              = LP_ITEM_STARTED;
			$checked_questions   = [];
			$hinted_questions    = [];
			$time_remaining      = $duration->get();

			$questions = learn_press_rest_prepare_user_questions(
				$question_ids,
				array(
					'instant_check'       => $show_check,
					'quiz_status'         => $status,
					'checked_questions'   => $checked_questions,
					'hinted_questions'    => $hinted_questions,
					'answered'            => [],
					'show_correct_review' => $show_correct_review,
				)
			);

			$response['status']      = 'success';
			$results['question_ids'] = $question_ids;
			$results['questions']    = $questions;
			$results['total_time']   = $time_remaining;
			$results['duration']     = $duration->get();
			$results['status']       = $status; // Must be started
			$results['retaken']      = 0;
			$results['attempts']     = [];
			$results['user_item_id'] = 0;
			$results['answered']     = [];
			$response['results']     = $results;

			return $response;
		}
	}
}

