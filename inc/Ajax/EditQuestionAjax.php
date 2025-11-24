<?php
/**
 * class EditCurriculumAjax
 *
 * This class handles the AJAX request to edit the curriculum of a course.
 *
 * @since 4.2.9
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
use LearnPress\Models\Question\QuestionSortingChoiceModel;
use LearnPress\TemplateHooks\Admin\AdminEditQuestionTemplate;
use LP_Helper;
use LP_REST_Response;
use Throwable;

class EditQuestionAjax extends AbstractAjax {
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
	 * Update question title.
	 *
	 * JS file edit-quiz.js: function updateQuestionTitle call this method.
	 *
	 */
	public static function update_question() {
		$response = new LP_REST_Response();

		try {
			$data                 = self::check_valid();
			$question_id          = $data['question_id'] ?? 0;
			$question_title       = $data['question_title'] ?? false;
			$question_description = $data['question_description'] ?? false;
			$question_hint        = $data['question_hint'] ?? false;
			$question_explanation = $data['question_explanation'] ?? false;
			$question_mark        = $data['question_mark'] ?? false;
			$question_type        = $data['question_type'] ?? false;

			$questionPostModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionPostModel ) {
				throw new Exception( __( 'Question not found', 'learnpress' ) );
			}

			if ( false !== $question_title ) {
				if ( empty( $question_title ) ) {
					throw new Exception( __( 'Question title is required', 'learnpress' ) );
				}

				$questionPostModel->post_title = $question_title;
			}

			if ( false !== $question_description ) {
				$questionPostModel->post_content = $question_description;
			}

			if ( false !== $question_hint ) {
				$questionPostModel->save_meta_value_by_key( QuestionPostModel::META_KEY_HINT, $question_hint );
			}

			if ( false !== $question_explanation ) {
				$questionPostModel->save_meta_value_by_key( QuestionPostModel::META_KEY_EXPLANATION, $question_explanation );
			}

			if ( false !== $question_mark ) {
				$questionPostModel->save_meta_value_by_key( QuestionPostModel::META_KEY_MARK, $question_mark );
			}

			if ( false !== $question_type ) {
				if ( ! in_array( $question_type, array_keys( QuestionPostModel::get_types() ), true ) ) {
					throw new Exception( __( 'Invalid question type', 'learnpress' ) );
				}

				$questionPostModel->save_meta_value_by_key( QuestionPostModel::META_KEY_TYPE, $question_type );

				// Create default answers for question
				$questionClassName = $questionPostModel::get_question_obj_by_type( $question_type );
				if ( class_exists( $questionClassName ) ) {
					$questionPostTyeModel = new $questionClassName( $questionPostModel );
					if ( method_exists( $questionPostTyeModel, 'create_default_answers' ) ) {
						$questionPostTyeModel->create_default_answers();
					}
				} else {
					throw new Exception( __( 'Question type not found', 'learnpress' ) );
				}

				$response->data->html_option_answers = AdminEditQuestionTemplate::instance()->html_answer_option(
					$questionPostModel
				);
			}

			$questionPostModel->save();
			$response->status  = 'success';
			$response->message = __( 'Question update successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update answer config of question
	 *
	 * JS file edit-quiz.js: function addQuestionAnswer call this method.
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
				$questionAnswerModel = new QuestionPostSingleChoiceModel( $questionPostModel );
			} elseif ( $type === 'multiple_choice' ) {
				$questionAnswerModel = new QuestionPostMultipleChoiceModel( $questionPostModel );
			} else {

			}

			$db        = QuestionAnswersDB::getInstance();
			$max_order = $db->get_last_number_order( $questionPostModel->get_id() );

			$answer = array(
				'question_id' => $questionPostModel->get_id(),
				'title'       => $answer_title,
				'value'       => learn_press_random_value(),
				'is_true'     => $answer['is_true'] ?? '',
				'order'       => $max_order + 1,
			);

			$questionAnswerModel = new QuestionAnswerModel( $answer );
			$questionAnswerModel->save();

			$response->status                = 'success';
			$response->data->question_answer = $questionAnswerModel;
			$response->message               = __( 'Question answer added successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Update answer config of question
	 *
	 * JS file edit-question.js: function fibSaveContent call this method.
	 *
	 * @return void
	 */
	public function update_question_answers_config() {
		$response = new LP_REST_Response();

		try {
			$data             = self::check_valid();
			$question_id      = $data['question_id'] ?? 0;
			$question_answers = $data['answers'] ?? [];
			$question_answers = LP_Helper::sanitize_params_submitted( $question_answers, 'html', false );

			$questionPostModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionPostModel ) {
				throw new Exception( __( 'Question not found', 'learnpress' ) );
			}

			if ( $questionPostModel->get_type() === 'fill_in_blanks' ) {
				$questionPostFIBModel = new QuestionPostFIBModel( $questionPostModel );
				// Update title
				$questionAnswerModel        = new QuestionAnswerModel( $question_answers );
				$content                    = $question_answers['title'] ?? '';
				$meta_data                  = $question_answers['meta_data'] ?? [];
				$questionAnswerModel->title = $questionPostFIBModel->convert_content_from_editor_to_db( $content );
				$questionAnswerModel->save();
				// Update meta value for fill in blanks
				$pattern = '#<span class="lp-question-fib-input" data-id="([^"]+)">([^<]+)<\/span>#';
				preg_match_all( $pattern, $content, $matches );
				$ids = [];

				$fib_blank_options = [];
				if ( ! empty( $matches ) ) {
					$ids   = $matches[1];
					$fills = $matches[2];

					foreach ( $ids as $index => $id ) {
						$fib_blank_options[ $id ] = [
							'id'         => $id,
							'fill'       => html_entity_decode( $fills[ $index ] ?? '' ),
							'match_case' => 0,
							'comparison' => 'equal',
						];
					}
				}

				if ( ! empty( $meta_data ) ) {
					foreach ( $meta_data as $blank_id => $blank_options ) {
						if ( $meta_data[ $blank_id ]['comparison'] === '' ) {
							$meta_data[ $blank_id ]['comparison'] = 'equal';
						}

						if ( ! in_array( $blank_id, $ids, true ) ) {
							unset( $meta_data[ $blank_id ] );
						}
					}
				}

				// If not null ids, but meta_data is empty, we will set default options for blanks.
				if ( ! empty( $ids ) && empty( $meta_data ) ) {
					$meta_data = $fib_blank_options;
				}

				$questionAnswerModel->save_meta_value_by_key( QuestionAnswerModel::META_KEY_BLANKS, $meta_data );
			} else {
				foreach ( $question_answers as $answer ) {
					if ( empty( $answer ) ) {
						continue;
					}

					$questionAnswerModel = new QuestionAnswerModel( $answer );
					$questionAnswerModel->save();
				}
			}

			$response->status  = 'success';
			$response->message = __( 'Answer of Question update successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Delete answer of question
	 *
	 * JS file edit-quiz.js: function deleteQuestionAnswer call this method.
	 *
	 * @return void
	 */
	public static function delete_question_answer() {
		$response = new LP_REST_Response();

		try {
			$data               = self::check_valid();
			$question_answer_id = $data['question_answer_id'] ?? '';
			if ( empty( $question_answer_id ) ) {
				throw new Exception( __( 'Invalid request!', 'learnpress' ) );
			}

			$questionAnswerModel = QuestionAnswerModel::find( $question_answer_id, true );
			if ( ! $questionAnswerModel ) {
				throw new Exception( __( 'Question answer not found', 'learnpress' ) );
			}

			// Delete question answer
			$questionAnswerModel->delete();

			$response->status  = 'success';
			$response->message = __( 'Question answer deleted successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
