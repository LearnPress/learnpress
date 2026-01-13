<?php

namespace LearnPress\Ajax\AI;

use Exception;
use LearnPress;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\CourseService;
use LearnPress\Services\OpenAiService;
use LearnPress\TemplateHooks\Admin\AI\AdminCreateCourseAITemplate;
use LearnPress\TemplateHooks\Admin\AI\AdminEditCourseCurriculumWithAITemplate;
use LearnPress\TemplateHooks\Admin\AI\AdminEditWithAITemplate;
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
				$result['lp_structure_course'] = $lp_structure_data[0] ?? '';
				if ( empty( $result['lp_structure_course'] )
					|| ! is_array( $result['lp_structure_course'] ) ) {
					throw new Exception( __( 'Error: no data structure course generated!', 'learnpress' ) );
				}

				$result['lp_html_preview'] = AdminCreateCourseAITemplate::html_preview_with_data( $result['lp_structure_course'] );
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
			$response->message               = __( 'Create Course Successfully! Redirecting to course detail...', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Generate prompt title with AI
	 */
	public function openai_generate_prompt() {
		$response = new LP_REST_Response();

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str       = LP_Request::get_param( 'data' );
			$params         = LP_Helper::json_decode( $data_str, true );
			$lp_prompt_type = $params['lp-prompt-type'] ?? '';

			switch ( $lp_prompt_type ) {
				case 'course-description':
					$prompt = Config::instance()->get( 'prompt-create-description-course', 'settings/openAi', compact( 'params' ) );
					break;
				case 'course-title':
					$prompt = Config::instance()->get( 'prompt-create-title-course', 'settings/openAi', compact( 'params' ) );
					break;
				case 'course-image':
					$prompt = Config::instance()->get( 'prompt-create-image-course', 'settings/openAi', compact( 'params' ) );
					break;
				case 'course-curriculum':
					$prompt = Config::instance()->get( 'prompt-curriculum-course', 'settings/openAi', compact( 'params' ) );
					break;
				case $lp_prompt_type:
					$prompt = apply_filters( 'lp-prompt-type', '', $lp_prompt_type, $params );
					break;
				default:
					$prompt = '';
					break;
			}

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
	public function openai_generate_data() {
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
				$lp_prompt_type = $params['lp-prompt-type'] ?? '';
				switch ( $lp_prompt_type ) {
					case 'course-description':
					case 'course-title':
						$results = $lp_structure_data[0];
						if ( isset( $results['results'] ) ) {
							$results = $results['results'];
						} else {
							throw new Exception(
								__(
									'Error: No data was generated. The requested data may be large, increase the Max Tokens in settings and try again.',
									'learnpress'
								)
							);
						}

						foreach ( $results as $index => $data_item ) {
							$data_item                  = $data_item['item'] ?? '';
							$args                       = [
								'index'        => $index,
								'value'        => $data_item,
								'target-apply' => $params['target-apply'] ?? '',
							];
							$result['lp_html_preview'] .= AdminEditWithAITemplate::instance()->html_list_results( $args );
						}
						break;
					case 'course-curriculum':
						$result['lp_structure_course'] = $lp_structure_data[0];
						$result['lp_html_preview']     = AdminEditCourseCurriculumWithAITemplate::html_preview_with_data(
							$result['lp_structure_course']
						);
						break;
					case $lp_prompt_type:
						$result['lp_html_preview'] = apply_filters( 'lp-openai-render-data-generated', '', $lp_prompt_type, $lp_structure_data );
						break;
					default:
						break;
				}
			}

			$result['lp_html_preview'] .= sprintf(
				'<input type="hidden" name="lp-openai-generated-data" value="%s" />',
				Template::convert_data_to_json( $lp_structure_data )
			);

			$response->data    = $result;
			$response->status  = 'success';
			$response->message = __( 'Generate course successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Generate image with prompt submitted
	 * Send data to Open AI to generate image
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public function openai_generate_image() {
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
				'n'      => intval( $params['outputs'] ?? 1 ),
				'size'   => $params['size'] ?? '',
			];

			$result                    = OpenAiService::instance()->send_request_create_image( $args );
			$html_image                = '';
			$result['lp_html_preview'] = '';
			$data                      = $result['data'] ?? [];
			if ( ! empty( $data ) ) {
				$result['lp_html_preview'] = '<div class="lp-ai-images-warp">';
				foreach ( $data as $index => $data_item ) {
					$image_base_64 = $data_item['b64_json'] ?? '';
					$image_url     = $data_item['url'] ?? '';

					if ( ! empty( $image_url ) ) {
						$html_image = sprintf(
							'<img src="%s" alt="AI Generated Image" />',
							esc_url( $image_url )
						);
					} elseif ( ! empty( $image_base_64 ) ) {
						$html_image = sprintf(
							'<img src="data:image/png;base64,%s" alt="AI Generated Image" />',
							esc_attr( $image_base_64 )
						);
					}

					if ( empty( $html_image ) ) {
						continue;
					}

					$result['lp_html_preview'] .= '<div class="lp-ai-image-item">';
					$result['lp_html_preview'] .= $html_image;
					$result['lp_html_preview'] .= sprintf(
						'<button class="lp-btn-ai-apply-image lp-button" type="button"
							data-send="%s">%s
						</button>',
						Template::convert_data_to_json(
							[
								'action'       => 'openai_apply_image_feature',
								'image-url'    => ! empty( $image_url ) ? $image_url : '',
								'image-base64' => ! empty( $image_base_64 ) ? $image_base_64 : '',
								'id_url'       => 'apply-image-feature',
							]
						),
						__( 'Apply Image', 'learnpress' )
					);
					$result['lp_html_preview'] .= '</div>';
				}
				$result['lp_html_preview'] .= '</div>';
			}

			$response->data    = $result;
			$response->status  = 'success';
			$response->message = __( 'Generate image successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Apply image feature to post
	 * Upload image to media and set as feature image for post
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public function openai_apply_image_feature() {
		$response = new LP_REST_Response();

		try {
			set_time_limit( 0 );
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str     = $_POST['data'] ?? '';
			$params       = LP_Helper::json_decode( wp_unslash( $data_str ), true );
			$image_base64 = $params['image-base64'] ?? '';
			$image_url    = $params['image-url'] ?? '';

			$params     = LP_Helper::sanitize_params_submitted( $params );
			$post_id    = $params['post-id'] ?? '';
			$post_title = $params['post-title'] ?? uniqid();
			$post_slug  = sanitize_title( $post_title );

			if ( empty( $post_id ) ) {
				throw new Exception( __( 'Invalid post ID.', 'learnpress' ) );
			}

			if ( ! function_exists( 'media_handle_sideload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

			if ( ! empty( $image_url ) ) {
				$tmp      = download_url( $image_url );
				$fileExt  = pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
				$filename = sanitize_file_name( $post_slug . '-' . uniqid() . '.' . $fileExt );

			} elseif ( ! empty( $image_base64 ) ) {
				$decoded_image = base64_decode( $image_base64 );
				$tmp           = wp_tempnam();
				file_put_contents( $tmp, $decoded_image );
				$filename = sanitize_file_name( $post_slug . '-' . uniqid() . '.png' );
			} else {
				throw new Exception( __( 'No image data provided.', 'learnpress' ) );
			}

			$file_array    = [
				'name'     => $filename,
				'tmp_name' => $tmp,
			];
			$attachment_id = media_handle_sideload( $file_array, $post_id );

			if ( ! is_wp_error( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			} else {
				throw new Exception( $attachment_id->get_error_message() );
			}

			$args = [
				'src'           => wp_get_attachment_url( $attachment_id ),
				'post-id'       => $post_id,
				'attachment-id' => $attachment_id,
			];

			$response->data->html_image    = AdminEditWithAITemplate::instance()->html_feature_image_created( $args );
			$response->data->attachment_id = $attachment_id;
			$response->status              = 'success';
			$response->message             = __( 'Apply image successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		set_time_limit( LearnPress::$time_limit_default_of_sever );

		wp_send_json( $response );
	}
}
