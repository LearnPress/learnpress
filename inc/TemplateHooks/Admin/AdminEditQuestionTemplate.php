<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LP_Helper;

/**
 * Template Admin Edit Quiz.
 *
 * @since 4.2.8.8
 * @version 1.0.0
 */
class AdminEditQuestionTemplate {
	use Singleton;

	public function init() {
	}

	public function html_edit_mark( $questionPostModel = null ): string {
		$point = 0;
		if ( $questionPostModel instanceof QuestionPostModel ) {
			$point = $questionPostModel->get_mark();
		}

		$section = [
			'wrap'     => '<div class="lp-question-point">',
			'label'    => sprintf(
				'<label for="lp-question-point">%s</label>',
				__( 'Points', 'learnpress' )
			),
			'input'    => sprintf(
				'<input type="number" name="lp-question-point-input" id="lp-question-point" value="%s" min="0" step="0.01">',
				esc_attr( $point )
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_edit_hint( $questionPostModel = null ): string {
		$hint        = '';
		$question_id = 0;
		if ( $questionPostModel instanceof QuestionPostModel ) {
			$question_id = $questionPostModel->ID;
			$hint        = $questionPostModel->get_hint();
		}

		$editor_id = 'lp-question-hint-' . $question_id;

		$section = [
			'wrap'     => '<div class="lp-question-hint">',
			'label'    => sprintf(
				'<label for="lp-question-hint">%s</label>',
				__( 'Hint', 'learnpress' )
			),
			'tinymce'  => AdminTemplate::editor_tinymce(
				$hint,
				$editor_id
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_edit_explanation( $questionPostModel = null ): string {
		$explanation = '';
		$question_id = 0;
		if ( $questionPostModel instanceof QuestionPostModel ) {
			$question_id = $questionPostModel->ID;
			$explanation = $questionPostModel->get_explanation();
		}

		$editor_id = 'lp-question-explanation-' . $question_id;

		$section = [
			'wrap'     => '<div class="lp-question-explanation">',
			'label'    => sprintf(
				'<label for="lp-question-explanation">%s</label>',
				__( 'Explanation', 'learnpress' )
			),
			'tinymce'  => AdminTemplate::editor_tinymce(
				$explanation,
				$editor_id
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for edit question description.
	 *
	 * @param QuestionPostModel|null $questionPostModel
	 *
	 * @return string
	 */
	public function html_edit_question_description( $questionPostModel = null ): string {
		$question_description = '';
		$question_id          = 0;

		if ( $questionPostModel instanceof QuestionPostModel ) {
			$question_id          = $questionPostModel->ID;
			$question_description = $questionPostModel->post_content;
		}

		$editor_id = 'lp-question-description-' . $question_id;

		$section = [
			'wrap'     => '<div class="lp-question-description">',
			'label'    => sprintf(
				'<label for="lp-question-description">%s</label>',
				__( 'Description', 'learnpress' )
			),
			'textarea' => sprintf(
				'<div name="lp-editor-wysiwyg" class="lp-editor-wysiwyg">%s</div>',
				htmlspecialchars_decode( $question_description )
			),
			'tinymce'  => AdminTemplate::editor_tinymce(
				$question_description,
				$editor_id
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_edit_question_by_type( $questionPostModel ): string {
		$section = [];

		return Template::combine_components( $section );
	}

	/**
	 * HTML input question title.
	 *
	 * @param string $question_title
	 *
	 * @return string
	 */
	public function html_input_question_title( string $question_title = '' ): string {
		return sprintf(
			'<input class="lp-question-title-input"
				name="lp-question-title-input"
				type="text"
				value="%1$s"
				data-old="%1$s"
				placeholder="%2$s"
				data-mess-empty-title="%3$s">',
			esc_attr( $question_title ),
			esc_attr__( 'Update question title', 'learnpress' ),
			esc_attr__( 'Question title is required', 'learnpress' )
		);
	}

	/**
	 * HTML for add new question.
	 *
	 * @return string
	 */
	public function html_add_new_question(): string {
		$html_question_types = '';
		$question_types      = QuestionPostModel::get_types();
		foreach ( $question_types as $type => $label ) {
			$html_question_types .= sprintf(
				'<option value="%s">%s</option>',
				esc_attr( $type ),
				esc_html( $label )
			);
		}
		$html_question_types = Template::instance()->nest_elements(
			[ '<select class="lp-question-type-new" name="lp-question-type-new">' => '</select>' ],
			$html_question_types
		);

		$section = [
			'wrap'     => '<div class="add-new-question">',
			'icon'     => '<span class="lp-icon-plus"></span>',
			'input'    => sprintf(
				'<input class="lp-question-title-new-input"
					name="lp-question-title-new-input"
					type="text"
					title="%1$s"
					placeholder="%1$s"
					data-mess-empty-title="%2$s">',
				esc_attr__( 'Create a new section', 'learnpress' ),
				esc_attr__( 'Section title is required', 'learnpress' )
			),
			'types'    => $html_question_types,
			'button'   => sprintf(
				'<button type="button" class="lp-btn-add-section button">%s</button>',
				__( 'Add Question', 'learnpress' )
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html edit question type.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_option( QuestionPostModel $questionPostModel ): string {
		$type = $questionPostModel->get_type();

		switch ( $type ) {
			case 'true_or_false':
				$html = self::html_answer_type_true_or_false( $questionPostModel );
				break;
			case 'single_choice':
				$html = self::html_answer_type_single_choice( $questionPostModel );
				break;
			case 'multi_choice':
				$html = self::html_answer_type_multiple_choice( $questionPostModel );
				break;
			case 'fill_in_blanks':
				$html = self::html_answer_type_fill_in_blanks( $questionPostModel );
				break;
			case $type:
				$html = apply_filters( 'learn-press/edit-question/type/html', '', $type, $questionPostModel );
				break;
			default:
				$html = '';
				break;
		}

		$answers = $questionPostModel->get_answer_option();
		if ( $questionPostModel->get_type() === 'fill_in_blanks' ) {
			$answers = $answers[0] ?? [];
		}

		$section = [
			'wrap'     => sprintf(
				'<div class="lp-answers-config" data-question-type="%s" data-answers="%s">',
				esc_attr( $type ),
				Template::convert_data_to_json( $answers )
			),
			'answers'  => $html,
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html edit question type true or false.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_type_true_or_false( QuestionPostModel $questionPostModel ): string {
		$name_radio = 'lp-input-answer-set-true-' . $questionPostModel->ID;
		$answers    = $questionPostModel->get_answer_option();

		$html_answers = '';
		foreach ( $answers as $answer ) {
			$questionAnswerModel = new QuestionAnswerModel( $answer );

			$html_answers .= sprintf(
				'<div class="lp-question-answer-item" data-answer-id="%6$s">
					<span class="drag lp-icon-drag" title="Drag to reorder section"></span>
					<input type="text" class="%1$s" name="%1$s" value="%2$s" />
					<input type="radio" class="lp-input-answer-set-true" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-title-input',
				esc_attr( $questionAnswerModel->title ),
				$questionAnswerModel->is_true === 'yes' ? 'checked' : '',
				$name_radio,
				esc_attr( $questionAnswerModel->is_true ),
				esc_attr( $questionAnswerModel->question_answer_id )
			);
		}

		$section = [
			'header'  => '<div class="lp-question-choice-header"><span>Answers</span><span>Correct</span></div>',
			'answers' => $html_answers,
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html edit single choice.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_type_single_choice( QuestionPostModel $questionPostModel ): string {
		$name_radio = 'lp-input-answer-set-true-' . $questionPostModel->ID;
		$answers    = $questionPostModel->get_answer_option();
		$answers[]  = null;

		$html_answers = '';
		foreach ( $answers as $answer ) {
			$is_clone           = true;
			$title              = '';
			$is_true            = 'no';
			$question_answer_id = 0;

			if ( ! is_null( $answer ) ) {
				$is_clone            = false;
				$questionAnswerModel = new QuestionAnswerModel( $answer );
				$title               = $questionAnswerModel->title;
				$is_true             = $questionAnswerModel->is_true;
				$question_answer_id  = $questionAnswerModel->question_answer_id;
			}

			$html_answers .= sprintf(
				'<div class="lp-question-answer-item %7$s" data-answer-id="%6$s">
					<span class="drag lp-icon-drag" title="Drag to reorder section"></span>
					%8$s
					<input type="text" class="%1$s" name="%1$s" value="%2$s" />
					<span class="lp-icon-trash-o lp-btn-delete-question-answer"></span>
					<input type="radio" class="lp-input-answer-set-true" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-title-input',
				esc_attr( $title ),
				$is_true === 'yes' ? 'checked' : '',
				$name_radio,
				esc_attr( $is_true ),
				esc_attr( $question_answer_id ),
				$is_clone ? 'clone lp-hidden' : '',
				$is_clone ? '<span class="lp-icon-spinner"></span>' : ''
			);
		}

		$html_answers .= sprintf(
			'<div class="lp-question-answer-item lp-question-answer-item-add-new">
				<span class="lp-icon-plus" title="Add answer option"></span>
				<input type="text" class="%1$s" name="%1$s" value="" />
				<button type="button" class="button lp-btn-add-question-answer">%2$s</button>
			</div>',
			'lp-question-answer-title-input',
			__( 'Add option', 'learnpress' )
		);

		$section = [
			'header'    => '<div class="lp-question-choice-header"><span>Answers</span><span>Correct</span></div>',
			'answers'   => $html_answers,
			'data_json' => sprintf(
				'<input type="hidden" class="lp-question-answer-json" name="lp-question-answer-json" value="%s">',
				esc_attr( json_encode( $answers ) )
			),
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html edit question type multiple choice.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_type_multiple_choice( QuestionPostModel $questionPostModel ): string {
		$name_checkbox = 'lp-input-answer-set-true-' . $questionPostModel->ID;
		$answers       = $questionPostModel->get_answer_option();
		$answers[]     = null;

		$html_answers = '';
		foreach ( $answers as $answer ) {
			$is_clone           = true;
			$title              = '';
			$is_true            = 'no';
			$question_answer_id = 0;

			if ( ! is_null( $answer ) ) {
				$is_clone            = false;
				$questionAnswerModel = new QuestionAnswerModel( $answer );
				$title               = $questionAnswerModel->title;
				$is_true             = $questionAnswerModel->is_true;
				$question_answer_id  = $questionAnswerModel->question_answer_id;
			}

			$html_answers .= sprintf(
				'<div class="lp-question-answer-item %7$s" data-answer-id="%6$s">
					<span class="drag lp-icon-drag" title="Drag to reorder section"></span>
					%8$s
					<input type="text" class="%1$s" name="%1$s" value="%2$s" />
					<span class="lp-icon-trash-o lp-btn-delete-question-answer"></span>
					<input type="checkbox" class="lp-input-answer-set-true" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-title-input',
				esc_attr( $title ),
				$is_true === 'yes' ? 'checked' : '',
				$name_checkbox,
				esc_attr( $is_true ),
				esc_attr( $question_answer_id ),
				$is_clone ? 'clone lp-hidden' : '',
				$is_clone ? '<span class="lp-icon-spinner"></span>' : ''
			);
		}

		$html_answers .= sprintf(
			'<div class="lp-question-answer-item lp-question-answer-item-add-new">
				<span class="lp-icon-plus" title="Add answer option"></span>
				<input type="text" class="%1$s" name="%1$s" value="" />
				<button type="button" class="button lp-btn-add-question-answer">%2$s</button>
			</div>',
			'lp-question-answer-title-input',
			__( 'Add option', 'learnpress' )
		);

		$section = [
			'header'  => '<div class="lp-question-choice-header"><span>Answers</span><span>Correct</span></div>',
			'answers' => $html_answers,
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get html edit question type fill in blanks.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_type_fill_in_blanks( QuestionPostModel $questionPostModel ): string {
		$questionPostFIBModel = new QuestionPostFIBModel( $questionPostModel );
		$name                 = 'lp-question-fib-input-' . $questionPostFIBModel->ID;
		$answers              = $questionPostFIBModel->get_answer_option();
		$options              = [
			'clone' =>
				[
					'fill'       => '',
					'match_case' => 0,
					'comparison' => 'equal',
					'id'         => 'clone',
				],
		];
		/**
		 * @var QuestionAnswerModel[] $answers
		 */
		$questionAnswerModel = $answers[0] ?? null;
		if ( $questionAnswerModel instanceof QuestionAnswerModel ) {
			$meta_data = $questionAnswerModel->get_all_metadata();

			if ( is_array( $meta_data ) ) {
				$options = array_merge(
					$options,
					$meta_data
				);
			}
		}

		$content = '';

		if ( $questionAnswerModel instanceof QuestionAnswerModel ) {
			$content = $this->convert_content_fib_to_span( $questionAnswerModel->title );
		}

		$section = [
			'input'                 => AdminTemplate::editor_tinymce(
				$content,
				$name,
				[
					'default_editor' => 'html', // Use HTML editor by default tinymce
					'tinymce'        => true,
					'quicktags'      => true, // Set true to show tab "Code" in editor
				]
			),
			'buttons'               => '<div class="lp-question-fib-buttons">',
			'btn-insert-new'        => sprintf(
				'<button type="button" class="lp-btn-fib-insert-blank button" data-default-text="%s">%s</button>',
				esc_html__( 'Enter answer correct on here', 'learnpress' ),
				__( 'Insert new blank', 'learnpress' )
			),
			'btn-delete-all-blanks' => sprintf(
				'<button type="button"
							class="lp-btn-fib-delete-all-blanks button"
							data-title="%s"
							data-content="%s"
							title="%s">%s
						</button>',
				esc_attr__( 'Are you sure?', 'learnpress' ),
				esc_html__( 'This action will delete all blanks in the editor, only keep text content.', 'learnpress' ),
				esc_attr__( 'Delete all blanks on this editor.', 'learnpress' ),
				__( 'Delete all blanks', 'learnpress' )
			),
			'btn-save'              => sprintf(
				'<button type="button" class="lp-btn-fib-save-content button">%s</button>',
				__( 'Save content', 'learnpress' )
			),
			'btn-clear'             => sprintf(
				'<button type="button"
							class="lp-btn-fib-clear-all-content button"
							data-title="%s"
							data-content="%s"
							title="%s">%s
						</button>',
				esc_attr__( 'Are you sure?', 'learnpress' ),
				esc_html__( 'This action will delete all content in the editor.', 'learnpress' ),
				esc_attr__( 'Clear all content on this editor.', 'learnpress' ),
				__( 'Clear content', 'learnpress' )
			),
			'buttons_end'           => '</div>',
			'options'               => $this->html_fib_options( $options ),
		];

		return Template::combine_components( $section );
	}

	public function html_fib_options( $options ): string {
		if ( empty( $options ) || ! is_array( $options ) ) {
			return '';
		}

		$html = '';
		$i    = 1;
		foreach ( $options as $option ) {
			$match_case = $option['match_case'] ?? 0;
			$comparison = $option['comparison'] ?? '';
			$id         = $option['id'] ?? '';

			if ( $id === 'clone' ) {
				$i = 0;
			}

			$section_header = [
				'wrap'       => '<div class="lp-question-fib-option-header">',
				'number'     => sprintf(
					'<span class="lp-question-fib-option-index">%s.</span>',
					$i++
				),
				'title'      => sprintf(
					'<input type="text"
						name="lp-question-fib-option-title-%s"
						value="%s"
						class="lp-question-fib-option-title" />',
					esc_attr( $id ),
					esc_html( $option['fill'] ?? '' )
				),
				'btn-save'   => sprintf(
					'<button type="button" class="lp-btn-fib-option-save button">%s</button>',
					__( 'Save', 'learnpress' )
				),
				'btn-delete' => sprintf(
					'<button type="button"
								class="lp-btn-fib-option-delete button"
								data-title="%s" data-content="%s">%s
							</button>',
					esc_attr__( 'Are you sure?', 'learnpress' ),
					esc_html__( 'Delete this blank and keep text', 'learnpress' ),
					__( 'Remove', 'learnpress' )
				),
				'toggle'     => '<div class="lp-trigger-toggle"><span class="lp-icon-angle-down"></span><span class="lp-icon-angle-up"></span></div>',
				'wrap_end'   => '</div>',
			];

			$section_detail = [
				'wrap'            => '<div class="lp-question-fib-option-detail lp-section-collapse">',
				'match-case'      => sprintf(
					'<label>
						<input type="checkbox" class="lp-question-fib-option-match-case" %s name="%s" data-key="%s" value="1" /> %s
					</label>',
					$match_case ? 'checked' : '',
					esc_attr( 'lp-question-fib-option-match-case-' . $id ),
					'match_case',
					__( 'Match case', 'learnpress' )
				),
				'separator-label' => sprintf(
					'<div>%s</div>',
					__( 'Comparison', 'learnpress' )
				),
				'equal'           => sprintf(
					'<div>
						<label>
						<input type="radio"
							data-key ="comparison"
							name="lp-question-fib-option-comparison-%s"
							class="lp-question-fib-option-comparison" value="equal" %s /> %s
						</label>
						<p>%s</p>
					</div>',
					esc_attr( $id ),
					$comparison === 'equal' || $id === 'clone' ? 'checked' : '',
					__( 'Equal', 'learnpress' ),
					__( 'Match two words are equality.', 'learnpress' )
				),
				'range'           => sprintf(
					'<div>
						<label>
							<input type="radio"
							data-key ="comparison"
							name="lp-question-fib-option-comparison-%s"
							class="lp-question-fib-option-comparison" value="range" %s /> %s
						</label>
						<p>%s</p>
					</div>',
					esc_attr( $id ),
					$comparison === 'range' ? 'checked' : '',
					__( 'Range', 'learnpress' ),
					__( 'Match any number in a range. Use 100, 200 to match any value from 100 to 200.', 'learnpress' )
				),
				'any'             => sprintf(
					'<div>
						<label>
							<input type="radio"
							data-key ="comparison"
							name="lp-question-fib-option-comparison-%s"
							class="lp-question-fib-option-comparison" value="any" %s /> %s
						</label>
						<p>%s</p>
					</div>',
					esc_attr( $id ),
					$comparison === 'any' ? 'checked' : '',
					__( 'Any', 'learnpress' ),
					__( 'Match any value in a set of words. Use fill, blank, or question to match any value in the set.', 'learnpress' )
				),
				'wrap_end'        => '</div>',
			];

			$section = [
				'wrap'     => sprintf(
					'<div class="lp-question-fib-blank-option-item %s lp-section-toggle lp-collapse" data-id="%s">',
					$id === 'clone' ? 'clone lp-hidden' : '',
					esc_attr( $id )
				),
				'header'   => Template::combine_components( $section_header ),
				'detail'   => Template::combine_components( $section_detail ),
				'wrap_end' => '</div>',
			];

			$html .= Template::combine_components( $section );
		}

		$html = Template::instance()->nest_elements(
			[ '<div class="lp-question-fib-blank-options">' => '</div>' ],
			$html
		);

		return $html;
	}

	/**
	 * Convert content of FIB to input tag HTML
	 *
	 * @param string $content
	 *
	 * @return string
	 * @since 4.2.8.8
	 * @version 1.0.0
	 */
	public function convert_content_fib_to_input( string $content = '' ): string {
		$regex_str = get_shortcode_regex( array( 'fib' ) );
		$pattern   = "/{$regex_str}/";
		preg_match_all(
			$pattern,
			$content,
			$all_shortcode,
			PREG_SET_ORDER
		);

		if ( ! empty( $all_shortcode ) ) {
			foreach ( $all_shortcode as $shortcode ) {
				$atts = shortcode_parse_atts( $shortcode[0] );

				$fill = $atts['fill'] ?? '';
				$id   = $atts['id'] ?? '';
				if ( empty( $id ) ) {
					$ida = explode( '=', str_replace( ']', '', $atts[1] ) );
					$id  = isset( $ida[1] ) ? str_replace( '"', '', $ida[1] ) : '';
				}

				$new_str = sprintf(
					'<input type="text" class="lp-question-fib-input"
						name="lp-question-fib-input" value="%s" data-id="%s" style="%s" />',
					$fill,
					esc_attr( $id ),
					'border: 1px dashed rebeccapurple;padding: 5px;margin: 0 3px;'
				);

				$content = str_replace( $shortcode[0], $new_str, $content );
			}
		}

		return $content;
	}

	public function convert_content_fib_to_span( string $content = '' ): string {
		$regex_str = get_shortcode_regex( array( 'fib' ) );
		$pattern   = "/{$regex_str}/";
		preg_match_all(
			$pattern,
			$content,
			$all_shortcode,
			PREG_SET_ORDER
		);

		if ( ! empty( $all_shortcode ) ) {
			foreach ( $all_shortcode as $shortcode ) {
				$atts = shortcode_parse_atts( $shortcode[0] );

				$fill = $atts['fill'] ?? '';
				$id   = $atts['id'] ?? '';
				if ( empty( $id ) ) {
					$ida = explode( '=', str_replace( ']', '', $atts[1] ) );
					$id  = isset( $ida[1] ) ? str_replace( '"', '', $ida[1] ) : '';
				}

				$new_str = sprintf(
					'<span class="lp-question-fib-input" data-id="%s">%s</span>',
					esc_attr( $id ),
					esc_html( $fill )
				);

				$content = str_replace( $shortcode[0], $new_str, $content );
			}
		}

		return $content;
	}
}
