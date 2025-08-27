<?php

namespace LearnPress\Models\Question;

/**
 * Class QuestionPostFIBModel
 * Question type Fill in the Blank
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.9
 */
class QuestionPostFIBModel extends QuestionPostModel {
	public $question_type = 'fill_in_blanks';

	/**
	 * Create default answers for question
	 *
	 * @return array[]
	 */
	public function get_default_answers(): array {
		return array(
			array(
				'value' => $this->random_value(),
				'title' => '',
			),
		);
	}

	/**
	 * Convert content to format [fib fill="" id="" ]
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function convert_content_from_editor_to_db( string $content ): string {
		$pattern = '#<span class="lp-question-fib-input" data-id="([^"]+)">([^<]+)<\/span>#';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) {
				$id   = $matches[1];
				$fill = $matches[2];
				return '[fib fill="' . $fill . '" id="' . $id . '" ]';
			},
			$content
		);
	}
}
