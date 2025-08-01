<?php

namespace LearnPress\Ajax;

use Exception;
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
 * @since 4.2.8.8
 * @version 1.0.0
 */

class EditQuizAjax extends AbstractAjax {
	/**
	 * Check permissions and validate parameters.
	 *
	 * @throws Exception
	 *
	 * @since 4.2.8.8
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
	 * Add section
	 *
	 * JS file edit-section.js: function addSection call this method to update the section description.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public static function add_question_to_quiz() {
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
	 * Remove question from quiz.
	 *
	 * JS file edit-quiz.js: function removeQuestion call this method.
	 *
	 * @since 4.2.8.8
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
	 * Add questions created to quiz.
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public function add_items( array $data ) {
	}
}
