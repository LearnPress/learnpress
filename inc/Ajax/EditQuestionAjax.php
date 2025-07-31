<?php
/**
 * class EditCurriculumAjax
 *
 * This class handles the AJAX request to edit the curriculum of a course.
 *
 * @since 4.2.8.8
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Ajax\AbstractAjax;

use LearnPress\Databases\QuestionAnswersDB;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Question\QuestionPostMultipleChoiceModel;
use LearnPress\Models\Question\QuestionPostSingleChoiceModel;
use LP_Helper;
use LP_REST_Response;
use Throwable;

class EditQuestionAjax extends AbstractAjax {
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

		$params            = LP_Helper::json_decode( $params, true );
		$question_id       = $params['question_id'] ?? 0;
		$questionPostModel = QuestionPostModel::find( $question_id, true );
		if ( ! $questionPostModel ) {
			throw new Exception( __( 'Question not found', 'learnpress' ) );
		}

		$params['questionPostModel'] = $questionPostModel;

		return $params;
	}


	/**
	 * Update answer config of question
	 *
	 * JS file edit-quiz.js: function updateAnswersConfig call this method.
	 *
	 * @return void
	 */
	public static function add_question_answer() {
		$response = new LP_REST_Response();

		try {
			$data         = self::check_valid();
			$answer_title = $data['answer_title'] ?? '';
			if ( empty( $answer_title ) ) {
				throw new Exception( __( 'Answer title is required', 'learnpress' ) );
			}

			/**
			 * @var QuestionPostModel $questionPostModel
			 */
			$questionPostModel = $data['questionPostModel'];
			$type              = $questionPostModel->get_type();

			if ( $type === 'single_choice' ) {
				$questionAnswerModel = new QuestionPostSingleChoiceModel( $questionPostModel->ID );
			} elseif ( $type === 'multiple_choice' ) {
				$questionAnswerModel = new QuestionPostMultipleChoiceModel( $questionPostModel->ID );
			} else {

			}

			$db        = QuestionAnswersDB::getInstance();
			$max_order = $db->get_last_number_order( $questionPostModel->get_id() );

			$answer = array(
				'question_id' => $questionPostModel->get_id(),
				'title'       => $answer_title,
				'value'       => learn_press_random_value(),
				'is_true'     => $answer['is_true'] ?? '',
				'order'       => $max_order,
			);

			$questionAnswerModel = new QuestionAnswerModel( $answer );
			$questionAnswerModel->save();

			$response->status  = 'success';
			$response->message = __( 'Question answer added successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
