<?php
/**
 * class EditCurriculumAjax
 *
 * This class handles the AJAX request to edit the curriculum of a course.
 *
 * @since 4.2.9
 * @version 1.0.2
 */

namespace LearnPress\Ajax;

use Exception;

use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionAnswerConfigModel;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\Admin\AdminEditQuestionTemplate;
use LP_Helper;
use LP_REST_Response;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class EditQuestionAjax
 *
 * @since 4.2.9
 * @version 1.0.1
 */
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
	 * @since 4.2.9
	 * @version 1.0.1
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
				$question_hint = Template::sanitize_html_content( $question_hint );
				$questionPostModel->save_meta_value_by_key( QuestionPostModel::META_KEY_HINT, $question_hint );
			}

			if ( false !== $question_explanation ) {
				$question_explanation = Template::sanitize_html_content( $question_explanation );
				$questionPostModel->save_meta_value_by_key( QuestionPostModel::META_KEY_EXPLANATION, $question_explanation );
			}

			if ( false !== $question_mark ) {
				$question_mark = (float) $question_mark;
				$questionPostModel->save_meta_value_by_key( QuestionPostModel::META_KEY_MARK, $question_mark );
			}

			if ( false !== $question_type ) {
				$question_type = LP_Helper::sanitize_params_submitted( $question_type, 'key' );

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
	 * JS file edit-question.js: functions addQuestionAnswer, autoUpdateAnswer,
	 * fibSaveContent call this method.
	 *
	 * @since 4.2.9
	 * @version 1.0.1
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
			$answers_save     = $question_answers;

			$questionPostModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionPostModel ) {
				throw new Exception( __( 'Question not found', 'learnpress' ) );
			}

			if ( $questionPostModel->get_type() === 'fill_in_blanks' ) {
				$questionPostFIBModel = new QuestionPostFIBModel( $questionPostModel );

				$answers_save = [
					[
						'title'   => $questionPostFIBModel->convert_content_from_editor_to_db(
							$question_answers['title'] ?? ''
						),
						'value'   => $question_answers['value'] ?? learn_press_random_value(),
						'order'   => 1,
						//'is_true' => 'yes',
					],
				];

				// Update title
				$questionAnswerModel              = new QuestionAnswerModel( $question_answers );
				$questionAnswerModel->question_id = $question_id;
				$content                          = $question_answers['title'] ?? '';
				$meta_data                        = $question_answers['meta_data'] ?? [];
				$questionAnswerModel->title       = $answers_save[0]['title'];
				$questionAnswerModel->value       = $answers_save[0]['value'];
				$questionAnswerModel->order       = $answers_save[0]['order'];
				//$questionAnswerModel->is_true     = 'yes';
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
				// End save old table question_answers

				$answers_save[0]['meta'] = [
					QuestionAnswerModel::META_KEY_BLANKS => $meta_data,
				];
			} elseif ( $questionPostModel->is_legacy_answer_type() ) {
				$answers_save      = [];
				$existing_answers  = $questionPostModel->get_answer_option();
				$answer_ids        = array_map(
					'absint',
					array_column( $question_answers, 'question_answer_id' )
				);
				$answers_to_delete = [];
				foreach ( $existing_answers as $existing_answer ) {
					if ( ! in_array( (int) $existing_answer->question_answer_id, $answer_ids, true ) ) {
						$answers_to_delete[] = $existing_answer;
					}
				}

				if ( ! empty( $answers_to_delete )
					&& in_array( $questionPostModel->get_type(), [ 'single_choice', 'multi_choice' ], true )
					&& count( array_filter( $question_answers ) ) < 2 ) {
					throw new Exception( __( 'At least two answer is required.', 'learnpress' ) );
				}

				foreach ( $question_answers as $answer ) {
					if ( empty( $answer ) ) {
						continue;
					}

					$is_new = empty( $answer['question_answer_id'] );
					if ( $is_new && empty( $answer['value'] ) ) {
						$answer['value'] = learn_press_random_value();
					}

					$questionAnswerModel              = new QuestionAnswerModel( $answer );
					$questionAnswerModel->question_id = $question_id;
					$questionAnswerModel->save();

					$answers_save[] = [
						'title'   => $answer['title'] ?? '',
						'value'   => $answer['value'],
						'order'   => $answer['order'] ?? count( $question_answers ) + 1,
						'is_true' => $answer['is_true'] ?? '',
					];
				}

				foreach ( $answers_to_delete as $answer_to_delete ) {
					$answer_to_delete->delete();
				}
			}

			// For save new table question_answer_config - @since 4.5.0
			$questionPostModel->save_answers_config( $answers_save );

			$response->status  = 'success';
			$response->message = __( 'Answer of Question update successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
