<?php

/**
 * Class Quiz Post Model
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.7.6
 */

namespace LearnPress\Models;

use LP_Cache;
use LP_Question;
use LP_Question_DB;
use LP_Question_Filter;
use LP_Quiz;
use LP_Quiz_Filter;
use Throwable;


class QuizPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_QUIZ_CPT;

	/**
	 * Const meta key
	 */
	const META_KEY_PASSING_GRADE        = '_lp_passing_grade';
	const META_KEY_RETAKE_COUNT         = '_lp_retake_count';
	const META_KEY_DURATION             = '_lp_duration';
	const META_KEY_PAGINATION           = '_lp_pagination';
	const META_KEY_REVIEW               = '_lp_review';
	const META_KEY_SHOW_CORRECT_REVIEW  = '_lp_show_correct_review';
	const META_KEY_INSTANT_CHECK        = '_lp_instant_check';
	const META_KEY_NEGATIVE_MARKING     = '_lp_negative_marking';
	const META_KEY_MINUS_SKIP_QUESTIONS = '_lp_minus_skip_questions';

	/**
	 * Get post assignment by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter_post     = new LP_Quiz_Filter();
		$filter_post->ID = $post_id;

		$key_cache   = "quizPostModel/find/{$post_id}";
		$lpQuizCache = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$quizPostModel = $lpQuizCache->get_cache( $key_cache );
			if ( $quizPostModel instanceof QuizPostModel ) {
				return $quizPostModel;
			}
		}

		$quizPostModel = self::get_item_model_from_db( $filter_post );
		// Set cache
		if ( $quizPostModel instanceof QuizPostModel ) {
			$lpQuizCache->set_cache( $key_cache, $quizPostModel );
		}

		return $quizPostModel;
	}

	/**
	 * Get max mark of assignment
	 *
	 * @return string
	 */
	public function get_duration(): string {
		return $this->get_meta_value_by_key( self::META_KEY_DURATION, '0 minute' );
	}

	/**
	 * Get max mark of assignment
	 *
	 * @return float
	 */
	public function get_passing_grade(): float {
		return (float) $this->get_meta_value_by_key( self::META_KEY_PASSING_GRADE, 80 );
	}

	/**
	 * Get retake count option.
	 *
	 * @return int
	 */
	public function get_retake_count(): int {
		return (int) $this->get_meta_value_by_key( self::META_KEY_RETAKE_COUNT, 0 );
	}

	/**
	 * Get all question's ids of the quiz.
	 *
	 * @param array $statuses
	 *
	 * @return int[]
	 * @version 1.0.0
	 * @since 4.2.7.6
	 */
	public function get_question_ids( array $statuses = [ 'publish' ] ): array {
		$lp_question_db = LP_Question_DB::getInstance();
		$question_ids   = [];

		try {
			$filter              = new LP_Question_Filter();
			$filter->ID          = $this->get_id();
			$filter->post_status = $statuses;
			$question_ids        = $lp_question_db->get_list_question_ids_of_quiz( $filter );

			// Hook old
			if ( has_filter( 'learn-press/quiz/get-question-ids' ) ) {
				$quiz_old              = new LP_Quiz( $this->get_id() );
				$course_id_of_quiz_old = $quiz_old->get_course_id();

				$question_ids = apply_filters(
					'learn-press/quiz/get-question-ids',
					$question_ids,
					$this->get_id(),
					$course_id_of_quiz_old
				);
			}

			$question_ids = apply_filters(
				'learn-press/quiz/question-ids',
				$question_ids,
				$this,
				$statuses
			);

			if ( ! is_array( $question_ids ) ) {
				$question_ids = array();
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $question_ids;
	}

	/**
	 * Get number questions in quiz.
	 *
	 * @return int
	 */
	public function count_questions(): int {
		$size      = 0;
		$questions = $this->get_question_ids();

		if ( $questions ) {
			$size = sizeof( $questions );
		}

		return (int) apply_filters( 'learn-press/quiz/count-questions', $size, $this->get_id() );
	}

	/**
	 * Get Mark of the quiz (total mark questions).
	 *
	 * @return mixed|null
	 */
	public function get_mark() {
		$questions = $this->get_question_ids();
		$mark      = 0;

		foreach ( $questions as $question_id ) {
			$question = LP_Question::get_question( $question_id );
			if ( $question ) {
				$mark += $question->get_mark();
			}
		}

		return apply_filters( 'learn-press/quiz-mark', $mark, $this->get_id() );
	}

	/**
	 * Check option instant check has enabled.
	 *
	 * @return bool
	 */
	public function has_instant_check(): bool {
		return $this->get_meta_value_by_key( self::META_KEY_INSTANT_CHECK, 'no' ) === 'yes';
	}

	/**
	 * Check option negative marking has enabled.
	 *
	 * @return bool
	 */
	public function has_negative_marking(): bool {
		return $this->get_meta_value_by_key( self::META_KEY_NEGATIVE_MARKING, 'no' ) === 'yes';
	}

	/**
	 * Check option minus skip questions has enabled.
	 *
	 * @return bool
	 */
	public function has_minus_skip_questions(): bool {
		return $this->get_meta_value_by_key( self::META_KEY_MINUS_SKIP_QUESTIONS, 'no' ) === 'yes';
	}

	/**
	 * Check option minus skip questions has enabled.
	 *
	 * @return bool
	 */
	public function has_show_correct_review(): bool {
		return $this->get_meta_value_by_key( self::META_KEY_SHOW_CORRECT_REVIEW, 'yes' ) === 'yes';
	}
}
