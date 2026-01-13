<?php

/**
 * Class Quiz Post Model
 *
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 4.2.7.6
 */

namespace LearnPress\Models;

use Exception;
use LearnPress\Databases\QuizQuestionsDB;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Quiz\QuizQuestionModel;
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

	/**
	 * Create question and add to quiz.
	 *
	 * @param array $data [ question_title, question_type, question_content ]
	 *
	 * @return QuizQuestionModel
	 * @throws Exception
	 * @since 4.2.9
	 * @version 1.0.1
	 */
	public function create_question_and_add( array $data ): QuizQuestionModel {
		$question_title   = $data['question_title'] ?? '';
		$question_type    = $data['question_type'] ?? '';
		$question_content = $data['question_content'] ?? '';
		$question_options = $data['question_options'] ?? [];

		if ( empty( $question_title ) ) {
			throw new Exception( __( 'Question title is required', 'learnpress' ) );
		}

		if ( ! QuestionPostModel::check_type_valid( $question_type ) ) {
			throw new Exception( __( 'Invalid question type', 'learnpress' ) );
		}

		// Create question
		$questionPostModelNew               = new QuestionPostModel();
		$questionPostModelNew->post_title   = $question_title;
		$questionPostModelNew->post_content = $question_content;
		$questionPostModelNew->post_status  = 'publish';
		$questionPostModelNew->post_author  = get_current_user_id();
		$questionPostModelNew->save();
		$questionPostModelNew->save_meta_value_by_key(
			QuestionPostModel::META_KEY_TYPE,
			$question_type
		);

		// Get question object by type
		$questionClassName = $questionPostModelNew::get_question_obj_by_type( $question_type );
		if ( class_exists( $questionClassName ) ) {
			/**
			 * @var QuestionPostModel $questionPostTyeModel
			 */
			$questionPostTyeModel = new $questionClassName( $questionPostModelNew );
			if ( ! empty( $question_options ) ) {
				foreach ( $question_options as $index => $answer ) {
					$answer = array(
						'question_id' => $questionPostTyeModel->get_id(),
						'title'       => $answer['title'],
						'value'       => $questionPostTyeModel->random_value(),
						'is_true'     => $answer['is_true'] ?? '',
						'order'       => $index + 1,
					);

					$questionAnswerModel = new QuestionAnswerModel( $answer );
					$questionAnswerModel->save();
					$answers[ $index ]['question_answer_id'] = $questionAnswerModel->question_answer_id;
				}
			} elseif ( method_exists( $questionPostTyeModel, 'create_default_answers' ) ) {
				$questionPostTyeModel->create_default_answers();
			}
		} else {
			throw new Exception( __( 'Question type not found', 'learnpress' ) );
		}

		$quizQuestionsDB = QuizQuestionsDB::getInstance();
		$max_order       = $quizQuestionsDB->get_last_number_order( $this->get_id() );

		// Add question to quiz
		$quizQuestionModel                 = new QuizQuestionModel();
		$quizQuestionModel->quiz_id        = $this->get_id();
		$quizQuestionModel->question_id    = $questionPostModelNew->get_id();
		$quizQuestionModel->question_order = $max_order + 1;
		$quizQuestionModel->save();

		return $quizQuestionModel;
	}

	/**
	 * Add questions exists (from Question Bank) to quiz.
	 *
	 * @param array $data [ 'question_ids' => [] ]
	 *
	 * @throws Exception
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function add_questions_to_quiz( array $data ): array {
		$this->check_capabilities_update_item_course();

		$question_ids = $data['question_ids'] ?? [];
		if ( empty( $question_ids ) ) {
			throw new Exception( __( 'Question IDs are required', 'learnpress' ) );
		}

		$quiz_questions_added = [];
		foreach ( $question_ids as $question_id ) {
			$questionPostModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionPostModel ) {
				throw new Exception( __( 'Question not found', 'learnpress' ) );
			}

			// Check if question already exists in quiz
			$quizQuestionModel = QuizQuestionModel::find( $this->get_id(), $question_id, true );
			if ( $quizQuestionModel ) {
				continue; // Skip if question already exists in quiz
			}

			$quizQuestionsDB = QuizQuestionsDB::getInstance();
			$max_order       = $quizQuestionsDB->get_last_number_order( $this->get_id() );

			// Add question to quiz
			$quizQuestionModel                 = new QuizQuestionModel();
			$quizQuestionModel->quiz_id        = $this->get_id();
			$quizQuestionModel->question_id    = $question_id;
			$quizQuestionModel->question_order = $max_order + 1;
			$quizQuestionModel->save();

			$quiz_questions_added[] = $quizQuestionModel;
		}

		return $quiz_questions_added;
	}

	/**
	 * Update question positions in quiz.
	 *
	 * @param int[] $data [ 'question_ids' => [] ]
	 *
	 * @throws Exception
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function update_question_position( array $data ) {
		$this->check_capabilities_update_item_course();

		$question_ids = $data['question_ids'] ?? [];
		if ( empty( $question_ids ) ) {
			throw new Exception( __( 'Question IDs are required', 'learnpress' ) );
		}

		$question_ids = $data['question_ids'] ?? [];
		QuizQuestionsDB::getInstance()->update_question_position( $question_ids, $this->get_id() );
	}

	/**
	 * Remove question from quiz.
	 *
	 * @param int $question_id
	 *
	 * @throws Exception
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function remove_question_from_quiz( int $question_id ) {
		$this->check_capabilities_update_item_course();

		$questionPostModel = QuestionPostModel::find( $question_id, true );
		if ( ! $questionPostModel ) {
			throw new Exception( __( 'Question not found', 'learnpress' ) );
		}

		$quizQuestionModel = QuizQuestionModel::find( $this->get_id(), $question_id );
		if ( ! $quizQuestionModel ) {
			throw new Exception( __( 'Question not found in quiz', 'learnpress' ) );
		}

		$quizQuestionModel->delete();
	}
}
