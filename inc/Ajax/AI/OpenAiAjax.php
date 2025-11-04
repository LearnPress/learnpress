<?php

namespace LearnPress\Ajax\AI;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Helpers\Config;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\CourseService;
use LearnPress\Services\OpenAiService;
use LearnPress\TemplateHooks\Admin\AdminCreateCourseAITemplate;
use LearnPress\TemplateHooks\Admin\AdminEditWithAITemplate;
use LP_Helper;
use LP_Request;
use LP_REST_Response;
use Throwable;

/**
 * class OpenAiAjax
 * Handle request Open AI
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class OpenAiAjax extends AbstractAjax {
	/**
	 * Generate prompt course with AI
	 */
	public function openai_generate_prompt_course() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );
			$prompt   = Config::instance()->get( 'prompt-create-course', 'settings/openAi', compact( 'params' ) );

			$response->data    = $prompt;
			$response->status  = 'success';
			$response->message = __( 'Generate prompt successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Generate data course with prompt submitted
	 */
	public function openai_generate_data_course() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );
			$prompt   = $params['lp-openai-prompt-generated-field'] ?? '';
			$args     = [
				'prompt' => $prompt,
			];

			$result            = OpenAiService::instance()->send_request( $args );
			$lp_structure_data = $result['lp_structure_data'] ?? [];
			if ( count( $lp_structure_data ) > 0 ) {
				$result['lp_structure_course'] = $lp_structure_data[0];
				$result['lp_html_preview']     = AdminCreateCourseAITemplate::html_preview_with_data( $result['lp_structure_course'] );
			}

			$response->data    = $result;
			$response->status  = 'success';
			$response->message = __( 'Generate course successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Create course with data generated
	 */
	public function openai_create_course() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str              = LP_Request::get_param( 'data' );
			$data                  = LP_Helper::json_decode( $data_str, true );
			$data_structure_course = $data['lp_structure_course'] ?? [];
			if ( empty( $data_structure_course ) ) {
				throw new Exception( __( 'Invalid data to create course!', 'learnpress' ) );
			}

			$courseService = CourseService::instance();

			$data_info_main  = [
				'post_title'   => $data_structure_course['course_title'] ?? 'AI Generated Course',
				'post_content' => $data_structure_course['course_description'] ?? '',
				'post_status'  => 'draft',
				'post_author'  => get_current_user_id(),
			];
			$coursePostModel = $courseService->create_info_main( $data_info_main );

			// Create section
			$data_sections = $data_structure_course['sections'] ?? [];
			foreach ( $data_sections as $data_section ) {
				$section_name        = $data_section['section_title'] ?? '';
				$section_description = $data_section['section_description'] ?? '';
				$lesson_items        = $data_section['lessons'] ?? [];
				$quiz_items          = $data_section['quizzes'] ?? [];

				$courseSectionModel = $coursePostModel->add_section(
					[
						'section_name'        => $section_name,
						'section_description' => $section_description,
					]
				);

				// Create lesson items for section
				foreach ( $lesson_items as $lesson_item ) {
					$lesson_name        = $lesson_item['lesson_title'] ?? '';
					$lesson_description = $lesson_item['lesson_description'] ?? '';

					$courseSectionModel->create_item_and_add(
						[
							'item_title'   => $lesson_name,
							'item_type'    => LP_LESSON_CPT,
							'item_content' => $lesson_description,
						]
					);
				}

				// Create quiz items for section
				foreach ( $quiz_items as $quiz_item ) {
					$quiz_name        = $quiz_item['quiz_title'] ?? '';
					$quiz_description = $quiz_item['quiz_description'] ?? '';

					$courseSectionQuizModel = $courseSectionModel->create_item_and_add(
						[
							'item_title'   => $quiz_name,
							'item_type'    => LP_QUIZ_CPT,
							'item_content' => $quiz_description,
						]
					);
					$quizPostModel          = QuizPostModel::find( $courseSectionQuizModel->item_id, true );

					// Create questions for quiz
					$question_items = $quiz_item['questions'] ?? [];
					foreach ( $question_items as $question_item ) {
						$question_name        = $question_item['question_title'] ?? '';
						$question_description = $question_item['question_description'] ?? '';
						$options              = $question_item['options'] ?? [];
						$correct_answer       = $question_item['correct_answer'] ?? '';

						$data_answers = [];
						foreach ( $options as $index => $option ) {
							$data_answers[] = [
								'title'   => $option,
								'is_true' => ( $option === $correct_answer ) ? 'yes' : 'no',
								'order'   => $index + 1,
							];
						}

						$quizPostModel->create_question_and_add(
							[
								'question_title'   => $question_name,
								'question_content' => $question_description,
								'question_type'    => 'single_choice',
								'question_options' => $data_answers,
							]
						);
					}
				}
			}

			$course_edit_url = $coursePostModel->get_edit_link();

			$response->data->edit_course_url = $course_edit_url;
			$response->data->button_label    = __( 'Redirecting...', 'learnpress' );
			$response->status                = 'success';
			$response->message               = __( 'Create Course Successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Generate prompt title with AI
	 */
	public function openai_generate_prompt_title() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );
			$prompt   = Config::instance()->get( 'prompt-create-title-course', 'settings/openAi', compact( 'params' ) );

			$response->data    = $prompt;
			$response->status  = 'success';
			$response->message = __( 'Generate prompt successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Generate data course with prompt submitted
	 */
	public function openai_generate_title() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );
			$prompt   = $params['lp-openai-prompt-generated-field'] ?? '';
			$args     = [
				'prompt' => $prompt,
			];

			$result                    = OpenAiService::instance()->send_request( $args );
			$lp_structure_data         = $result['lp_structure_data'] ?? [];
			$result['lp_html_preview'] = '';
			if ( count( $lp_structure_data ) > 0 ) {
				$titles = $lp_structure_data[0];
				if ( isset( $titles['titles'] ) ) {
					$titles = $titles['titles'];
				}

				foreach ( $titles as $index => $data_item ) {
					$title                      = $data_item['title'] ?? '';
					$result['lp_html_preview'] .= AdminEditWithAITemplate::instance()->html_list_results( $index, $title );
				}
			}

			$response->data    = $result;
			$response->status  = 'success';
			$response->message = __( 'Generate course successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
