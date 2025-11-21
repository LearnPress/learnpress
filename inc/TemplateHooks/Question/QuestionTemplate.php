<?php
/**
 * Class QuestionTemplate
 *
 * @since 4.2.9.4
 * @version 2.0.0
 */

namespace LearnPress\TemplateHooks\Question;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionPostModel;
use Throwable;

/**
 * QuestionTemplate class.
 */
class QuestionTemplate {
	use Singleton;

	/**
	 * Initialize hooks.
	 *
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function init() {
		// Initialize question template hooks.
	}

	/**
	 * Render complete question HTML.
	 * Master method that orchestrates all component methods based on question type.
	 *
	 * @param int   $question_id Question ID.
	 * @param array $args        Arguments for prepare_render_data (instant_check, quiz_status, checked_questions, answered, show_correct_review, status).
	 * @param int   $question_index Question index (optional).
	 * @param bool  $show_hint   Whether to show hint.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 2.0.0
	 */
	public function render_question_html( int $question_id, array $args = [], $question_index = 0, $show_hint = false ) {
		try {
			// Prepare question data using QuestionPostModel::prepare_render_data.
			$questionData = QuestionPostModel::prepare_render_data( $question_id, $args );

			if ( empty( $questionData ) ) {
				return '';
			}

			$question      = $questionData['object'];
			$question_type = $questionData['type'];
			$status        = $args['status'] ?? 'started';

			// Build question wrapper classes.
			$wrapper_classes = array( 'question', 'question-' . $question_type );
			if ( $questionData['disabled'] ) {
				$wrapper_classes[] = 'question-answered';
			}

			// Render answer options based on question type.
			$answer_html = '';
			switch ( $question_type ) {
				case 'multi_choice':
					$answer_html = $this->multi_choice_question_html( $questionData );
					break;
				case 'single_choice':
					$answer_html = $this->single_choice_question_html( $questionData );
					break;
				case 'true_or_false':
					$answer_html = $this->true_or_false_question_html( $questionData );
					break;
				case 'fill_in_blanks':
					$answer_html = $this->fib_question_html( $questionData );
					break;
				default:
					// Allow custom question types via filter.
					$answer_html = apply_filters( 'learnpress/question/render-answers', '', $questionData );
					break;
			}

			// Build complete question HTML using Template::combine_components.
			$section = array(
				'wrapper'     => sprintf(
					'<div class="%s" data-id="%s">',
					esc_attr( implode( ' ', $wrapper_classes ) ),
					esc_attr( $question_id )
				),
				'title'       => $this->title_html( $question, $question_index ),
				'content'     => $this->content_html( $question ),
				'answers'     => $answer_html,
				'explanation' => $this->explanation_html( $question ),
				'hint'        => $this->hint_html( $question, $show_hint ),
				'buttons'     => $this->check_answer_button_html( $question, $status ),
				'wrapper_end' => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render multiple choice question HTML.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 2.0.0
	 */
	public function multi_choice_question_html( array $questionData ) {
		try {
			$question_id         = $questionData['id'];
			$options             = $questionData['options'] ?? [];
			$disabled            = $questionData['disabled'] ?? false;
			$answered            = $questionData['answered'] ?? [];
			$show_correct_review = $questionData['show_correct_review'] ?? false;
			$options_html        = '';
			$disabled_attr       = $disabled ? 'disabled' : '';

			foreach ( $options as $option ) {
				$option_uid     = is_array( $option ) ? ( $option['uid'] ?? uniqid() ) : ( $option->uid ?? uniqid() );
				$option_value   = is_array( $option ) ? ( $option['value'] ?? '' ) : ( $option->value ?? '' );
				$option_title   = is_array( $option ) ? ( $option['title'] ?? $option_value ) : ( $option->title ?? $option_value );
				$option_is_true = is_array( $option ) ? ( $option['is_true'] ?? '' ) : ( $option->is_true ?? '' );
				$input_id       = 'learn-press-answer-option-' . $option_uid;

				// Check if this option was answered incorrectly
				$li_class = 'answer-option';
				if ( $show_correct_review && is_array( $answered ) && in_array( $option_value, $answered, true ) ) {
					if ( ! $option_is_true || $option_is_true === 'no' || $option_is_true === '' ) {
						$is_answered_wrong = true;
						$li_class .= ' answered-wrong';
					} else {
						$li_class .= ' answered-correct';
					}
				}

				$options_html .= sprintf(
					'<li class="%6$s">
						<input type="checkbox" class="option-check" name="learn-press-question-%1$s" id="%2$s" value="%3$s" %5$s />
						<label for="%2$s" class="option-title">%4$s</label>
					</li>',
					esc_attr( $question_id ),
					esc_attr( $input_id ),
					esc_attr( $option_value ),
					wp_kses_post( $option_title ),
					$disabled_attr,
					esc_attr( $li_class )
				);
			}

			$section = array(
				'wrapper'         => '<div class="question-answers">',
				'options_wrapper' => sprintf( '<ul id="answer-options-%s" class="answer-options">', esc_attr( $question_id ) ),
				'options'         => $options_html,
				'options_end'     => '</ul>',
				'wrapper_end'     => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render single choice question HTML.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 2.0.0
	 */
	public function single_choice_question_html( array $questionData ) {
		try {
			$question_id         = $questionData['id'];
			$options             = $questionData['options'] ?? [];
			$disabled            = $questionData['disabled'] ?? false;
			$answered            = $questionData['answered'] ?? '';
			$show_correct_review = $questionData['show_correct_review'] ?? false;
			$options_html        = '';
			$disabled_attr       = $disabled ? 'disabled' : '';

			foreach ( $options as $option ) {
				$option_uid     = is_array( $option ) ? ( $option['uid'] ?? uniqid() ) : ( $option->uid ?? uniqid() );
				$option_value   = is_array( $option ) ? ( $option['value'] ?? '' ) : ( $option->value ?? '' );
				$option_title   = is_array( $option ) ? ( $option['title'] ?? $option_value ) : ( $option->title ?? $option_value );
				$option_is_true = is_array( $option ) ? ( $option['is_true'] ?? '' ) : ( $option->is_true ?? '' );
				$input_id       = 'learn-press-answer-option-' . $option_uid;

				// Check if this option was answered incorrectly
				$li_class = 'answer-option';
				if ( $show_correct_review && $answered === $option_value ) {
					if ( ! $option_is_true || $option_is_true === 'no' || $option_is_true === '' ) {
						$li_class .= ' answered-wrong';
					} else {
						$li_class .= ' answered-correct';
					}
				}

				$options_html .= sprintf(
					'<li class="%6$s">
						<input type="radio" class="option-check" name="learn-press-question-%1$s" id="%2$s" value="%3$s" %5$s />
						<label for="%2$s" class="option-title">%4$s</label>
					</li>',
					esc_attr( $question_id ),
					esc_attr( $input_id ),
					esc_attr( $option_value ),
					wp_kses_post( $option_title ),
					$disabled_attr,
					esc_attr( $li_class )
				);
			}

			$section = array(
				'wrapper'         => '<div class="question-answers">',
				'options_wrapper' => sprintf( '<ul id="answer-options-%s" class="answer-options">', esc_attr( $question_id ) ),
				'options'         => $options_html,
				'options_end'     => '</ul>',
				'wrapper_end'     => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render true or false question HTML.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 2.0.0
	 */
	public function true_or_false_question_html( array $questionData ) {
		try {
			$question_id         = $questionData['id'];
			$options             = $questionData['options'] ?? [];
			$disabled            = $questionData['disabled'] ?? false;
			$answered            = $questionData['answered'] ?? '';
			$show_correct_review = $questionData['show_correct_review'] ?? false;
			$options_html        = '';
			$disabled_attr       = $disabled ? 'disabled' : '';

			foreach ( $options as $option ) {
				$option_uid     = is_array( $option ) ? ( $option['uid'] ?? uniqid() ) : ( $option->uid ?? uniqid() );
				$option_value   = is_array( $option ) ? ( $option['value'] ?? '' ) : ( $option->value ?? '' );
				$option_title   = is_array( $option ) ? ( $option['title'] ?? $option_value ) : ( $option->title ?? $option_value );
				$option_is_true = is_array( $option ) ? ( $option['is_true'] ?? '' ) : ( $option->is_true ?? '' );
				$input_id       = 'learn-press-answer-option-' . $option_uid;

				// Check if this option was answered incorrectly
				$li_class = 'answer-option';
				if ( $show_correct_review && $answered === $option_value ) {
					if ( ! $option_is_true || $option_is_true === 'no' || $option_is_true === '' ) {
						$li_class .= ' answered-wrong';
					} else {
						$li_class .= ' answered-correct';
					}
				}

				if ( $option_is_true ) {
					$li_class .= ' answered-correct';
				}

				$options_html .= sprintf(
					'<li class="%6$s">
						<input type="radio" class="option-check" name="learn-press-question-%1$s" id="%2$s" value="%3$s" %5$s />
						<label for="%2$s" class="option-title">%4$s</label>
					</li>',
					esc_attr( $question_id ),
					esc_attr( $input_id ),
					esc_attr( $option_value ),
					wp_kses_post( $option_title ),
					$disabled_attr,
					esc_attr( $li_class )
				);
			}

			$section = array(
				'wrapper'         => '<div class="question-answers">',
				'options_wrapper' => sprintf( '<ul id="answer-options-%s" class="answer-options">', esc_attr( $question_id ) ),
				'options'         => $options_html,
				'options_end'     => '</ul>',
				'wrapper_end'     => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render fill in the blanks question HTML.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 2.0.0
	 */
	public function fib_question_html( array $questionData ) {
		try {
			$question_id  = $questionData['id'];
			$options      = $questionData['options'] ?? [];
			$disabled     = $questionData['disabled'] ?? false;
			$content_html = '';

			foreach ( $options as $option ) {
				$option_uid     = is_array( $option ) ? ( $option['uid'] ?? uniqid() ) : ( $option->uid ?? uniqid() );
				$option_title   = is_array( $option ) ? ( $option['title'] ?? '' ) : ( $option->title ?? '' );
				$option_ids     = is_array( $option ) ? ( $option['ids'] ?? [] ) : ( $option->ids ?? [] );
				$option_answers = is_array( $option ) ? ( $option['answers'] ?? [] ) : ( $option->answers ?? [] );

				// Process fill-in-blank placeholders.
				$processed_title = $option_title;
				foreach ( $option_ids as $blank_id ) {
					$placeholder = '{{FIB_' . $blank_id . '}}';

					// Check if question is checked/completed
					if ( $disabled && isset( $option_answers[ $blank_id ] ) ) {
						$answer_data = $option_answers[ $blank_id ];
						$is_correct  = $answer_data['is_correct'] ?? false;
						$user_answer = $answer_data['answer'] ?? '';

						if ( $is_correct ) {
							// Show correct answer
							$input_html = sprintf(
								'<span class="lp-fib-answered correct"><span class="lp-fib-answered__fill">%s</span></span>',
								esc_html( $user_answer )
							);
						} else {
							// Show incorrect answer with user's answer and correct answer
							$correct_answer = $answer_data['correct'] ?? '';
							$input_html     = sprintf(
								'<span class="lp-fib-answered fail"><span class="lp-fib-answered__answer">%s</span> → <span class="lp-fib-answered__fill">%s</span></span>',
								esc_html( $user_answer ),
								esc_html( $correct_answer )
							);
						}
					} else {
						// Show input field
						$disabled_attr = $disabled ? 'disabled' : '';
						$input_html    = sprintf(
							'<div class="lp-fib-input" style="display: inline-block; width: auto;">
								<input type="text" data-id="%s" value="" %s />
							</div>',
							esc_attr( $blank_id ),
							$disabled_attr
						);
					}

					$processed_title = str_replace( $placeholder, $input_html, $processed_title );
				}

				$content_html .= sprintf(
					'<div>%s</div>',
					$processed_title
				);
			}

			$section = array(
				'wrapper'     => '<div class="question-answers">',
				'fib_wrapper' => '<div class="lp-fib-content">',
				'fib_content' => $content_html,
				'fib_end'     => '</div>',
				'wrapper_end' => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render question title HTML.
	 *
	 * @param QuestionPostModel $question       Question post model.
	 * @param int               $question_index Question index number (optional).
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 2.0.0
	 */
	public function title_html( QuestionPostModel $question, $question_index = 0 ) {
		try {
			$question_id    = $question->get_id();
			$question_title = $question->get_the_title();
			$index_html     = '';
			$hint_button    = '';
			$edit_link      = '';

			// Question index
			if ( $question_index > 0 ) {
				$index_html = sprintf(
					'<span class="question-index">%s.</span>',
					esc_html( $question_index )
				);
			}

			// Hint button
			$hint_button = sprintf(
				'<button type="button" class="btn-show-hint" data-question-id="%s"><span>%s</span></button>',
				esc_attr( $question_id ),
				esc_html__( 'Hint', 'learnpress' )
			);

			// Edit link (only if user can edit)
			if ( current_user_can( 'edit_post', $question_id ) ) {
				$edit_url  = get_edit_post_link( $question_id );
				$edit_link = sprintf(
					'<span class="edit-link"><a href="%s">%s</a></span>',
					esc_url( $edit_url ),
					esc_html__( 'Edit', 'learnpress' )
				);
			}

			$section = array(
				'wrapper'     => '<h4 class="question-title">',
				'index'       => $index_html,
				'title'       => sprintf( '<span>%s</span>', wp_kses_post( $question_title ) ),
				'hint_button' => $hint_button,
				'edit_link'   => $edit_link,
				'wrapper_end' => '</h4>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render question content HTML.
	 *
	 * @param QuestionPostModel $question Question post model.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function content_html( QuestionPostModel $question ) {
		try {
			$question_content = $question->get_the_content();

			if ( empty( $question_content ) ) {
				return '';
			}

			$section = array(
				'wrapper'     => '<div class="question-content">',
				'content'     => wp_kses_post( $question_content ),
				'wrapper_end' => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render question explanation HTML.
	 *
	 * @param QuestionPostModel $question Question post model.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function explanation_html( QuestionPostModel $question ) {
		try {
			$explanation = $question->get_explanation();

			if ( empty( $explanation ) ) {
				return '';
			}

			$section = array(
				'wrapper'     => '<div class="question-explanation-content">',
				'title'       => sprintf(
					'<strong class="explanation-title">%s:</strong>',
					esc_html__( 'Explanation', 'learnpress' )
				),
				'content'     => sprintf( '<div>%s</div>', wp_kses_post( $explanation ) ),
				'wrapper_end' => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render question hint HTML.
	 *
	 * @param QuestionPostModel $question  Question post model.
	 * @param bool              $show_hint Whether to show the hint.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function hint_html( QuestionPostModel $question, $show_hint = false ) {
		try {
			$hint        = $question->get_hint();
			$explanation = $question->get_explanation();

			// Only show hint if there's no explanation and hint is available.
			if ( empty( $hint ) || ! empty( $explanation ) || ! $show_hint ) {
				return '';
			}

			$section = array(
				'wrapper'     => '<div class="question-hint-content">',
				'title'       => sprintf(
					'<strong class="hint-title">%s:</strong>',
					esc_html__( 'Hint', 'learnpress' )
				),
				'content'     => sprintf( '<div>%s</div>', wp_kses_post( $hint ) ),
				'wrapper_end' => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render question buttons HTML.
	 *
	 * @param QuestionPostModel $question Question post model.
	 * @param string            $status   Quiz status (started, completed, etc.).
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function check_answer_button_html( QuestionPostModel $question, $status = 'started' ) {
		try {
			// Only show buttons when quiz is started.
			if ( 'started' !== $status ) {
				return '';
			}

			$question_id = $question->get_id();


			$section = array(
				'wrapper'     => sprintf( '<button class="lp-button instant-check" data-question-id="%s" >', $question_id ),
				'check_icon'  => '<span class="instant-check__icon"></span>',
				'check_text'  => __( 'Check answers', 'learnpress' ),
				'check_info'  => sprintf( '<div class="instant-check__info">%s</div>', __( 'You need to answer the question before checking the answer key.', 'learnpress' ) ),
				'wrapper_end' => '</button>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render question response HTML (correct/incorrect label with points).
	 * Based on JavaScript getCorrectLabel() function.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function question_response_html( array $questionData ) {
		try {
			// Only show response if show_correct_review is enabled
			$show_correct_review = $questionData['show_correct_review'] ?? false;
			if ( ! $show_correct_review ) {
				return '';
			}
			if ( $questionData['question_type'] === 'fill_in_blanks' ) {
				return $this->fib_correct_label( $questionData );
			}

			$question_id = $questionData['id'] ?? 0;
			$answered    = $questionData['answered'] ?? null;
			$point       = $questionData['point'] ?? 0;
			$is_correct  = $this->is_correct( $questionData );

			// Don't show if not answered
			if ( empty( $answered ) && $answered !== '0' && $answered !== 0 ) {
				return '';
			}

			// Determine correct/incorrect class and label
			$response_class = $is_correct ? 'correct' : 'incorrect';
			$label_text     = $is_correct ? __( 'Correct', 'learnpress' ) : __( 'Incorrect', 'learnpress' );
			$earned_point   = $is_correct ? $point : 0;

			$section = array(
				'wrapper'     => sprintf( '<div class="question-response %s">', esc_attr( $response_class ) ),
				'label'       => sprintf( '<span class="label">%s</span>', esc_html( $label_text ) ),
				'point'       => sprintf(
					'<span class="point">%s</span>',
					sprintf(
						esc_html__( '%d/%d point', 'learnpress' ),
						$earned_point,
						$point
					)
				),
				'wrapper_end' => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Render fill-in-blanks question correct label HTML (points with color legend).
	 * Based on JavaScript getCorrectLabel() function in fill-in-blanks component.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function fib_correct_label( array $questionData ) {
		try {
			// Only show response if show_correct_review is enabled
			$show_correct_review = $questionData['show_correct_review'] ?? false;
			if ( ! $show_correct_review ) {
				return '';
			}

			$point      = $questionData['point'] ?? 0;
			$answered   = $questionData['answered'] ?? null;

			// Don't show if not answered
			if ( empty( $answered ) ) {
				return '';
			}
			$is_correct = $this->is_correct( $questionData );

			$mark = $is_correct ? $point : 0;

			$section = array(
				'wrapper'         => sprintf( '<div class="question-response %s">', $is_correct ? 'correct' : 'incorrect' ),
				'label'           => sprintf( '<span class="label">%s</span>', esc_html__( 'Points', 'learnpress' ) ),
				'point'           => sprintf(
					'<span class="point">%s/%s %s</span>',
					esc_html( $mark ),
					esc_html( $point ),
					esc_html__( 'point', 'learnpress' )
				),
				'correct_note'    => sprintf(
					'<span class="lp-fib-note"><span style="background: #00adff;"></span>%s</span>',
					esc_html__( 'Correct', 'learnpress' )
				),
				'incorrect_note'  => sprintf(
					'<span class="lp-fib-note"><span style="background: #d85554;"></span>%s</span>',
					esc_html__( 'Incorrect', 'learnpress' )
				),
				'wrapper_end'     => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Check if radio-based question (single choice, true/false) is answered correctly.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return bool
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function check_radio_question( array $questionData ) {
		$answered = $questionData['answered'] ?? null;
		$options  = $questionData['options'] ?? [];

		// No answer provided
		if ( empty( $answered ) && $answered !== '0' && $answered !== 0 ) {
			return false;
		}

		// Check if answered value matches any option where is_true === 'yes'
		foreach ( $options as $option ) {
			$option_is_true = is_array( $option ) ? ( $option['is_true'] ?? '' ) : ( $option->is_true ?? '' );
			$option_value   = is_array( $option ) ? ( $option['value'] ?? '' ) : ( $option->value ?? '' );

			if ( $option_is_true === 'yes' ) {
				if ( $answered == $option_value ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if multiple choice question is answered correctly.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return bool
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function check_multi_choice_question( array $questionData ) {
		$answered = $questionData['answered'] ?? null;
		$options  = $questionData['options'] ?? [];

		// No answer provided or answered is not an array
		if ( is_bool( $answered ) || empty( $answered ) ) {
			return false;
		}

		// Ensure answered is an array
		if ( ! is_array( $answered ) ) {
			return false;
		}

		// Check all options
		foreach ( $options as $option ) {
			$option_is_true = is_array( $option ) ? ( $option['is_true'] ?? '' ) : ( $option->is_true ?? '' );
			$option_value   = is_array( $option ) ? ( $option['value'] ?? '' ) : ( $option->value ?? '' );

			if ( $option_is_true === 'yes' ) {
				// Correct option must be in answered array
				if ( ! in_array( $option_value, $answered, true ) ) {
					return false;
				}
			} else {
				// Incorrect option must NOT be in answered array
				if ( in_array( $option_value, $answered, true ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Check if FIB question is answered correctly.
	 *
	 * @param array $questionData Question data from QuestionPostModel::prepare_render_data().
	 *
	 * @return bool
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function check_fib_question( $questionData ) {
		if ( empty( $questionData['answered'] ) || empty( $questionData['options'] ) ) {
			return false;
		}
		if ( empty( $questionData['options']['answers'] ) ) {
			return false;
		}
		$answers         = $questionData['options']['answers'];
		$corrected_count = 0;
		$blank_count     = count( $questionData['options']['ids'] ?? [] );
		foreach ( $answered as $blank => $answer ) {
			if ( $answer['is_correct'] ) {
				$corrected_count++;
			}
		}
		return $blank_count === $corrected_count;
	}


	public function is_correct( $questionData ) {
		$is_correct = false;
		switch ( $questionData['question_type'] ) {
			case 'multi_choice':
				$is_correct = $this->check_multi_choice_question( $questionData );
				break;
			case 'single_choice':
			case 'true_or_false':
				$is_correct = $this->check_radio_question( $questionData );
				break;
			case 'fill_in_blanks':
				$is_correct = $this->check_fib_question( $questionData );
				break;
			default:
				break;
		}
		return $is_correct;
	}
}
