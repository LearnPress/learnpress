<?php

namespace LearnPress\Models;

use LP_Settings;

class AiModel
{
	public static function get_course_title_prompt($params)
	{
		$topic = $params['topic'] ?? '';
		$goal = $params['goal'] ?? '';
		$audience = is_array($params['audience']) && !empty($params['audience']) ? implode(', ', $params['audience']) :
			'';
		$tone = is_array($params['tone']) && !empty($params['tone']) ? implode(', ', $params['tone']) : '';
		$language = is_array($params['lang']) && !empty($params['lang']) ? implode(', ', $params['lang']) : '';

		$prompt = <<<PROMPT
				You are an expert course title creator.
				Create a concise, compelling course title with the following details:
				- Topic: {$topic}
				- Goal: {$goal}
				- Audience: {$audience}
				- Tone: {$tone}
				- Language: {$language}

				Constraints:
				- The title must be no longer than 10 words and 60 characters.
				- Do not include quotation marks
				- Do not add explanation or extra text

				Output: Only the course title as plain text.
				PROMPT;

		return [
			'prompt' => $prompt,
		];
	}

	public static function get_lesson_title_prompt($params)
	{
		$topic = $params['topic'] ?? '';
		$goal = $params['goal'] ?? '';
		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt = 'Create a lesson title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the lesson title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea rows="5" style="width: 100%">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-title" class="button">Generate with prompt</button>';

		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_quiz_title_prompt($params)
	{
		$topic = $params['topic'] ?? '';
		$goal = $params['goal'] ?? '';
		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt = 'Create a quiz title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the quiz title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea rows="5" style="width: 100%">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-title" class="button">Generate with prompt</button>';

		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_question_title_prompt($params)
	{
		$topic = $params['topic'] ?? '';
		$goal = $params['goal'] ?? '';
		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt = 'Create a question title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the question title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea rows="5" style="width: 100%">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-title" class="button">Generate with prompt</button>';

		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	public static function get_course_image_create_prompt($params)
	{
		$data = [];
		$model = LP_Settings::instance()->get_option('open_ai_image_model_type', 'dall-e-3');

		$title         = trim($params['title'] ?? '');
		$description   = trim(str_replace(['<p>', '</p>'], '', $params['description'] ?? ''));
		$image_subject = trim($params['topic'] ?? '');
		$quality       = trim($params['quality'] ?? 'standard');
		$size          = trim($params['size'] ?? '1024x1024');

		$style_arr = $params['style'] ?? [];
		$style     = is_array($style_arr) ? implode(', ', $style_arr) : '';

		$prompt = '';

		if ($model === 'dall-e-3') {
			$prompt = "Create a professional and visually appealing WordPress feature image for an online course. ";
			$prompt .= "The main subject of the image must be: '$image_subject'. ";

			if (!empty($title)) {
				$prompt .= "The image should be inspired by the course title: '$title'. ";
			}

			if (!empty($description)) {
				$prompt .= "It should also reflect the course's content: '$description'. ";
			}

			if (!empty($style)) {
				$prompt .= "The desired artistic style is: $style. ";
			}

			$prompt .= "Ensure the final image is $quality quality and fits a $size aspect ratio, suitable for a website banner.";

		} else {
			/**
			 * DALL-E 2 hoạt động tốt hơn với các từ khóa, cụm từ ngắn gọn, cách nhau bằng dấu phẩy.
			 */
			$prompt_parts = [];

			if (!empty($image_subject)) {
				$prompt_parts[] = $image_subject;
			}

			if (!empty($title)) {
				$prompt_parts[] = "for an online course titled '$title'";
			}

			if (!empty($style)) {
				$prompt_parts[] = "$style style";
			}

			// Thêm các từ khóa bổ trợ để định hướng kết quả tốt hơn
			$prompt_parts[] = 'professional feature image';
			$prompt_parts[] = 'educational content';
			$prompt_parts[] = 'high quality';
			$prompt_parts[] = 'digital art';

			$prompt = implode(', ', $prompt_parts);
		}

		$data['prompt'] = trim($prompt);
		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_course_image_edit_prompt($params)
	{
		$style = '';
		if (isset($params['style']) && is_array($params['style']) && count($params['style'])) {
			$style = implode(', ', $params['style']);
		}

		$title = $params['title'] ?? '';
		$icon = $params['icon'] ?? '';


		$prompt = 'Edit a wordpress feature image for course directly based on the following:\n';
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
	public static function get_course_des_prompt(array $params): array
	{
		$topic = $params['topic'] ?? '';
		$title = $params['title'] ?? '';
		$paragraph_number = $params['paragraph_number'] ?? 1;
		$max_length = $params['max_length'] ?? 300; // default 300 ký tự

		// Helper inline
		$implodeOrEmpty = fn($key) => !empty($params[$key]) && is_array($params[$key])
			? implode(', ', $params[$key])
			: '';

		$audience = $implodeOrEmpty('audience');
		$tone = $implodeOrEmpty('tone');
		$language = $implodeOrEmpty('lang');

		$prompt = <<<PROMPT
			Create a course description directly based on the following:
			Course title: {$title}
			Topic: {$topic}
			Audience: {$audience}
			Tone: {$tone}
			Paragraph number: {$paragraph_number}
			Language: {$language}

			Constraints:
			- The description must not exceed {$max_length} characters.
			- Provide only the course description without any additional explanation or details.
			- Do not include quotation marks.
			PROMPT;

		return ['prompt' => $prompt];
	}


	public static function get_course_curriculum_prompt($params)
	{
		$title = $params['title'] ?? 'Untitled Course';
		$description = $params['description'] ?? 'A general course.';
		$section_number = (int)($params['section_number'] ?? 3);
		$less_per_section = (int)($params['less_per_section'] ?? 5);
		$level = $params['level'] ?? 'All levels';
		$topic = $params['topic'] ?? '';

		$questions_per_quiz = (int)($params['quiz_number'] ?? 0);

		$language = 'English';
		if (!empty($params['lang']) && is_array($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}


		if ($questions_per_quiz > 0) {

			$quiz_instruction
				= "- Each section MUST include a quiz with a relevant title and exactly $questions_per_quiz multiple-choice questions.\n- Each question MUST directly test the concepts from the lessons within that specific section.\n";

			$json_example = <<<JSON
				{
				  "sections": [
				    {
				      "section_title": "Section Name 1: Introduction to Topic",
				      "lessons": [
				        {"lesson_title": "Lesson 1.1: What is Topic X?"},
				        {"lesson_title": "Lesson 1.2: Key Principles of Topic X"}
				      ],
				      "quiz": {
				        "quiz_title": "Quiz for Section 1",
				        "questions": [
				          {
				            "question_text": "What is the primary subject of Lesson 1.1?",
				            "options": ["Answer A", "Answer B", "Topic X", "Answer D"],
				            "correct_answer": "Topic X"
				          }
				        ]
				      }
				    }
				  ]
				}
				JSON;
		} else {

			$quiz_instruction = "- Do NOT include any quizzes in the sections.\n";
			$json_example = <<<JSON
			{
			  "sections": [
			    {
			      "section_title": "Section Name 1",
			      "lessons": [
			        {"lesson_title": "Lesson Title 1.1"},
			        {"lesson_title": "Lesson Title 1.2"}
			      ]
			    }
			  ]
			}
			JSON;
		}

		$prompt = <<<EOT
			CONTEXT:
			You are an expert instructional designer creating a curriculum and associated quiz content for an online course.
			Course Title: $title
			Course Description: $description
			Target Level: $level
			Specific Key Topics to Include: $topic

			TASK:
			Generate a detailed course curriculum based on the context. If quizzes are requested, create relevant multiple-choice questions for each section that are directly based on the lesson titles you generate for that section.

			OUTPUT FORMAT:
			- You MUST respond with ONLY a single, valid JSON object.
			- The JSON object must follow this exact structure. Below is an example:
			$json_example

			CONSTRAINTS:
			- The output MUST be a raw JSON object, without any surrounding text, explanations, or markdown fences like \`\`\`json.
			- Generate exactly $section_number sections.
			- Each section must contain exactly $less_per_section lessons.
			$quiz_instruction- The language for all generated content (titles, questions, options) MUST be: $language.
			EOT;

		return ['prompt' => $prompt];
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_curriculum_quiz_prompt($params)
	{
		$course_title = $params['course_title'] ?? '';
		$topic = $params['topic'] ?? '';
		$goal = $params['goal'] ?? '';
		$quiz_num = $params['quiz_num'] ?? 1;
		$question_per_quiz_number = $params['question_per_quiz_number'] ?? 1;

		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$question_type = '';

		if (isset($params['question_type']) && is_array($params['question_type']) && count($params['question_type'])) {
			foreach ($params['question_type'] as $key => $value) {
				if ($key) {
					$question_type .= ', ';
				}
				$question_type .= $value['type'] ?? '';
			}
		} else {
			$question_type = 'single_choice, multi_choice, true_or_false, fill_in_blanks';
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt = 'Create quizzes and questions based on the following:\n';
		$prompt .= 'Course title: ' . $course_title . '\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Question Type: ' . $question_type . '\n';
		$prompt .= 'Quiz number: ' . $quiz_num . '\n';
		$prompt .= 'Question per quiz number: ' . $question_per_quiz_number . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Content return : JSON structure like this example, with an array that contains the number of' .
			'elements equal to ' . $quiz_num . 'and "questions" being an array that contains the number of' .
			'elements equal to:\n';
		$prompt .= '[{"quiz_title":"What is this","questions":[{"question_type":"single_choice","question_title":"How old?","options":["a","b","c","d"],"answer":"a","description":"","points":1},{"question_type":"multi_choice","question_title":"How many?","options":["a","b","c","d","e"],"answer":["a","b","e"],"description":"","points":3},{"question_type":"true_or_false","question_title":"How do?","options":["True","False"],"answer":"True","description":""},{"question_type":"fill_in_blanks","question_title":"Is this?","question_content":"The CSS properties used to control the font size and font style of text are ___ and ___.?","answer":["a","b"],"description":""}]},{"quiz_title":"How about?","questions":[{"question_type":"single_choice","question_title":"Which?","options":["a","b","c","d"],"answer":"a","description":"","points":1}]}]';
		$prompt .= 'The single choice and multi choices can have 1,2,3 or more options. Ensure that the result is array contains exactly "Quiz number" elements" and questions" array contains exactly "Question per quiz number" elements. Please give the correct choice answers.';
		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-curriculum-quiz" class="button">' .
			__('Generate with prompt', 'learnpress') . '</button>';


		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_lesson_des_prompt($params)
	{
		$topic = $params['topic'] ?? '';
//		$goal     = $params['goal'] ?? '';
		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt
			= 'Create a lesson description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the lesson description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-lesson-des" class="button">Generate with prompt</button>';

		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_quiz_des_prompt($params)
	{
		$topic = $params['topic'] ?? '';
		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt
			= 'Create a quiz description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the quiz description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-quiz-des" class="button">Generate with prompt</button>';

		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_quiz_question_prompt($params)
	{
		$title = $params['title'] ?? '';
		$description = $params['description'] ?? '';
		$topic = $params['topic'] ?? '';
		$goal = $params['goal'] ?? '';
		$number = $params['number'] ?? 1;

		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$question_type = '';

		if (isset($params['question_type']) && is_array($params['question_type']) && count($params['question_type'])) {
			foreach ($params['question_type'] as $key => $value) {
				if ($key) {
					$question_type .= ', ';
				}
				$question_type .= $value['type'] ?? '';
			}
		} else {
			$question_type = 'single_choice, multi_choice, true_or_false, fill_in_blanks';
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt = 'Create a quiz question based on the following:\n';
		$prompt .= 'Quiz title: ' . $title . '\n';
		$prompt .= 'Description: ' . $description . '\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Question Type: ' . $question_type . '\n';
		$prompt .= 'Question number: ' . $number . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Content return : JSON structure like this example, with "questions" being an array that contains the number of' .
			'elements equal to ' . $number . ':\n';
		$prompt .= '{"questions":[{"question_type":"single_choice","question_title":"What property is' .
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
		$prompt .= 'The single choice and multi choices can have 1,2,3 or more options. Ensure that the "questions" array' .
			'contains exactly "Question number" elements. Please give the correct choice answers.';
		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-quiz-question" class="button">' .
			__('Generate with prompt', 'learnpress') . '</button>';


		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_question_des_prompt($params)
	{
		$topic = $params['topic'] ?? '';
		$audience = '';

		if (isset($params['audience']) && is_array($params['audience']) && count($params['audience'])) {
			$audience = implode(', ', $params['audience']);
		}

		$tone = '';

		if (isset($params['tone']) && is_array($params['tone']) && count($params['tone'])) {
			$tone = implode(', ', $params['tone']);
		}

		$language = '';

		if (isset($params['lang']) && is_array($params['lang']) && count($params['lang'])) {
			$language = implode(', ', $params['lang']);
		}

		$prompt
			= 'Create a question description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the question description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-question-des" class="button">Generate with prompt</button>';

		$data['prompt'] = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array|string[]
	 */
	public static function get_completions_prompt($params)
	{
		$prompt = [
			'prompt'      => '',
			'prompt_html' => ''
		];

		if (empty($params['type'])) {
			return $prompt;
		}

		switch ($params['type']) {
			case 'course-title':
				$prompt = self::get_course_title_prompt($params);
				break;
			case 'course-description':
				$prompt = self::get_course_des_prompt($params);
				break;
			case 'course-curriculum':
				$prompt = self::get_course_curriculum_prompt($params);
				break;
			case 'course-feature-image':
				$prompt = self::get_course_image_create_prompt($params);
				break;
			case 'curriculum-quiz':
				$prompt = self::get_curriculum_quiz_prompt($params);
				break;
			case 'lesson-title':
				$prompt = self::get_lesson_title_prompt($params);
				break;
			case 'lesson-description':
				$prompt = self::get_lesson_des_prompt($params);
				break;
			case 'quiz-title':
				$prompt = self::get_quiz_title_prompt($params);
				break;
			case 'quiz-description':
				$prompt = self::get_quiz_des_prompt($params);
				break;
			case 'quiz-question':
				$prompt = self::get_quiz_question_prompt($params);
				break;
			case 'question-title':
				$prompt = self::get_question_title_prompt($params);
				break;
			case 'question-description':
				$prompt = self::get_question_des_prompt($params);
				break;
			default:
		}

		return $prompt;
	}
}
