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

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserQuizModel;
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
	 * HTML title of question
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_title( QuestionPostModel $questionPostModel, array $args = [] ): string {
		return apply_filters(
			'learn-press/question/html-title',
			sprintf(
				"<span class='question-title'>%s</span>",
				wp_kses_post( $questionPostModel->get_the_title() )
			),
			$questionPostModel,
			$args
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
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_explanation( QuestionPostModel $questionPostModel, array $args = [] ): string {
		$explanation = (string) $questionPostModel->get_explanation();
		if ( empty( $explanation ) ) {
			return '';
		}

		$explanation = do_shortcode( $explanation );

		return sprintf(
			'<div class="question-explanation">
					<strong class="explanation-title">%s:</strong>
					<div>%s</div>
				</div>',
			esc_html__( 'Explanation', 'learnpress' ),
			Template::sanitize_html_content( $explanation )
		);
	}

	/**
	 * HTML hint of question, hidden until user click button hint.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function html_hint( QuestionPostModel $questionPostModel, array $args = [] ): string {
		$hint = $questionPostModel->get_hint();
		if ( empty( $hint ) ) {
			return '';
		}

		$hint = do_shortcode( $hint );

		return sprintf(
			'<div class="question-hint">
				<strong class="hint-title">%s:</strong>
				<div>%s</div>
			</div>',
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
		// Fill default args, each type reads show_result, show_correct, correct without checking the key.
		$args          = $this->parse_answer_args( $args );
		$quizPostModel = $args['quizPostModel'] ?? false;
		$html          = '';
		$type          = (string) $questionPostModel->get_type();

		if ( ! $quizPostModel instanceof QuizPostModel ) {
			return $html;
		}

		$quiz_setting         = [
			'enable_review' => $quizPostModel->has_review(),
			'enable_show_correct_answer' => $quizPostModel->has_show_correct_review(),
		];
		$args['quiz_setting'] = $quiz_setting;

		try {
			switch ( $type ) {
				case 'true_or_false':
					$html = $this->html_answer_type_true_or_false( $questionPostModel, $args );
					break;
				case 'single_choice':
					$html = $this->html_answer_type_single_choice( $questionPostModel, $args );
					break;
				case 'multi_choice':
					$html = $this->html_answer_type_multi_choice( $questionPostModel, $args );
					break;
				case 'fill_in_blanks':
					$html = $this->html_answer_type_fill_in_blanks( $questionPostModel, $args );
					break;
				case 'sorting_choice':
					$html = $this->html_answer_type_sorting_choice( $questionPostModel, $args );
					break;
				default:
					$html = apply_filters( 'learn-press/question/html-answers', $html, $questionPostModel, $args );
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
					'<span>' . esc_html( $type ) . '</span>'
				)
			);
		}

		return $html;
	}

	/**
	 * Answers of question for display, read from get_answers_for_legacy_type().
	 * An answer is QuestionAnswerModel (old tables) or array (new table question_answer_config),
	 * both are normalized to one shape: uid, title, value, is_true, answer (the source answer).
	 * is_true is empty when not show correct answers, avoid leaking correct answers.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.1
	 */
	public function get_answers( QuestionPostModel $questionPostModel, array $args ): array {
		$answers = [];

		try {
			$answers = $questionPostModel->get_answers_for_legacy_type();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		$options = [];
		foreach ( array_values( $answers ) as $index => $answer ) {
			if ( $answer instanceof QuestionAnswerModel ) {
				$uid     = $answer->question_answer_id;
				$title   = $answer->title;
				$value   = $answer->value;
				$order   = $answer->order;
				$is_true = $answer->is_true;
			} else {
				$answer_arr = (array) $answer;
				$uid        = $answer_arr['question_answer_id'] ?? '';
				$title      = $answer_arr['title'] ?? '';
				$value      = $answer_arr['value'] ?? '';
				$order      = $answer_arr['order'] ?? $index + 1;
				$is_true    = $answer_arr['is_true'] ?? '';
			}

			$options[] = [
				// Sort keys, removed before return.
				'index'   => $index,
				'order'   => (int) $order,
				'uid'     => (string) ( $uid ?? '' ),
				'title'   => (string) $title,
				'value'   => (string) $value,
				// is_true leaks the correct answer, only read it when showing correct answers.
				'is_true' => $args['show_correct'] ? (string) $is_true : '',
				// Source answer, type-specific code reads what it needs (FIB blanks).
				'answer'  => $answer,
			];
		}

		// Sort by order ASC as old tables do, keep position when same order.
		usort(
			$options,
			function ( $a, $b ) {
				return [ $a['order'], $a['index'] ] <=> [ $b['order'], $b['index'] ];
			}
		);

		foreach ( $options as $k => $option ) {
			unset( $options[ $k ]['index'], $options[ $k ]['order'] );
		}

		return apply_filters( 'learn-press/question/answers-for-display', array_values( $options ), $questionPostModel, $args );
	}

	/**
	 * Random order of options, different from original order when has more than one option.
	 * Same as QuestionSortingChoiceModel::shuffle_options(), that one is protected.
	 *
	 * @param array $options
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function shuffle_answers( array $options ): array {
		$count = count( $options );
		if ( $count < 2 ) {
			return $options;
		}

		$new_options = $options;
		for ( $i = 0; $i < 10 && $new_options === $options; $i++ ) {
			shuffle( $new_options );
		}

		// Options same value (rare), shuffle can't change order, move first option to last.
		if ( $new_options === $options ) {
			$new_options[] = array_shift( $new_options );
		}

		return $new_options;
	}

	/**
	 * HTML answers of type true_or_false, one answer only (radio).
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function html_answer_type_true_or_false( QuestionPostModel $questionPostModel, array $args ): string {
		$userQuizModel           = $args['userQuizModel'] ?? false;
		$question_id             = $questionPostModel->get_id();
		$is_started              = ! $userQuizModel || $userQuizModel instanceof UserQuizModel && $userQuizModel->has_started();
		$result_questions        = $args['result_questions'] ?? [];
		$question_answer_checked = $result_questions[ $questionPostModel->get_id() ] ?? false;
		$answered                = is_scalar( $question_answer_checked['answered'] ?? null )
									? (string) $question_answer_checked['answered']
									: '';
		$disabled                = ( $args['show_result'] ?? false ) || ! $is_started;

		$html_options   = '';
		$answers_config = $questionPostModel->get_answers_for_legacy_type();
		foreach ( $answers_config as $index => $option ) {
			if ( $option instanceof QuestionAnswerModel ) {
				$option_arr = (array) $option;
				$option     = $option_arr;
			}

			$title = (string) ( $option['title'] ?? '' );
			$value = (string) ( $option['value'] ?? '' );
			$uid   = $question_id . '-' . $index;

			$is_selected = '' !== $answered && $value === $answered;
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
				'<li class="%1$s">
					<input type="radio" class="option-check" name="%2$s" id="%3$s" value="%4$s"%5$s%6$s/>
					<label for="%3$s" class="option-title">%7$s</label>
				</li>',
				esc_attr( implode( ' ', $classes ) ),
				$is_started ? esc_attr( 'learn-press-question-' . $question_id ) : '',
				esc_attr( $input_id ),
				$is_started ? esc_attr( $value ) : '',
				$is_selected ? ' checked' : '',
				$disabled ? ' disabled' : '',
				Template::sanitize_html_content( do_shortcode( $title ) )
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
			apply_filters( 'learn-press/question/html-answers-true-or-false', $section, $questionPostModel, $args )
		);
	}

	/**
	 * HTML answers of type single_choice, one answer only (radio).
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function html_answer_type_single_choice( QuestionPostModel $questionPostModel, array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;
		$question_id   = $questionPostModel->get_id();
		$is_started    = ! $userQuizModel || $userQuizModel instanceof UserQuizModel && $userQuizModel->has_started();
		$disabled      = $args['show_result'] || ! $is_started;

		$result_questions        = $args['result_questions'] ?? [];
		$question_answer_checked = $result_questions[ $questionPostModel->get_id() ] ?? false;
		$answered                = is_scalar( $question_answer_checked['answered'] ?? null ) ? (string) $question_answer_checked['answered'] : '';

		$html_options   = '';
		$answers_config = $questionPostModel->get_answers_for_legacy_type();
		foreach ( $answers_config as $index => $option ) {
			if ( $option instanceof QuestionAnswerModel ) {
				$option_arr = (array) $option;
				$option     = $option_arr;
			}

			$title = (string) ( $option['title'] ?? '' );
			$value = (string) ( $option['value'] ?? '' );
			$uid   = $question_id . '-' . $index;

			$is_selected = '' !== $answered && $value === $answered;
			// is_true leaks the correct answer, only read it when showing correct answers.
			$is_true = $args['show_correct'] && 'yes' === ( $option['is_true'] ?? '' );

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
				'<li class="%1$s"><input type="radio" class="option-check" name="%2$s" id="%3$s" value="%4$s"%5$s%6$s/><label for="%3$s" class="option-title">%7$s</label></li>',
				esc_attr( implode( ' ', $classes ) ),
				$is_started ? esc_attr( 'learn-press-question-' . $question_id ) : '',
				esc_attr( $input_id ),
				$is_started ? esc_attr( $value ) : '',
				$is_selected ? ' checked' : '',
				$disabled ? ' disabled' : '',
				wp_kses_post( '' !== $title ? do_shortcode( $title ) : $value )
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
			apply_filters( 'learn-press/question/html-answers-single-choice', $section, $questionPostModel, $args )
		);
	}

	/**
	 * HTML answers of type multi_choice, many answers (checkbox).
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function html_answer_type_multi_choice( QuestionPostModel $questionPostModel, array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;
		$question_id   = $questionPostModel->get_id();
		$is_started    = ! $userQuizModel || $userQuizModel instanceof UserQuizModel && $userQuizModel->has_started();
		$disabled      = $args['show_result'] || ! $is_started;

		$result_questions        = $args['result_questions'] ?? [];
		$question_answer_checked = $result_questions[ $questionPostModel->get_id() ] ?? false;
		$answered                = array_map( 'strval', (array) ( $question_answer_checked['answered'] ?? [] ) );

		$html_options   = '';
		$answers_config = $questionPostModel->get_answers_for_legacy_type();
		foreach ( $answers_config as $index => $option ) {
			if ( $option instanceof QuestionAnswerModel ) {
				$option_arr = (array) $option;
				$option     = $option_arr;
			}

			$title = (string) ( $option['title'] ?? '' );
			$value = (string) ( $option['value'] ?? '' );
			$uid   = $question_id . '-' . $index;

			$is_selected = in_array( $value, $answered, true );
			// is_true of each option leaks which answers are correct, only read it when showing correct answers.
			$is_true = $args['show_correct'] && 'yes' === ( $option['is_true'] ?? '' );

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
				'<li class="%1$s"><input type="checkbox" class="option-check" name="%2$s" id="%3$s" value="%4$s"%5$s%6$s/><label for="%3$s" class="option-title">%7$s</label></li>',
				esc_attr( implode( ' ', $classes ) ),
				$is_started ? esc_attr( 'learn-press-question-' . $question_id ) : '',
				esc_attr( $input_id ),
				$is_started ? esc_attr( $value ) : '',
				$is_selected ? ' checked' : '',
				$disabled ? ' disabled' : '',
				wp_kses_post( '' !== $title ? do_shortcode( $title ) : $value )
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
			apply_filters( 'learn-press/question/html-answers-multi-choice', $section, $questionPostModel, $args )
		);
	}

	/**
	 * Blanks of one fill_in_blanks answer: title with blanks replaced, blank ids, result of each blank.
	 * Was QuestionPostFIBModel::get_answer_options(), the helpers it uses are public on the model.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $option one answer of get_answers_for_legacy_type(), model cast to array
	 * @param mixed $answered fills of user, [ blank_id => fill ].
	 * @param bool $show_answer show the correct fill of each blank.
	 * @param bool $show_result show the result of each blank.
	 *
	 * @return array [ title, ids, answers ]
	 * @since 4.5.0
	 * @version 1.0.1
	 */
	public function get_answer_blanks( QuestionPostModel $questionPostModel, array $option, $answered, bool $show_answer, bool $show_result ): array {
		$title  = (string) ( $option['title'] ?? '' );
		$blanks = [
			'title'   => $title,
			'ids'     => [],
			'answers' => [],
		];

		if ( ! $questionPostModel instanceof QuestionPostFIBModel ) {
			return $blanks;
		}

		try {
			// Answer read from config has value instead of id, then it has no numeric id.
			$uid       = (string) ( $option['question_answer_id'] ?? $option['uid'] ?? '' );
			$answer_id = is_numeric( $uid ) ? (int) $uid : 0;
			$fills     = $questionPostModel->get_blanks_of_answer( $option['answer'] ?? $option );

			$title_content = apply_filters(
				'learn-press/question/fib/regex-content',
				$title,
				$answer_id,
				$show_answer,
				$answered
			);
			// Always replace shortcode fib remain (hook removed or changed), avoid show fill.
			$title_content = $questionPostModel->match_shortcode_api( (string) $title_content, $answer_id, $show_answer, $answered );

			$blanks['title']   = $title_content;
			$blanks['ids']     = $questionPostModel->fib_get_ids( $title );
			$blanks['answers'] = $show_result
				? $questionPostModel->get_answer_data( $title, $answer_id, $answered, $fills )
				: [];

			if ( ! $show_answer ) {
				foreach ( $blanks['answers'] as $blank_id => $blank_data ) {
					unset( $blanks['answers'][ $blank_id ]['correct'] );
				}
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $blanks;
	}

	/**
	 * HTML answers of type fill_in_blanks.
	 * Blank {{FIB_id}} in title replace to input when doing, to answered/fill when show result.
	 *
	 * @param QuestionPostModel $questionPostModel
	 * @param array $args
	 *
	 * @return string
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function html_answer_type_fill_in_blanks( QuestionPostModel $questionPostModel, array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;
		$is_started    = ! $userQuizModel || $userQuizModel instanceof UserQuizModel && $userQuizModel->has_started();
		$disabled      = $args['show_result'] || ! $is_started;

		$result_questions        = $args['result_questions'] ?? [];
		$question_answer_checked = $result_questions[ $questionPostModel->get_id() ] ?? false;
		$answered                = is_array( $question_answer_checked['answered'] ?? null ) ? $question_answer_checked['answered'] : [];
		// Fill of each blank only when showing correct answers, same as question answer options.
		$show_answer = (bool) $args['show_correct'];
		$show_result = $show_answer || ! empty( $args['show_result'] );

		$html_options   = '';
		$answers_config = $questionPostModel->get_answers_for_legacy_type();
		foreach ( $answers_config as $option ) {
			if ( $option instanceof QuestionAnswerModel ) {
				$option_arr = (array) $option;
				$option     = $option_arr;
			}

			$blanks_of_option = $this->get_answer_blanks( $questionPostModel, $option, $answered, $show_answer, $show_result );

			// Replace blanks after kses, input is not allowed by wp_kses_post.
			$title = wp_kses_post( (string) ( $blanks_of_option['title'] ?? '' ) );

			foreach ( $blanks_of_option['ids'] as $blank_id ) {
				$blank_id = (string) $blank_id;
				$blank    = $blanks_of_option['answers'][ $blank_id ] ?? null;

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
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function html_answer_type_sorting_choice( QuestionPostModel $questionPostModel, array $args ): string {
		$userQuizModel = $args['userQuizModel'] ?? false;
		$question_id   = $questionPostModel->get_id();
		$is_started    = ! $userQuizModel || $userQuizModel instanceof UserQuizModel && $userQuizModel->has_started();
		$disabled      = $args['show_result'] || ! $is_started;

		$result_questions        = $args['result_questions'] ?? [];
		$question_answer_checked = $result_questions[ $questionPostModel->get_id() ] ?? false;
		$answered                = array_map( 'strval', array_filter( (array) ( $question_answer_checked['answered'] ?? [] ), 'is_scalar' ) );

		// Was QuestionSortingChoiceModel::get_answer_options(): correct position, then random order.
		$options        = [];
		$answers_config = $questionPostModel->get_answers_for_legacy_type();
		foreach ( array_values( $answers_config ) as $position => $option ) {
			if ( $option instanceof QuestionAnswerModel ) {
				$option_arr = (array) $option;
				$option     = $option_arr;
			}

			// Student can know correct order by answer id (sort low to high), so only title and value are kept.
			$option_sort = [
				'title' => (string) ( $option['title'] ?? '' ),
				'value' => (string) ( $option['value'] ?? '' ),
			];

			if ( $args['show_correct'] ) {
				// Correct position of the option, JS use it to show correct/incorrect.
				$option_sort['sorting'] = $position;
			}

			$options[] = $option_sort;
		}

		$options = $this->shuffle_answers( $options );

		// Sort options by order of answered, options not in answered go to the end.
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
				wp_kses_post( '' !== (string) ( $option['title'] ?? '' ) ? do_shortcode( $option['title'] ) : $value ),
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
			'<div class="question-response %1$s">
					<span class="label">%2$s</span>
					<span class="point">%3$s</span>
				</div>',
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
			'<div class="question-response correct">
				<span class="label">%1$s</span>
				<span class="point">%2$s</span>
				<span class="lp-fib-note">
					<span style="background: #00adff"></span>%3$s
				</span>
				<span class="lp-fib-note">
					<span style="background: #d85554"></span>%4$s
				</span>
			</div>',
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
		$mark = $questionPostModel->get_mark();

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
