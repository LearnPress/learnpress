<?php

namespace LearnPress\Ajax\AI;

use Exception;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Helpers\Config;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Question\QuestionPostSingleChoiceModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\CourseService;
use LearnPress\Services\OpenAiService;
use LearnPress\TemplateHooks\Admin\AdminCreateCourseAITemplate;
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

			$args   = [
				'prompt' => $prompt,
			];
			$result = OpenAiService::instance()->send_request( $args );

			$result['html_preview'] = AdminCreateCourseAITemplate::html_preview_with_data( $result );

			$response->data    = $result;
			$response->status  = 'success';
			$response->message = __( 'Generate course successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Generate data course with prompt submitted
	 */
	public function openai_create_course() {
		$response = new LP_REST_Response();

		$data_dumy = <<<EOD
  {
    "course_title": "Introduction to HTML5: Building the Web from Scratch",
    "course_description": "Get started with writing code. This introductory course will walk you through the basics of HTML5, semantic tags, accessibility, and elementary on-page SEO techniques.",
    "sections": [
      {
        "section_title": "Basic HTML5 and Semantic Tags",
        "section_description": "Become familiar with the structure of an HTML5 webpage and understand how semantic tags improve the usability and accessibility of your site.",
        "lessons": [
          {
            "lesson_title": "HTML5 Structure Basics",
            "lesson_description": "Discover the foundational elements of HTML5 - doctype, html, head, and body tags. Explore the hierarchy of these elements and their individual functionalities. Conclude by creating a simple HTML5 page structure."
          },
          {
            "lesson_title": "A Guide to Semantic Tags",
            "lesson_description": "Learn the importance of using semantic tags such as header, nav, section, article, footer, etc., which bear inherent meaning to develop more meaningful, accessible, and SEO-friendly web content."
          }
        ],
        "quizzes": [
          {
            "quiz_title": "Understanding HTML5 and Semantic Tags",
            "quiz_description": "Test your knowledge about HTML5 structure and semantic tags usage.",
            "questions": [
              {
                "question_title": "Which HTML5 tag defines the main content of a web document?",
                "question_description": "Choose the correct HTML5 element",
                "options": [
                  "<main>",
                  "<header>",
                  "<body>",
                  "<article>"
                ],
                "correct_answer": "<main>"
              }
            ]
          }
        ]
      },
      {
        "section_title": "Accessibility and Elementary On-Page SEO",
        "section_description": "Improve your webpage’s visibility and usability. Discover how to make your site accessible and introduce yourself to the basics of on-page SEO.",
        "lessons": [
          {
            "lesson_title": "Web Accessibility Essentials",
            "lesson_description": "Understand the importance of making your web content accessible to all users, including people with disabilities. Get to know essential techniques such as correct use of alt tags for images, color contrast, hierarchy, ARIA roles and more."
          },
          {
            "lesson_title": "Introduction to On-Page SEO",
            "lesson_description": "Uncover the essence of on-page SEO. Learn how to optimize your tags (title, meta, heading tags) and your content to increase your page's visibility on search engines, improve click-through-rate (CTR), and boost traffic to your site."
          }
        ],
        "quizzes": [
          {
            "quiz_title": "Recognition of Web Accessibility and On-Page SEO",
            "quiz_description": "Demonstrate your understanding of web accessibility and basic on-page SEO practices.",
            "questions": [
              {
                "question_title": "Which tag can improve your ranking on search engine results pages (SERPs)?",
                "question_description": "Identify the HTML tag useful in SEO",
                "options": [
                  "<title>",
                  "<h1>",
                  "<meta name='description'>",
                  "All of the above"
                ],
                "correct_answer": "All of the above"
              }
            ]
          }
        ]
      }
    ],
    "course_author": "AI Assistant"
  }
EOD;

		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str = LP_Request::get_param( 'data' );
			$data_str = $data_dumy;
			$data     = LP_Helper::json_decode( $data_str, true );
			if ( empty( $data ) || ! is_array( $data ) ) {
				throw new Exception( __( 'Invalid data to create course!', 'learnpress' ) );
			}

			$courseService = CourseService::instance();

			$data_info_main  = [
				'post_title'   => $data['course_title'] ?? 'AI Generated Course',
				'post_content' => $data['course_description'] ?? '',
				'post_status'  => 'draft',
				'post_author'  => get_current_user_id(),
			];
			$coursePostModel = $courseService->create_info_main( $data_info_main );

			// Create section
			$data_sections = $data['sections'] ?? [];
			foreach ( $data_sections as $data_section ) {
				$section_name        = $data_section['section_title'] ?? '';
				$section_description = $data_section['section_description'] ?? '';
				$lesson_items        = $data_section['lessons'] ?? [];
				$quiz_items          = $data_section['quizzes'] ?? [];

				$courseSectionModel = $courseService->add_section(
					$coursePostModel,
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
			$response->status                = 'success';
			$response->message               = __( 'Create Course Successfully!', 'learnpress' );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}
