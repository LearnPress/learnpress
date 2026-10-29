<?php
namespace LearnPress\TemplateHooks\Learning;

use LearnPress\Helpers\LPDateTime;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\QuizPostModel;
use LP_Duration;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Template hooks Quiz.
 *
 * HTML of the quiz, not depend on user.
 *
 * @since 4.5.0
 * @version 1.0.0
 */
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
					'content' => $this->html_duration( $quizPostModel ),
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
				'<li class="quiz-intro-item quiz-intro-item--%1$s">
							<div class="quiz-intro-item__title">%2$s</div>
							<span class="quiz-intro-item__content">%3$s</span>
						</li>',
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
	 * Get html duration course.
	 *
	 * @param QuizPostModel $quizPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_duration( QuizPostModel $quizPostModel, array $args = [] ): string {
		$content = '';

		try {
			$duration        = $quizPostModel->get_duration();
			$duration_arr    = explode( ' ', $duration );
			$duration_number = floatval( $duration_arr[0] ?? 0 );
			$duration_type   = $duration_arr[1] ?? '';
			if ( empty( $duration_number ) ) {
				$duration_str = __( 'Unlimited', 'learnpress' );
			} else {
				$duration_str = LPDateTime::get_string_plural_duration( $duration_number, $duration_type );
			}

			$html_wrapper = [
				'<span class="question-duration">' => '</span>',
			];
			$content      = Template::instance()->nest_elements( $html_wrapper, $duration_str );
		} catch ( Throwable $e ) {
			$content = Template::print_message(
				$e->getMessage(),
				'error',
				false
			);
		}

		return $content;
	}
}
