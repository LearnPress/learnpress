<?php

namespace LearnPress\Models;

class AiModel
{
	public static function get_course_title_prompt( $params ) {
		$topic    = $params['topic'] ?? '';
		$goal     = $params['goal'] ?? '';
		$audience = is_array( $params['audience'] ) && !empty( $params['audience'] ) ? implode(', ', $params['audience']) : '';
		$tone     = is_array( $params['tone'] ) && !empty( $params['tone'] ) ? implode(', ', $params['tone']) : '';
		$language = is_array( $params['lang'] ) && !empty( $params['lang'] ) ? implode(', ', $params['lang']) : '';

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
		$style = '';
		if ( isset( $params['style'] ) && is_array( $params['style'] ) && count( $params['style'] ) ) {
			$style = implode( ', ', $params['style'] );
		}

		$title       = $params['title'] ?? '';
		$description = $params['description'] ?? '';
		$icon        = $params['icon'] ?? '';

		$prompt = 'Create a wordpress feature image for course directly based on the following:\n';
		$prompt .= 'Course title: ' . $title . '\n';
		$prompt .= 'Description: ' . str_replace( [ '<p>', '</p>' ], [ "\n", '' ], $description ) . '\n';
		$prompt .= 'Style: ' . $style . '\n';
		if(!empty($icon)){
			$prompt .= 'Image Icon: ' . $icon . '\n';
		}


		return $prompt;
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


		$prompt = 'Edit a wordpress feature image for course directly based on the following:\n';
		$prompt .= 'Course title: ' . $title . '\n';
		$prompt .= 'Style: ' . $style . '\n';
		$prompt .= 'Image Icon: ' . $icon . '\n';

		$data['prompt']      = $prompt;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_course_des_prompt(array $params): array {
		$topic            = $params['topic'] ?? '';
		$title            = $params['title'] ?? '';
		$paragraph_number = $params['paragraph_number'] ?? 1;
		$max_length       = $params['max_length'] ?? 300; // default 300 ký tự

		// Helper inline
		$implodeOrEmpty = fn($key) => !empty($params[$key]) && is_array($params[$key])
			? implode(', ', $params[$key])
			: '';

		$audience = $implodeOrEmpty('audience');
		$tone     = $implodeOrEmpty('tone');
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


	public static function get_course_curriculum_prompt( $params ) {
		$title            = $params['title'] ?? '';
		$description      = $params['description'] ?? '';
		$section_number   = $params['section_number'] ?? 1;
		$less_per_section = $params['less_per_section'] ?? 1;
		$level            = $params['level'] ?? 'All levels';
		$topic            = $params['topic'] ?? '';

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt      = 'Create a course curriculum based on the following:\n';
		$prompt      .= 'Course title: ' . $title . '\n';
		$prompt      .= 'Description: ' . $description . '\n';
		$prompt      .= 'Section number: ' . $section_number . '\n';
		$prompt      .= 'Lesson per section: ' . $less_per_section . '\n';
		$prompt      .= 'Level: ' . $level . '\n';
		$prompt      .= 'Specific key topics: ' . $topic . '\n';
		$prompt      .= 'Language: ' . $language . '\n';
		$prompt      .= 'Content return : JSON structure only in this exact format like this example:{"sections":[{"section_title": "Section Name","lessons": [{"lesson_title": "Lesson Title 1"},{"lesson_title": "Lesson Title 2"}]}]}\n';
		$prompt      .= 'Make sure to provide only ' . $section_number . ' sections with exactly ' . $less_per_section . ' lessons each, and no additional data or formatting.\n';


		$data['prompt']      = $prompt;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_curriculum_quiz_prompt( $params ) {
		$course_title       = $params['course_title'] ?? '';
		$topic       = $params['topic'] ?? '';
		$goal        = $params['goal'] ?? '';
		$quiz_num      = $params['quiz_num'] ?? 1;
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

		$prompt      = 'Create quizzes and questions based on the following:\n';
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
		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-curriculum-quiz" class="button">' . __( 'Generate with prompt', 'learnpress' ) . '</button>';


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
//		$goal     = $params['goal'] ?? '';
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

		$prompt = 'Create a lesson description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
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

		$prompt = 'Create a quiz description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
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

		$prompt      = 'Create a quiz question based on the following:\n';
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
		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-quiz-question" class="button">' . __( 'Generate with prompt', 'learnpress' ) . '</button>';


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

		$prompt = 'Create a question description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
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
		$prompt = array(
			'prompt'      => '',
			'prompt_html' => ''
		);

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
