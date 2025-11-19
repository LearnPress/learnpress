<?php
/**
 * Class QuestionTemplate
 *
 * @since 4.2.9.4
 * @version 1.0.0
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
	 * @param QuestionPostModel $question       Question post model.
	 * @param int               $question_index Question index (optional).
	 * @param string            $status         Quiz status (started, completed, etc.).
	 * @param mixed             $answered       User's answered value(s).
	 * @param bool              $show_hint      Whether to show hint.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function render_question_html( QuestionPostModel $question, $question_index = 0, $status = 'started', $answered = null, $show_hint = false ) {
		try {
			$question_id   = $question->get_id();
			$question_type = $question->get_type();

			// Get answer options from question.
			$answer_models = $question->get_answer_option();
			$options       = $this->convert_answers_to_options( $answer_models, $question_type );

			// Build question wrapper classes.
			$wrapper_classes = array( 'question', 'question-' . $question_type );

			// Render answer options based on question type.
			$answer_html = '';
			switch ( $question_type ) {
				case 'multi_choice':
					$answer_html = $this->multi_choice_question_html( $question, $options, $answered );
					break;
				case 'single_choice':
					$answer_html = $this->single_choice_question_html( $question, $options, $answered );
					break;
				case 'true_or_false':
					$answer_html = $this->true_or_false_question_html( $question, $options, $answered );
					break;
				case 'fill_in_blanks':
					$answer_html = $this->fib_question_html( $question, $options, $answered );
					break;
				default:
					// Allow custom question types via filter.
					$answer_html = apply_filters( 'learnpress/question/render-answers', '', $question, $options, $answered );
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
				'buttons'     => $this->buttons_html( $question, $status ),
				'wrapper_end' => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}

	/**
	 * Convert QuestionAnswerModel array or legacy LP_Question answer options to options format.
	 * Handles both:
	 * - QuestionAnswerModel instances from QuestionPostModel->get_answer_option()
	 * - Array format from LP_Question->get_answer_options()
	 *
	 * @param array  $answer_models Array of QuestionAnswerModel instances or arrays.
	 * @param string $question_type Question type.
	 *
	 * @return array
	 * @since 4.2.9.4
	 * @version 1.0.1
	 */
	protected function convert_answers_to_options( $answer_models, $question_type = 'single_choice' ) {
		$options = array();

		if ( empty( $answer_models ) || ! is_array( $answer_models ) ) {
			return $options;
		}

		foreach ( $answer_models as $answer ) {
			// Check if it's a QuestionAnswerModel object or legacy array format.
			$is_model = is_object( $answer ) && isset( $answer->question_answer_id );

			if ( $is_model ) {
				// Handle QuestionAnswerModel format.
				$option = (object) array(
					'uid'   => $answer->question_answer_id,
					'value' => $answer->value,
					'title' => $answer->title,
				);

				if ( $question_type === 'fill_in_blanks' ) {
					$metadata    = $answer->get_all_metadata();
					$option->ids = isset( $metadata->ids ) ? $metadata->ids : array();
				} else {
					$option->isTrue = $answer->is_true === 'yes' ? 'yes' : '';
				}
			} else {
				// Handle legacy LP_Question array format.
				$option = (object) array(
					'uid'   => isset( $answer['question_answer_id'] ) ? $answer['question_answer_id'] : ( isset( $answer['uid'] ) ? $answer['uid'] : uniqid() ),
					'value' => isset( $answer['value'] ) ? $answer['value'] : '',
					'title' => isset( $answer['title'] ) ? $answer['title'] : '',
				);

				if ( $question_type === 'fill_in_blanks' ) {
					// For fill-in-blanks, LP_Question_Fill_In_Blanks already provides 'ids'.
					$option->ids = isset( $answer['ids'] ) ? $answer['ids'] : array();
				} else {
					$option->isTrue = isset( $answer['is_true'] ) && $answer['is_true'] === 'yes' ? 'yes' : '';
				}
			}

			$options[] = $option;
		}

		return $options;
	}

	/**
	 * Render multiple choice question HTML.
	 *
	 * @param QuestionPostModel $question Question post model.
	 * @param array             $options  Question options/answers.
	 * @param string            $answered User's answered value(s).
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function multi_choice_question_html( QuestionPostModel $question, $options, $answered = '' ) {
		try {
			$question_id  = $question->get_id();
			$options_html = '';

			foreach ( $options as $option ) {
				$option_uid   = $option->uid ?? uniqid();
				$option_value = $option->value ?? '';
				$option_title = $option->title ?? $option_value;
				$is_checked   = is_array( $answered ) && in_array( $option_value, $answered, true );
				$input_id     = 'learn-press-answer-option-' . $option_uid;

				$options_html .= sprintf(
					'<li class="answer-option">
						<input type="checkbox" class="option-check" name="learn-press-question-%1$s" id="%2$s" value="%3$s" %4$s />
						<label for="%2$s" class="option-title">%5$s</label>
					</li>',
					esc_attr( $question_id ),
					esc_attr( $input_id ),
					esc_attr( $option_value ),
					checked( $is_checked, true, false ),
					wp_kses_post( $option_title )
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
	 * @param QuestionPostModel $question Question post model.
	 * @param array             $options  Question options/answers.
	 * @param string            $answered User's answered value.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function single_choice_question_html( QuestionPostModel $question, $options, $answered = '' ) {
		try {
			$question_id  = $question->get_id();
			$options_html = '';

			foreach ( $options as $option ) {
				$option_uid   = $option->uid ?? uniqid();
				$option_value = $option->value ?? '';
				$option_title = $option->title ?? $option_value;
				$is_checked   = $answered === $option_value;
				$input_id     = 'learn-press-answer-option-' . $option_uid;

				$options_html .= sprintf(
					'<li class="answer-option">
						<input type="radio" class="option-check" name="learn-press-question-%1$s" id="%2$s" value="%3$s" %4$s />
						<label for="%2$s" class="option-title">%5$s</label>
					</li>',
					esc_attr( $question_id ),
					esc_attr( $input_id ),
					esc_attr( $option_value ),
					checked( $is_checked, true, false ),
					wp_kses_post( $option_title )
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
	 * @param QuestionPostModel $question Question post model.
	 * @param array             $options  Question options/answers.
	 * @param string            $answered User's answered value.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function true_or_false_question_html( QuestionPostModel $question, $options, $answered = '' ) {
		try {
			$question_id  = $question->get_id();
			$options_html = '';

			foreach ( $options as $option ) {
				$option_uid   = $option->uid ?? uniqid();
				$option_value = $option->value ?? '';
				$option_title = $option->title ?? $option_value;
				$is_checked   = $answered === $option_value;
				$input_id     = 'learn-press-answer-option-' . $option_uid;

				$options_html .= sprintf(
					'<li class="answer-option">
						<input type="radio" class="option-check" name="learn-press-question-%1$s" id="%2$s" value="%3$s" %4$s />
						<label for="%2$s" class="option-title">%5$s</label>
					</li>',
					esc_attr( $question_id ),
					esc_attr( $input_id ),
					esc_attr( $option_value ),
					checked( $is_checked, true, false ),
					wp_kses_post( $option_title )
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
	 * @param QuestionPostModel $question Question post model.
	 * @param array             $options  Question options/blanks.
	 * @param array             $answered User's answered values.
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function fib_question_html( QuestionPostModel $question, $options, $answered = array() ) {
		try {
			$question_id  = $question->get_id();
			$content_html = '';

			foreach ( $options as $option ) {
				$option_uid   = $option->uid ?? uniqid();
				$option_title = $option->title ?? '';
				$option_ids   = $option->ids ?? array();

				// Process fill-in-blank placeholders.
				$processed_title = $option_title;
				foreach ( $option_ids as $blank_id ) {
					$placeholder  = '{{FIB_' . $blank_id . '}}';
					$answer_value = isset( $answered[ $blank_id ] ) ? esc_attr( $answered[ $blank_id ] ) : '';
					$input_html   = sprintf(
						'<div class="lp-fib-input" style="display: inline-block; width: auto;">
							<input type="text" data-id="%s" value="%s" />
						</div>',
						esc_attr( $blank_id ),
						$answer_value
					);

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
	 * @version 1.0.0
	 */
	public function title_html( QuestionPostModel $question, $question_index = 0 ) {
		try {
			$question_title = $question->get_the_title();
			$index_html     = '';

			if ( $question_index > 0 ) {
				$index_html = sprintf(
					'<span class="question-index">%s.</span>',
					esc_html( $question_index )
				);
			}

			$section = array(
				'wrapper'     => '<h4 class="question-title">',
				'index'       => $index_html,
				'title'       => sprintf( '<span>%s</span>', wp_kses_post( $question_title ) ),
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
	 *
	 * @return string
	 * @since 4.2.9.4
	 * @version 1.0.0
	 */
	public function buttons_html( QuestionPostModel $question, $status = 'started' ) {
		try {
			// Only show buttons when quiz is started.
			if ( 'started' !== $status ) {
				return '';
			}

			$question_id = $question->get_id();

			$section = array(
				'wrapper'      => '<div class="question-buttons">',
				'check_button' => sprintf(
					'<button type="button" class="lp-button lp-button-check" data-question-id="%s">%s</button>',
					esc_attr( $question_id ),
					esc_html__( 'Check', 'learnpress' )
				),
				'hint_button'  => sprintf(
					'<button type="button" class="lp-button lp-button-hint" data-question-id="%s">%s</button>',
					esc_attr( $question_id ),
					esc_html__( 'Hint', 'learnpress' )
				),
				'wrapper_end'  => '</div>',
			);

			return Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
			return '';
		}
	}
}