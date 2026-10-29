<?php
/**
 * Template hooks Quiz.
 *
 * HTML of the quiz, not depend on user.
 *
 * @since 4.5.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Learning;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\QuizPostModel;
use LP_Duration;

defined( 'ABSPATH' ) || exit;

class QuizTemplate {
	use Singleton;

	public function init(): void {}

	/**
	 * HTML of the quiz description.
	 *
	 * @param QuizPostModel $quizPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_description( QuizPostModel $quizPostModel ): string {
		$section = [
			'wrapper'     => '<div class="quiz-content">',
			'content'     => $quizPostModel->get_the_content(),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/quiz/html-description', $section, $quizPostModel )
		);
	}

	/**
	 * HTML of the quiz meta: duration, passing grade, number of questions.
	 *
	 * @param QuizPostModel $quizPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_meta( QuizPostModel $quizPostModel ): string {
		$duration = ( new LP_Duration( $quizPostModel->get_duration() ) )->get();
		$grade    = $quizPostModel->get_passing_grade();

		$fields = apply_filters(
			'learn-press/quiz/meta-fields',
			[
				'duration'        => [
					'title'   => __( 'Duration:', 'learnpress' ),
					'content' => $duration > 0 ? $this->format_duration( $duration ) : '--',
				],
				'passing-grade'   => [
					'title'   => __( 'Passing grade:', 'learnpress' ),
					'content' => $grade > 0 ? (string) $grade : '--',
				],
				'questions-count' => [
					'title'   => __( 'Questions:', 'learnpress' ),
					'content' => (string) $quizPostModel->count_questions(),
				],
			],
			$quizPostModel
		);

		$html_items = '';
		foreach ( $fields as $name => $field ) {
			$html_items .= sprintf(
				'<li class="quiz-intro-item quiz-intro-item--%1$s"><div class="quiz-intro-item__title">%2$s</div><span class="quiz-intro-item__content">%3$s</span></li>',
				esc_attr( $name ),
				wp_kses_post( $field['title'] ?? '' ),
				wp_kses_post( $field['content'] ?? '' )
			);
		}

		$section = [
			'wrapper'     => '<ul class="quiz-intro">',
			'items'       => $html_items,
			'wrapper_end' => '</ul>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/quiz/html-meta', $section, $quizPostModel )
		);
	}

	/**
	 * Format seconds to HH:MM:SS.
	 *
	 * @param int $seconds
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function format_duration( int $seconds ): string {
		$seconds = max( 0, $seconds );

		return sprintf(
			'%02d:%02d:%02d',
			floor( $seconds / 3600 ),
			floor( ( $seconds % 3600 ) / 60 ),
			$seconds % 60
		);
	}
}
