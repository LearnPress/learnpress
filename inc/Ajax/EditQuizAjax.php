<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Databases\QuizQuestionsDB;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Quiz\QuizQuestionModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\Admin\AdminEditQizTemplate;
use LearnPress\TemplateHooks\Admin\AdminEditQuestionTemplate;
use LP_Helper;
use LP_REST_Response;
use Throwable;

/**
 * class EditQuizAjax
 *
 * This class handles the AJAX request to edit the quiz.
 *
 * @since 4.2.9
 * @version 1.0.0
 */
class EditQuizAjax extends AbstractAjax {
	/**
	 * Check permissions and validate parameters.
	 *
	 * @throws Exception
	 *
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public static function check_valid() {
		$params = wp_unslash( $_REQUEST['data'] ?? '' );
		if ( empty( $params ) ) {
			throw new Exception( 'Error: params invalid!' );
		}

		$params        = LP_Helper::json_decode( $params, true );
		$quiz_id       = $params['quiz_id'] ?? 0;
		$quizPostModel = QuizPostModel::find( $quiz_id, true );
		if ( ! $quizPostModel ) {
			throw new Exception( __( 'Quiz not found', 'learnpress' ) );
		}

		$params['quizPostModel'] = $quizPostModel;

		return $params;
	}

	/**
	 * Create a new question and add it to the quiz.
	 *
	 * JS file edit-section.js: function addSection call this method to update the section description.
	 *
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public static function create_question_add_to_quiz() {
		$response = new LP_REST_Response();

		try {
			$data = self::check_valid();
			/**
			 * @var QuizPostModel $quizPostModel
			 */
			$quizPostModel = $data['quizPostModel'];

			$quizQuestionModel  = $quizPostModel->create_question_and_add( $data );
			$questionPostModel  = $quizQuestionModel->get_question_post_model();
			$html_edit_question = AdminEditQizTemplate::instance()->html_edit_question( $questionPostModel );

			$response->data->question           = $questionPostModel;
			$response->data->html_edit_question = $html_edit_question;
			$response->status                   = 'success';
			$response->message                  = __( 'Question added successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Add questions exists (from Question Bank) to quiz.
	 *
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public static function add_questions_to_quiz() {
		$response = new LP_REST_Response();

		try {
			$data = self::check_valid();
			/**
			 * @var QuizPostModel $quizPostModel
			 */
			$quizPostModel = $data['quizPostModel'];
			$quiz_id       = $quizPostModel->get_id();
			$question_ids  = $data['question_ids'] ?? [];
			if ( empty( $question_ids ) ) {
				throw new Exception( __( 'Question IDs are required', 'learnpress' ) );
			}

			$response->data->html_edit_question = [];
			foreach ( $question_ids as $question_id ) {
				$questionPostModel = QuestionPostModel::find( $question_id, true );
				if ( ! $questionPostModel ) {
					throw new Exception( __( 'Question not found', 'learnpress' ) );
				}

				// Check if question already exists in quiz
				$quizQuestionModel = QuizQuestionModel::find( $quiz_id, $question_id );
				if ( $quizQuestionModel ) {
					continue; // Skip if question already exists in quiz
				}

				$quizQuestionsDB = QuizQuestionsDB::getInstance();
				$max_order       = $quizQuestionsDB->get_last_number_order( $quiz_id );

				// Add question to quiz
				$quizQuestionModel                 = new QuizQuestionModel();
				$quizQuestionModel->quiz_id        = $quiz_id;
				$quizQuestionModel->question_id    = $question_id;
				$quizQuestionModel->question_order = $max_order + 1;
				$quizQuestionModel->save();

				$questionPostModel                                  = $quizQuestionModel->get_question_post_model();
				$response->data->html_edit_question[ $question_id ] = AdminEditQizTemplate::instance()->html_edit_question( $questionPostModel );
			}

			$response->status  = 'success';
			$response->message = __( 'Question added successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Remove question from quiz.
	 *
	 * JS file edit-quiz.js: function removeQuestion call this method.
	 *
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public static function remove_question_from_quiz() {
		$response = new LP_REST_Response();

		try {
			$data        = self::check_valid();
			$question_id = $data['question_id'] ?? 0;

			$questionPostModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionPostModel ) {
				throw new Exception( __( 'Question not found', 'learnpress' ) );
			}

			/**
			 * @var QuizPostModel $quizPostModel
			 */
			$quizPostModel     = $data['quizPostModel'];
			$quizQuestionModel = QuizQuestionModel::find( $quizPostModel->get_id(), $questionPostModel->get_id() );
			if ( ! $quizQuestionModel ) {
				throw new Exception( __( 'Question not found in quiz', 'learnpress' ) );
			}

			// Delete question from quiz
			$quizQuestionModel->delete();
			$response->status  = 'success';
			$response->message = __( 'Question removed successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update item on new section and position
	 *
	 * $data['course_id']              => ID of course
	 * $data['items_position']         => list of item id by order on section new
	 * $data['item_id_change']         => ID of item to change section
	 * $data['section_id_new_of_item'] => ID of new section of item
	 * $data['section_id_old_of_item'] => ID of old section of item
	 *
	 * JS file edit-section-item.js: function sortAbleItem call this method.
	 *
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public static function update_questions_position() {
		$response = new LP_REST_Response();

		try {
			$data          = self::check_valid();
			$quizPostModel = $data['quizPostModel'];
			$question_ids  = $data['question_ids'] ?? [];

			if ( empty( $question_ids ) ) {
				throw new Exception( __( 'Question IDs are required', 'learnpress' ) );
			}

			QuizQuestionsDB::getInstance()->update_question_position( $question_ids, $quizPostModel->get_id() );

			$response->status  = 'success';
			$response->message = __( 'Question position updated successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Add questions created to quiz.
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public function add_items( array $data ) {
	}
}
