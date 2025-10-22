<?php

namespace LearnPress\Models;

use LP_Settings;

class AiModel {


	public static function get_course_title_prompt( $params ) {
		$topic        = $params['topic'] ?? '';
		$characters   = $params['characters'] ?? 60;
		$goal         = $params['goal'] ?? '';
		$audience     = $params['audience'] ?? [];
		$audience_str = implode( ', ', $audience );
		$tone         = $params['tone'] ?? [];
		$tone_str     = implode( ', ', $tone );
		$language     = $params['lang'] ?? [];
		$language_str = implode( ', ', $language );

		$prompt = <<<PROMPT
				You are an expert course title creator.
				Create a concise, compelling course title with the following details:
				- Topic: {$topic}
				- Goal: {$goal}
				- Audience: {$audience_str}
				- Tone: {$tone_str}
				- Language: {$language_str}

				Constraints:
				- The title must be {$characters} characters.
				- Do not include quotation marks
				- Do not add explanation or extra text

				Output: Only the course title as plain text.
				PROMPT;

		return [
			'prompt' => $prompt,
		];
	}

	public static function get_generate_full_course_prompt( array $params ): string {
		// Course Intent
		$role_persona     = trim( $params['role_persona'] ?? 'Expert Instructional Designer' );
		$target_audience  = $params['target_audience']?? 'Beginners';
		$course_objective = trim( $params['course_objective'] ?? '' );

		// AI Settings
		$language        = trim( $params['language'] ?? 'English' );
		$tone            = trim( $params['tone'] ?? 'Informative and encouraging' );
		$lesson_length   = max( 50, (int) ( $params['lesson_length'] ?? 300 ) );
		$reading_level   = trim( $params['reading_level'] ?? 'High school' );
		$seo_emphasis    = trim( $params['seo_emphasis'] ?? '' );
		$target_keywords = trim( $params['target_keywords'] ?? '' );

		// Course Structure
		$sections            = max( 1, (int) ( $params['section_number'] ?? 3 ) );
		$lessons_per_section = max( 1, (int) ( $params['lessons_per_section'] ?? 5 ) );
		$quizzes_per_section = max( 0, (int) ( $params['quizzes_per_section'] ?? 1 ) );
		$questions_per_quiz  = max( 1, (int) ( $params['questions_per_quiz'] ?? 5 ) );

		$quiz_instructions = '';
		$quiz_json_example = '';
		if ( $quizzes_per_section > 0 ) {
			$quiz_instructions = <<<XML
        <quiz_requirements>
            - Each section MUST contain exactly **{$quizzes_per_section}** quiz object(s) within a "quizzes" array.
            - Each quiz MUST have a relevant "quiz_title".
            - Each quiz MUST contain exactly **{$questions_per_quiz}** question object(s) in a "questions" array.
            - Each question must be multiple-choice, testing concepts from the lessons in THAT SAME section.
            - Each question object MUST contain: "question_text", "options" (an array of 4 strings), and "correct_answer" (a string matching one of the options).
        </quiz_requirements>
XML;
			$quiz_json_example = ',' . <<<JSON
        "quizzes": [
          {
            "quiz_title": "Quiz Title Here",
            "questions": [
              {
                "question_text": "Question text here...",
                "options": ["Option A", "Option B", "Correct Option", "Option D"],
                "correct_answer": "Correct Option"
              }
            ]
          }
        ]
JSON;
		}

		$prompt = sprintf(
			<<<EOT
			<prompt>
			    <role_definition>
			        You are an AI assistant specialized in instructional design and content creation. Your persona for this task is: **%s**.
			    </role_definition>

			    <course_context>
			        <objective>
			            The primary goal of this course is: %s
			        </objective>
			        <audience>
			            The target audience is: %s
			        </audience>
			        <content_parameters>
			            <language>%s</language>
			            <tone>%s</tone>
			            <lesson_length_words>Approximately %d words per lesson</lesson_length_words>
			            <reading_level>%s</reading_level>
			        </content_parameters>
			        <seo_parameters>
			            <emphasis>%s</emphasis>
			            <keywords>%s</keywords>
			        </seo_parameters>
			    </course_context>

			    <task_instructions>
			        Your main task is to generate a complete, well-structured, and engaging online course based on all the provided context.

			        <structure_requirements>
			            - The course MUST have a compelling "course_title" and a concise "course_description".
			            - The course MUST be divided into exactly **%d** section(s).
			            - Each section MUST contain a relevant "section_title" and exactly **%d** lesson(s).
			            - Each lesson MUST have a "lesson_title" and detailed "lesson_content".
			        </structure_requirements>
			        %s
			    </task_instructions>

			    <output_format>
			        - You MUST respond with ONLY a single, valid JSON object.
			        - Do not include any introductory text, explanations, or markdown code fences like ```json.
			        - The JSON structure must strictly follow this example:
			        <json_example>
						{
						  "course_title": "Compelling Course Title Here",
						  "course_description": "A brief summary of the course.",
						  "sections": [
						    {
						      "section_title": "Section 1 Title Here",
						      "section_description": "Section 1 description Here",
						      "lessons": [
						        {
						          "lesson_title": "Lesson 1.1 Title Here",
						          "lesson_content": "Detailed content for lesson 1.1..."
						        }
						      ]%s
						    }
						  ]
						}
			        </json_example>
			    </output_format>
			</prompt>
			EOT,
			$role_persona,
			$course_objective,
			$target_audience,
			$language,
			$tone,
			$lesson_length,
			$reading_level,
			$seo_emphasis,
			$target_keywords,
			$sections,
			$lessons_per_section,
			$quiz_instructions,
			$quiz_json_example
		);

		return $prompt;
	}

	public static function get_lesson_title_prompt( $params ) {
		$topic    = $params['topic'] ?? '';
		$goal     = $params['goal'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt  = 'Create a lesson title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the lesson title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea rows="5" style="width: 100%">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-title" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_quiz_title_prompt( $params ) {
		$topic    = $params['topic'] ?? '';
		$goal     = $params['goal'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt  = 'Create a quiz title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the quiz title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea rows="5" style="width: 100%">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-title" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_question_title_prompt( $params ) {
		$topic    = $params['topic'] ?? '';
		$goal     = $params['goal'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt  = 'Create a question title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the question title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea rows="5" style="width: 100%">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-title" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	public static function get_course_image_create_prompt( $params ) {
		$data  = [];
		$model = LP_Settings::instance()->get_option( 'open_ai_image_model_type', 'dall-e-3' );

		$title         = trim( $params['title'] ?? '' );
		$description   = trim( str_replace( [ '<p>', '</p>' ], '', $params['description'] ?? '' ) );
		$image_subject = trim( $params['topic'] ?? '' );
		$quality       = trim( $params['quality'] ?? 'standard' );
		$size          = trim( $params['size'] ?? '1024x1024' );

		$style_arr = $params['style'] ?? [];
		$style     = is_array( $style_arr ) ? implode( ', ', $style_arr ) : '';

		$prompt = '';

		if ( $model === 'dall-e-3' ) {
			$prompt  = 'Create a professional and visually appealing WordPress feature image for an online course. ';
			$prompt .= "The main subject of the image must be: '$image_subject'. ";

			if ( ! empty( $title ) ) {
				$prompt .= "The image should be inspired by the course title: '$title'. ";
			}

			if ( ! empty( $description ) ) {
				$prompt .= "It should also reflect the course's content: '$description'. ";
			}

			if ( ! empty( $style ) ) {
				$prompt .= "The desired artistic style is: $style. ";
			}

			$prompt .= "Ensure the final image is $quality quality and fits a $size aspect ratio, suitable for a website banner.";

		} else {
			/**
			 * DALL-E 2 hoạt động tốt hơn với các từ khóa, cụm từ ngắn gọn, cách nhau bằng dấu phẩy.
			 */
			$prompt_parts = [];

			if ( ! empty( $image_subject ) ) {
				$prompt_parts[] = $image_subject;
			}

			if ( ! empty( $title ) ) {
				$prompt_parts[] = "for an online course titled '$title'";
			}

			if ( ! empty( $style ) ) {
				$prompt_parts[] = "$style style";
			}

			// Thêm các từ khóa bổ trợ để định hướng kết quả tốt hơn
			$prompt_parts[] = 'professional feature image';
			$prompt_parts[] = 'educational content';
			$prompt_parts[] = 'high quality';
			$prompt_parts[] = 'digital art';

			$prompt = implode( ', ', $prompt_parts );
		}

		$data['prompt'] = trim( $prompt );
		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_course_image_edit_prompt( $params ) {
		$style = '';
		if ( isset( $params['style'] ) && is_array( $params['style'] ) && count( $params['style'] ) ) {
			$style = implode( ', ', $params['style'] );
		}

		$title = $params['title'] ?? '';
		$icon  = $params['icon'] ?? '';

		$prompt  = 'Edit a WordPress feature image for course directly based on the following:\n';
		$prompt .= 'Course title: ' . $title . '\n';
		$prompt .= 'Style: ' . $style . '\n';
		$prompt .= 'Image Icon: ' . $icon . '\n';

		$data['prompt'] = $prompt;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_course_des_prompt( array $params ): array {
		$topic            = $params['topic'] ?? '';
		$title            = $params['title'] ?? '';
		$paragraph_number = $params['paragraph_number'] ?? 1;
		$characters       = $params['characters'] ?? 1000;

		// Helper inline
		$implodeOrEmpty = fn( $key ) => ! empty( $params[ $key ] ) && is_array( $params[ $key ] )
			? implode( ', ', $params[ $key ] )
			: '';

		$audience = $implodeOrEmpty( 'audience' );
		$tone     = $implodeOrEmpty( 'tone' );
		$language = $implodeOrEmpty( 'lang' );

		$prompt = <<<PROMPT
			Create a course description directly based on the following:
			Course title: {$title}
			Topic: {$topic}
			Audience: {$audience}
			Tone: {$tone}
			Paragraph number: {$paragraph_number}
			Language: {$language}

			Constraints:
			- The description must not exceed {$characters} characters.
			- Provide only the course description without any additional explanation or details.
			- Do not include quotation marks.
			PROMPT;

		return [ 'prompt' => $prompt ];
	}


	public static function get_course_curriculum_prompt( $params ) {
		$title             = $params['title'] ?? 'Untitled Course';
		$description       = $params['description'] ?? 'A general course.';
		$section_number    = (int) ( $params['section_number'] ?? 3 );
		$less_per_section  = (int) ( $params['less_per_section'] ?? 5 );
		$question_per_quiz = (int) ( $params['question_per_quiz'] ?? 3 );
		$level             = $params['level'] ?? 'All levels';
		$topic             = $params['topic'] ?? '';
		$quiz_number       = (int) ( $params['quiz_number'] ?? 0 );

		$language = 'English';
		if ( ! empty( $params['lang'] ) && is_array( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$instructions = [
			'- You will act as a world-class Instructional Design AI.',
			'- Your task is to generate a detailed course curriculum based on the provided details.',
			"- All content you generate, including titles, lessons, and quiz questions, MUST be in the following language: $language.",
		];

		$curriculum_structure = [
			"- The curriculum MUST have exactly **$section_number** section(s).",
			"- Each section MUST contain exactly **$less_per_section** lesson(s).",
		];

		if ( $quiz_number > 0 ) {
			$quiz_rules   = <<<XML
			<quiz_rules>
			    - Each section object in the JSON MUST include a "quizzes" array.
			    - The "quizzes" array for each section MUST contain exactly **$quiz_number** quiz object(s).
			    - Each quiz object MUST have a relevant "quiz_title".
			    - Each quiz object MUST have a "questions" array containing exactly **$question_per_quiz** question object(s).
			    - Each question MUST directly test concepts from the lessons within its specific section.
			    - Each question object MUST include:
			        - "question_text": The question itself.
			        - "options": An array of exactly 4 string options.
			        - "correct_answer": A string that exactly matches one of the provided options.
			</quiz_rules>
			XML;
			$json_example = <<<JSON
			{
			  "sections": [
			    {
			      "section_title": "Section Name 1: Introduction",
			      "section_description": "Section description 1",
			      "lessons": [
			        {"lesson_title": "Lesson 1.1: Title"},
			        {"lesson_title": "Lesson 1.2: Title"}
			      ],
			      "quizzes": [
			        {
			          "quiz_title": "Quiz 1 for Section 1",
			          "questions": [
			            {
			              "question_text": "A relevant question about the lessons in section 1?",
			              "options": ["Option A", "Option B", "Correct Option", "Option D"],
			              "correct_answer": "Correct Option"
			            }
			          ]
			        }
			      ]
			    }
			  ]
			}
			JSON;
		} else {
			$quiz_rules
			= "<quiz_rules>- Do NOT include the 'quizzes' key or any quiz content in any section.</quiz_rules>";
			$json_example = <<<JSON
				{
				  "sections": [
				    {
				      "section_title": "Section Name 1",
				      "section_description": "Section description 1",
				      "lessons": [
				        {"lesson_title": "Lesson Title 1.1"},
				        {"lesson_title": "Lesson Title 1.2"}
				      ]
				    }
				  ]
				}
				JSON;
		}

		$prompt = sprintf(
			<<<EOT
			<prompt>
			    <role_definition>
			        %s
			    </role_definition>

			    <course_details>
			        <title>%s</title>
			        <description>%s</description>
			        <target_level>%s</target_level>
			        <specific_topics>%s</specific_topics>
			    </course_details>

			    <instructions>
			        <curriculum_structure>
			%s
			        </curriculum_structure>
			        %s
			    </instructions>

			    <output_instructions>
			        - You MUST respond with ONLY a valid, raw JSON object.
			        - Do not include any explanations, comments, or markdown formatting like ```json.
			        - The JSON structure MUST strictly follow this example:
			        <json_example>
			%s
			        </json_example>
			        - Before generating the JSON, double-check all rules in the <instructions> section to ensure every constraint is met, especially the exact number of sections, lessons, quizzes, and questions.
			    </output_instructions>
			</prompt>
			EOT,
			implode( "\n        ", $instructions ),
			htmlspecialchars( $title ),
			htmlspecialchars( $description ),
			htmlspecialchars( $level ),
			htmlspecialchars( $topic ),
			implode( "\n", array_map( fn( $item ) => '            ' . $item, $curriculum_structure ) ),
			$quiz_rules,
			$json_example
		);

		return [ 'prompt' => $prompt ];
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_curriculum_quiz_prompt( $params ) {
		$course_title             = $params['course_title'] ?? '';
		$topic                    = $params['topic'] ?? '';
		$goal                     = $params['goal'] ?? '';
		$quiz_num                 = $params['quiz_num'] ?? 1;
		$question_per_quiz_number = $params['question_per_quiz_number'] ?? 1;

		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$question_type = '';

		if ( isset( $params['question_type'] ) && is_array( $params['question_type'] ) && count( $params['question_type'] ) ) {
			foreach ( $params['question_type'] as $key => $value ) {
				if ( $key ) {
					$question_type .= ', ';
				}
				$question_type .= $value['type'] ?? '';
			}
		} else {
			$question_type = 'single_choice, multi_choice, true_or_false, fill_in_blanks';
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt       = 'Create quizzes and questions based on the following:\n';
		$prompt      .= 'Course title: ' . $course_title . '\n';
		$prompt      .= 'Topic: ' . $topic . '\n';
		$prompt      .= 'Goal: ' . $goal . '\n';
		$prompt      .= 'Audience: ' . $audience . '\n';
		$prompt      .= 'Tone: ' . $tone . '\n';
		$prompt      .= 'Question Type: ' . $question_type . '\n';
		$prompt      .= 'Quiz number: ' . $quiz_num . '\n';
		$prompt      .= 'Question per quiz number: ' . $question_per_quiz_number . '\n';
		$prompt      .= 'Language: ' . $language . '\n';
		$prompt      .= 'Content return : JSON structure like this example, with an array that contains the number of' .
			'elements equal to ' . $quiz_num . 'and "questions" being an array that contains the number of' .
			'elements equal to:\n';
		$prompt      .= '[{"quiz_title":"What is this","questions":[{"question_type":"single_choice","question_title":"How old?","options":["a","b","c","d"],"answer":"a","description":"","points":1},{"question_type":"multi_choice","question_title":"How many?","options":["a","b","c","d","e"],"answer":["a","b","e"],"description":"","points":3},{"question_type":"true_or_false","question_title":"How do?","options":["True","False"],"answer":"True","description":""},{"question_type":"fill_in_blanks","question_title":"Is this?","question_content":"The CSS properties used to control the font size and font style of text are ___ and ___.?","answer":["a","b"],"description":""}]},{"quiz_title":"How about?","questions":[{"question_type":"single_choice","question_title":"Which?","options":["a","b","c","d"],"answer":"a","description":"","points":1}]}]';
		$prompt      .= 'The single choice and multi choices can have 1,2,3 or more options. Ensure that the result is array contains exactly "Quiz number" elements" and questions" array contains exactly "Question per quiz number" elements. Please give the correct choice answers.';
		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-curriculum-quiz" class="button">' .
			__( 'Generate with prompt', 'learnpress' ) . '</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_lesson_des_prompt( $params ) {
		$topic = $params['topic'] ?? '';
		//      $goal     = $params['goal'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt
		= 'Create a lesson description with 2 paragraphs to copy to WordPress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the lesson description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-lesson-des" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_quiz_des_prompt( $params ) {
		$topic    = $params['topic'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt
		= 'Create a quiz description with 2 paragraphs to copy to WordPress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the quiz description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-quiz-des" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_quiz_question_prompt( $params ) {
		$title       = $params['title'] ?? '';
		$description = $params['description'] ?? '';
		$topic       = $params['topic'] ?? '';
		$goal        = $params['goal'] ?? '';
		$number      = $params['number'] ?? 1;

		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$question_type = '';

		if ( isset( $params['question_type'] ) && is_array( $params['question_type'] ) && count( $params['question_type'] ) ) {
			foreach ( $params['question_type'] as $key => $value ) {
				if ( $key ) {
					$question_type .= ', ';
				}
				$question_type .= $value['type'] ?? '';
			}
		} else {
			$question_type = 'single_choice, multi_choice, true_or_false, fill_in_blanks';
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt       = 'Create a quiz question based on the following:\n';
		$prompt      .= 'Quiz title: ' . $title . '\n';
		$prompt      .= 'Description: ' . $description . '\n';
		$prompt      .= 'Topic: ' . $topic . '\n';
		$prompt      .= 'Goal: ' . $goal . '\n';
		$prompt      .= 'Audience: ' . $audience . '\n';
		$prompt      .= 'Tone: ' . $tone . '\n';
		$prompt      .= 'Question Type: ' . $question_type . '\n';
		$prompt      .= 'Question number: ' . $number . '\n';
		$prompt      .= 'Language: ' . $language . '\n';
		$prompt      .= 'Content return : JSON structure like this example, with "questions" being an array that contains the number of' .
			'elements equal to ' . $number . ':\n';
		$prompt      .= '{"questions":[{"question_type":"single_choice","question_title":"What property is' .
			'used to change the text color of an element in CSS?","options":["font-color","text-color","color","background-color"],' .
			'"answer":"color","points":1},{"question_type":"multi_choice",' .
			'"question_title":"Which of the following properties are used to control the spacing between elements in CSS?",' .
			'"options":["margin","padding","border","width","line-height"],"answer":["margin","padding","line-height"]' .
			'},{"question_type":"true_or_false","question_title":' .
			'"The z-index property in CSS controls the vertical stacking order of elements?","options":["True","False"],' .
			'"answer":"True"},{"question_type":"fill_in_blanks",' .
			'"question_title":"Fill in the blanks' .
			'"question_content":"The CSS properties used to control the font size and font style of text' .
			'are ___ and ___.","answer":["font-size","font-style"]' .
			'}]}\n';
		$prompt      .= 'The single choice and multi choices can have 1,2,3 or more options. Ensure that the "questions" array' .
			'contains exactly "Question number" elements. Please give the correct choice answers.';
		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-quiz-question" class="button">' .
			__( 'Generate with prompt', 'learnpress' ) . '</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_question_des_prompt( $params ) {
		$topic    = $params['topic'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['tone'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt
		= 'Create a question description with 2 paragraphs to copy to WordPress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the question description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html  = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-question-des" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array|string[]
	 */
	public static function get_completions_prompt( $params ) {
		$prompt = [
			'prompt'      => '',
			'prompt_html' => '',
		];

		if ( empty( $params['type'] ) ) {
			return $prompt;
		}

		switch ( $params['type'] ) {
			case 'course-title':
				$prompt = self::get_course_title_prompt( $params );
				break;
			case 'course-description':
				$prompt = self::get_course_des_prompt( $params );
				break;
			case 'course-curriculum':
				$prompt = self::get_course_curriculum_prompt( $params );
				break;
			case 'generate-full-course':
				$prompt = self::get_generate_full_course_prompt( $params );
				break;
			case 'course-feature-image':
				$prompt = self::get_course_image_create_prompt( $params );
				break;
			case 'curriculum-quiz':
				$prompt = self::get_curriculum_quiz_prompt( $params );
				break;
			case 'lesson-title':
				$prompt = self::get_lesson_title_prompt( $params );
				break;
			case 'lesson-description':
				$prompt = self::get_lesson_des_prompt( $params );
				break;
			case 'quiz-title':
				$prompt = self::get_quiz_title_prompt( $params );
				break;
			case 'quiz-description':
				$prompt = self::get_quiz_des_prompt( $params );
				break;
			case 'quiz-question':
				$prompt = self::get_quiz_question_prompt( $params );
				break;
			case 'question-title':
				$prompt = self::get_question_title_prompt( $params );
				break;
			case 'question-description':
				$prompt = self::get_question_des_prompt( $params );
				break;
			default:
		}

		return $prompt;
	}
}
