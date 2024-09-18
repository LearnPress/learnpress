<?php

namespace LearnPress\Helpers;

class OpenAi {
	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_course_title_prompt( $params ) {
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

		$prompt = 'Create a course title directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the course title without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea rows="5" style="width: 100%">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-title" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
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
	 * @return array
	 */
	public static function get_course_image_create_prompt( $params ) {
		$style = '';
		if ( isset( $params['style'] ) && is_array( $params['style'] ) && count( $params['style'] ) ) {
			$style = implode( ', ', $params['style'] );
		}

		$title    = $params['title'] ?? '';
		$icon    = $params['icon'] ?? '';

		$prompt = 'Create a wordpress feature image for course directly based on the following:\n';
		$prompt .= 'Title: ' . $title . '\n';
		$prompt .= 'Style: ' . $style . '\n';
		$prompt .= 'Image Icon: ' . $icon . '\n';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .=  $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-create-fi" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

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

		$title    = $params['title'] ?? '';
		$icon    = $params['icon'] ?? '';


		$prompt = 'Edit a wordpress feature image for course directly based on the following:\n';
		$prompt .= 'Title: ' . $title . '\n';
		$prompt .= 'Style: ' . $style . '\n';
		$prompt .= 'Image Icon: ' . $icon . '\n';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .=  $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-edit-fi" class="button">Generate with prompt</button>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

	/**
	 * @param $params
	 *
	 * @return array
	 */
	public static function get_course_des_prompt( $params ) {
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

		$prompt = 'Create a course description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the course description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<textarea style="width: 100%" rows="5">';
		$prompt_html .= $prompt;
		$prompt_html .= '</textarea>\n';
		$prompt_html .= '<button id="lp-re-generate-course-des" class="button">Generate with prompt</button>';


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
		$topic    = $params['topic'] ?? '';
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
	 * @return array|string
	 */
	public static function get_completions_prompt($params){
		$prompt = '';
		if( empty($params['type']) ){
			return $prompt;
		}

		switch ($params['type']) {
			case 'course-title':
				$prompt = self::get_course_title_prompt($params);
				break;
			case 'course-description':
				$prompt = self::get_course_des_prompt($params);
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