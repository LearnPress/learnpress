<?php
/**
 * Template hooks Question.
 *
 * HTML of the question, render by state of user (doing, checked, review) passed via $args.
 * Markup keeps the class names of the old React app (question-types), so the old style still applies.
 *
 * @since 4.5.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Learning;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionPostModel;
use LP_Debug;
use Throwable;

defined( 'ABSPATH' ) || exit;

class QuestionTemplate {
	use Singleton;

	public function init(): void {}

	/**
	 * Parse args state of question answers.
	 *
	 * status: status of user quiz (started, completed).
	 * answered: answer of user, string (single), array (multi, sorting), [blank_id => fill] (FIB).
	 * show_result: show result of user's answer (checked by instant check or quiz completed).
	 * show_correct: show correct answers, only when show_result (option "Show the correct answer").
	 * correct: result of the question from server (bool|null).
	 * mark: mark of user for the question.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function parse_answer_args( array $args = [] ): array {
		$args = array_merge(
			[
				'status'       => '',
				'answered'     => null,
				'show_result'  => false,
				'show_correct' => false,
				'correct'      => null,
				'mark'         => 0,
			],
			$args
		);

		$args['show_result'] = (bool) $args['show_result'];
		// Never show correct answers when not show result, same as learn_press_rest_prepare_user_questions.
		$args['show_correct'] = $args['show_result'] && $args['show_correct'];

		return $args;
	}

	/**
	 * HTML of a question: title, content, answers, explanation, hint, footer.
	 *
	 * Args extend parse_answer_args():
	 * index: number show before title (0 to hide).
	 * show_explanation: show explanation (question checked or quiz completed).
	 * edit_link: link edit question (only for user can edit).
	 * footer: HTML buttons below question (instant check...).
	 * page: page of question in quiz.
	 * hidden: hide question (not in current page).
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_question( QuestionPostModel $questionPostModel, array $args = [] ): string {
		$args = array_merge(
			[
				'index'            => 0,
				'show_explanation' => false,
				'edit_link'        => '',
				'footer'           => '',
				'page'             => 1,
				'hidden'           => false,
			],
			$this->parse_answer_args( $args )
		);

		$classes = [ 'question', 'question-' . $questionPostModel->get_type() ];
		if ( $args['show_correct'] ) {
			$classes[] = 'question-answered';
		}

		$explanation = $args['show_explanation'] ? (string) $questionPostModel->get_explanation() : '';
		// Hint (button + content) not show when has explanation, same as old.
		$args['show_hint'] = '' === $explanation && '' !== (string) $questionPostModel->get_hint();

		$section = [
			'wrapper'     => sprintf(
				'<div class="%1$s" data-id="%2$d" data-page="%3$d"%4$s>',
				esc_attr( implode( ' ', $classes ) ),
				$questionPostModel->get_id(),
				(int) $args['page'],
				$args['hidden'] ? ' style="display: none;"' : ''
			),
			'title'       => $this->html_title( $questionPostModel, $args ),
			'hint'        => $args['show_hint'] ? $this->html_hint( $questionPostModel ) : '',
			'content'     => $this->html_content( $questionPostModel ),
			'answers'     => $this->html_answers( $questionPostModel, $args ),
			'explanation' => $this->html_explanation( $explanation ),
			'footer'      => $args['footer'],
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/question/html-question', $section, $questionPostModel, $args )
		);
	}

	/**
	 * HTML title of question: index, title, button hint, link edit.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_title( QuestionPostModel $questionPostModel, array $args = [] ): string {
		$index     = (int) ( $args['index'] ?? 0 );
		$edit_link = (string) ( $args['edit_link'] ?? '' );
		$show_hint = $args['show_hint'] ?? ( '' !== (string) $questionPostModel->get_hint() );

		$section = [
			'wrapper'     => '<h4 class="question-title">',
			'index'       => $index > 0 ? sprintf( '<span class="question-index">%d.</span>', $index ) : '',
			'title'       => sprintf( '<span>%s</span>', wp_kses_post( $questionPostModel->get_the_title() ) ),
			'hint'        => $show_hint
				? sprintf(
					'<span class="btn-show-hint"><span>%s</span></span>',
					esc_html__( 'View hint', 'learnpress' )
				)
				: '',
			'edit_link'   => $edit_link
				? sprintf( '<span class="edit-link"><a href="%s">%s</a></span>', esc_url( $edit_link ), esc_html__( 'Edit', 'learnpress' ) )
				: '',
			'wrapper_end' => '</h4>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/question/html-title', $section, $questionPostModel, $args )
		);
	}

	/**
	 * HTML content of question.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_content( QuestionPostModel $questionPostModel ): string {
		$content = learn_press_get_question_content_display( $questionPostModel );
		if ( empty( $content ) ) {
			return '';
		}

		return sprintf( '<div class="question-content">%s</div>', $content );
	}

	/**
	 * HTML explanation of question.
	 *
	 * @param string $explanation
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_explanation( string $explanation ): string {
		if ( '' === $explanation ) {
			return '';
		}

		return sprintf(
			'<div class="question-explanation-content"><strong class="explanation-title">%s:</strong><div>%s</div></div>',
			esc_html__( 'Explanation', 'learnpress' ),
			wp_kses_post( $explanation )
		);
	}

	/**
	 * HTML hint of question, hidden until user click button hint.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_hint( QuestionPostModel $questionPostModel ): string {
		$hint = (string) $questionPostModel->get_hint();
		if ( '' === $hint ) {
			return '';
		}

		return sprintf(
			'<div class="question-hint-content" style="display: none;"><strong class="hint-title">%s:</strong><div>%s</div></div>',
			esc_html__( 'Hint', 'learnpress' ),
			wp_kses_post( $hint )
		);
	}

	/**
	 * HTML answer options of question by type.
	 * Add-on can render own type via filter 'learn-press/question/html-answers'.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args see parse_answer_args()
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_answers( QuestionPostModel $questionPostModel, array $args = [] ): string {
		$html = '';
		$args = $this->parse_answer_args( $args );
		$type = (string) $questionPostModel->get_type();

		try {
			switch ( $type ) {
				case 'true_or_false':
				case 'single_choice':
				case 'multi_choice':
					$html = $this->html_answers_choice( $questionPostModel, $args );
					break;
				case 'fill_in_blanks':
					$html = $this->html_answers_fib( $questionPostModel, $args );
					break;
				case 'sorting_choice':
					$html = $this->html_answers_sorting( $questionPostModel, $args );
					break;
				default:
					break;
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		$html = apply_filters( 'learn-press/question/html-answers', $html, $questionPostModel, $args );

		if ( '' === $html ) {
			$html = sprintf(
				'<div class="question-types">%s</div>',
				sprintf(
					/* translators: %s: question type */
					esc_html__( 'Question %s invalid!', 'learnpress' ),
					'<code>' . esc_html( $type ) . '</code>'
				)
			);
		}

		return $html;
	}

	/**
	 * Get answer options of question for display.
	 * Not include is_true when not show correct answers, avoid leaking correct answers.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_answer_options( QuestionPostModel $questionPostModel, array $args ): array {
		$exclude = [ 'question_id', 'order' ];
		if ( ! $args['show_correct'] ) {
			$exclude[] = 'is_true';
		}

		return array_values(
			$questionPostModel->get_answer_options(
				[
					'exclude'     => $exclude,
					'map'         => [ 'question_answer_id' => 'uid' ],
					'answer'      => $args['answered'],
					'show_result' => $args['show_result'],
				]
			)
		);
	}

	/**
	 * HTML answers of type true_or_false, single_choice (radio), multi_choice (checkbox).
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_answers_choice( QuestionPostModel $questionPostModel, array $args ): string {
		$question_id = $questionPostModel->get_id();
		$is_multi    = 'multi_choice' === $questionPostModel->get_type();
		$is_started  = 'started' === $args['status'];
		$disabled    = $args['show_result'] || ! $is_started;
		$answered    = $args['answered'];

		if ( $is_multi ) {
			$answered = array_map( 'strval', (array) $answered );
		} else {
			$answered = is_scalar( $answered ) ? (string) $answered : '';
		}

		$html_options = '';
		foreach ( $this->get_answer_options( $questionPostModel, $args ) as $index => $option ) {
			$value = (string) ( $option['value'] ?? '' );
			$uid   = (string) ( $option['uid'] ?? '' );
			if ( '' === $uid ) {
				$uid = $question_id . '-' . $index;
			}

			$is_selected = $is_multi ? in_array( $value, $answered, true ) : ( '' !== $answered && $value === $answered );
			$is_true     = 'yes' === ( $option['is_true'] ?? '' );

			$classes = [ 'answer-option' ];
			if ( $args['show_correct'] && $is_true ) {
				$classes[] = 'answer-correct';
			}

			// is_true not send when not show correct answers, use result of server.
			if ( $args['show_result'] && $is_selected ) {
				$classes[] = ( $is_true || ! empty( $args['correct'] ) ) ? 'answered-correct' : 'answered-wrong';
			}

			$input_id      = 'learn-press-answer-option-' . $uid;
			$html_options .= sprintf(
				'<li class="%1$s"><input type="%2$s" class="option-check" name="%3$s" id="%4$s" value="%5$s"%6$s%7$s/><label for="%4$s" class="option-title">%8$s</label></li>',
				esc_attr( implode( ' ', $classes ) ),
				$is_multi ? 'checkbox' : 'radio',
				$is_started ? esc_attr( 'learn-press-question-' . $question_id ) : '',
				esc_attr( $input_id ),
				$is_started ? esc_attr( $value ) : '',
				$is_selected ? ' checked' : '',
				$disabled ? ' disabled' : '',
				wp_kses_post( '' !== (string) ( $option['title'] ?? '' ) ? $option['title'] : $value )
			);
		}

		$section = [
			'wrapper'     => '<div class="question-answers">',
			'options'     => sprintf(
				'<ul id="answer-options-%d" class="answer-options">%s</ul>',
				$question_id,
				$html_options
			),
			'response'    => $this->html_response( $questionPostModel, $args ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/question/html-answers-choice', $section, $questionPostModel, $args )
		);
	}

	/**
	 * HTML answers of type fill_in_blanks.
	 * Blank {{FIB_id}} in title replace to input when doing, to answered/fill when show result.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_answers_fib( QuestionPostModel $questionPostModel, array $args ): string {
		$answered = is_array( $args['answered'] ) ? $args['answered'] : [];
		$disabled = 'started' !== $args['status'];

		$html_options = '';
		foreach ( $this->get_answer_options( $questionPostModel, $args ) as $option ) {
			// Replace blanks after kses, input is not allowed by wp_kses_post.
			$title = wp_kses_post( (string) ( $option['title'] ?? '' ) );

			foreach ( (array) ( $option['ids'] ?? [] ) as $blank_id ) {
				$blank_id = (string) $blank_id;
				$blank    = $option['answers'][ $blank_id ] ?? null;

				if ( $args['show_result'] && is_array( $blank ) ) {
					$is_correct = ! empty( $blank['is_correct'] );
					// correct (fill) only has when show correct answers.
					$show_fill = $args['show_correct'] && isset( $blank['correct'] ) && '' !== (string) $blank['correct'];
					$user_fill = $blank['answer'] ?? ( $answered[ $blank_id ] ?? '' );

					$html_blank = sprintf(
						'<span class="lp-fib-answered %s">',
						isset( $blank['is_correct'] ) ? ( $is_correct ? 'correct' : 'fail' ) : ''
					);
					if ( ! $is_correct || ! $show_fill ) {
						$html_blank .= sprintf(
							'<span class="lp-fib-answered__answer">%s</span>',
							esc_html( is_scalar( $user_fill ) ? (string) $user_fill : '' )
						);
					}
					if ( ! $is_correct && $show_fill ) {
						$html_blank .= ' &rarr; ';
					}
					if ( $show_fill ) {
						$html_blank .= sprintf( '<span class="lp-fib-answered__fill">%s</span>', esc_html( (string) $blank['correct'] ) );
					}
					$html_blank .= '</span>';
				} else {
					$user_fill  = $answered[ $blank_id ] ?? '';
					$html_blank = sprintf(
						'<div class="lp-fib-input" style="display: inline-block; width: auto;"><input type="text" data-id="%s" value="%s"%s/></div>',
						esc_attr( $blank_id ),
						esc_attr( is_scalar( $user_fill ) ? (string) $user_fill : '' ),
						$disabled ? ' disabled' : ''
					);
				}

				$title = str_replace( '{{FIB_' . esc_attr( $blank_id ) . '}}', $html_blank, $title );
			}

			$html_options .= sprintf( '<div>%s</div>', $title );
		}

		$section = [
			'content'  => sprintf( '<div class="lp-fib-content">%s</div>', $html_options ),
			'response' => $this->html_response_fib( $questionPostModel, $args ),
		];

		return Template::combine_components(
			apply_filters( 'learn-press/question/html-answers-fib', $section, $questionPostModel, $args )
		);
	}

	/**
	 * HTML answers of type sorting_choice.
	 * Options show by order user sorted (answered), random order when not answered.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_answers_sorting( QuestionPostModel $questionPostModel, array $args ): string {
		$question_id = $questionPostModel->get_id();
		$is_started  = 'started' === $args['status'];
		$disabled    = $args['show_result'] || ! $is_started;
		$options     = $this->get_answer_options( $questionPostModel, $args );

		// Sort options by order of answered, options not in answered go to the end.
		$answered = array_map( 'strval', array_filter( (array) $args['answered'], 'is_scalar' ) );
		if ( ! empty( $answered ) ) {
			$options_by_value = [];
			foreach ( $options as $option ) {
				$options_by_value[ (string) ( $option['value'] ?? '' ) ] = $option;
			}

			$sorted = [];
			foreach ( $answered as $value ) {
				if ( isset( $options_by_value[ $value ] ) ) {
					$sorted[] = $options_by_value[ $value ];
					unset( $options_by_value[ $value ] );
				}
			}

			$options = array_merge( $sorted, array_values( $options_by_value ) );
		}

		$html_options = '';
		foreach ( $options as $position => $option ) {
			$value   = (string) ( $option['value'] ?? '' );
			$classes = [ 'answer-option' ];
			$extra   = '';

			if ( $args['show_result'] ) {
				if ( $args['show_correct'] && isset( $option['sorting'] ) ) {
					// Correct position of each option.
					$classes[] = (int) $option['sorting'] === $position ? 'answered-correct' : 'answered-wrong';
					$extra     = sprintf( '<span class="lp-sorting-choice__position">%d</span>', (int) $option['sorting'] + 1 );
				} else {
					$classes[] = ! empty( $args['correct'] ) ? 'answered-correct' : 'answered-wrong';
				}
			}

			$html_options .= sprintf(
				'<li class="%1$s"><span class="lp-sorting-choice__handle"></span><input type="hidden" class="option-sort" name="%2$s" value="%3$s"%4$s/><span class="option-title">%5$s</span>%6$s</li>',
				esc_attr( implode( ' ', $classes ) ),
				$is_started ? esc_attr( 'learn-press-question-' . $question_id . '[]' ) : '',
				$is_started ? esc_attr( $value ) : '',
				$disabled ? ' disabled' : '',
				wp_kses_post( '' !== (string) ( $option['title'] ?? '' ) ? $option['title'] : $value ),
				$extra
			);
		}

		$section = [
			'wrapper'     => '<div class="question-answers">',
			'options'     => sprintf(
				'<ul id="answer-options-%d" class="answer-options lp-sorting-choice%s"%s>%s</ul>',
				$question_id,
				// Not draggable (checked, review): Sortable toggles attribute draggable of li, so state is a class.
				$disabled ? ' lp-sorting-choice--readonly' : '',
				// JS only counts the question as answered after user sorted (options are shuffled by server).
				empty( $answered ) ? '' : ' data-sorted="1"',
				$html_options
			),
			'response'    => $this->html_response( $questionPostModel, $args ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components(
			apply_filters( 'learn-press/question/html-answers-sorting', $section, $questionPostModel, $args )
		);
	}

	/**
	 * HTML result label of question: Correct / Incorrect and point.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_response( QuestionPostModel $questionPostModel, array $args ): string {
		if ( empty( $args['show_result'] ) ) {
			return '';
		}

		$is_correct = ! empty( $args['correct'] );
		$point      = $this->get_point( $questionPostModel );

		$html = sprintf(
			'<div class="question-response %1$s"><span class="label">%2$s</span><span class="point">%3$s</span></div>',
			$is_correct ? 'correct' : 'incorrect',
			$is_correct ? esc_html__( 'Correct', 'learnpress' ) : esc_html__( 'Incorrect', 'learnpress' ),
			esc_html(
				sprintf(
					/* translators: 1: user point, 2: point of question */
					__( '%1$s/%2$s point', 'learnpress' ),
					$this->format_point( $is_correct ? $point : 0 ),
					$this->format_point( $point )
				)
			)
		);

		return apply_filters( 'learn-press/question/html-response', $html, $questionPostModel, $args );
	}

	/**
	 * HTML result label of question fill_in_blanks: points of user (can be part of point) and legend.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_response_fib( QuestionPostModel $questionPostModel, array $args ): string {
		if ( empty( $args['show_result'] ) ) {
			return '';
		}

		$html = sprintf(
			'<div class="question-response correct"><span class="label">%1$s</span><span class="point">%2$s</span><span class="lp-fib-note"><span style="background: #00adff"></span>%3$s</span><span class="lp-fib-note"><span style="background: #d85554"></span>%4$s</span></div>',
			esc_html__( 'Points', 'learnpress' ),
			esc_html(
				sprintf(
					/* translators: 1: user point, 2: point of question */
					__( '%1$s/%2$s point', 'learnpress' ),
					$this->format_point( (float) $args['mark'] ),
					$this->format_point( $this->get_point( $questionPostModel ) )
				)
			),
			esc_html__( 'Correct', 'learnpress' ),
			esc_html__( 'Incorrect', 'learnpress' )
		);

		return apply_filters( 'learn-press/question/html-response-fib', $html, $questionPostModel, $args );
	}

	/**
	 * Point of question, default 1 when not set mark.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return float
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_point( QuestionPostModel $questionPostModel ): float {
		$mark = (float) $questionPostModel->get_mark();

		return $mark > 0 ? $mark : 1;
	}

	/**
	 * Format point: integer without decimals, other 2 decimals.
	 *
	 * @param float $point
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function format_point( float $point ): string {
		if ( floor( $point ) === $point ) {
			return (string) (int) $point;
		}

		return number_format( $point, 2, '.', '' );
	}
}
