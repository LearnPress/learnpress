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
			$tone = implode( ', ', $params['audience'] );
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
		$prompt_html .= '<p>Create a course title based on the following:</br>';
		$prompt_html .= 'Topic: ' . $topic . '</br>';
		$prompt_html .= 'Goal: ' . $goal . '</br>';
		$prompt_html .= 'Audience: ' . $audience . '</br>';
		$prompt_html .= 'Tone: ' . $tone . '</br>';
		$prompt_html .= 'Language: ' . $language . '</br>';
		$prompt_html .= 'Please provide only the course title without any additional explanation or details, and do not include quotation marks.';
		$prompt_html .= '</p>';

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

		$icon    = $params['icon'] ?? '';


		$prompt = 'Edit a wordpress feature image for course post type directly based on the following:\n';
		$prompt .= 'Style: ' . $style . '\n';
		$prompt .= 'Image Icon: ' . $icon . '\n';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<p>Edit a wordpress feature image for course post type directly based on the following:</br>';
		$prompt_html .= 'Style: ' . $style . '</br>';
		$prompt_html .= 'Image Icon: ' . $icon . '</br>';
		$prompt_html .= '</p>';

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
//		$goal     = $params['goal'] ?? '';
		$audience = '';

		if ( isset( $params['audience'] ) && is_array( $params['audience'] ) && count( $params['audience'] ) ) {
			$audience = implode( ', ', $params['audience'] );
		}

		$tone = '';

		if ( isset( $params['tone'] ) && is_array( $params['tone'] ) && count( $params['tone'] ) ) {
			$tone = implode( ', ', $params['audience'] );
		}

		$language = '';

		if ( isset( $params['lang'] ) && is_array( $params['lang'] ) && count( $params['lang'] ) ) {
			$language = implode( ', ', $params['lang'] );
		}

		$prompt = 'Create a course description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:\n';
		$prompt .= 'Topic: ' . $topic . '\n';
//		$prompt .= 'Goal: ' . $goal . '\n';
		$prompt .= 'Audience: ' . $audience . '\n';
		$prompt .= 'Tone: ' . $tone . '\n';
		$prompt .= 'Language: ' . $language . '\n';
		$prompt .= 'Please provide only the course description without any additional explanation or details, and do not include quotation marks.';

		$prompt_html = '<div class="title"><strong>Prompt:</strong></div>';
		$prompt_html .= '<p>Create a course description with 2 paragraphs to copy to wordpress editor content tag directly based on the following:</br>';
		$prompt_html .= 'Topic: ' . $topic . '</br>';
//		$prompt_html .= 'Goal: ' . $goal . '</br>';
		$prompt_html .= 'Audience: ' . $audience . '</br>';
		$prompt_html .= 'Tone: ' . $tone . '</br>';
		$prompt_html .= 'Language: ' . $language . '</br>';
		$prompt_html .= 'Please provide only the course description without any additional explanation or details, and do not include quotation marks.';
		$prompt_html .= '</p>';

		$data['prompt']      = $prompt;
		$data['prompt_html'] = $prompt_html;

		return $data;
	}

}
