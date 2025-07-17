<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
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
			'tinymce'  => Template::editor_tinymce(
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
			'tinymce'  => Template::editor_tinymce(
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
			'tinymce'  => Template::editor_tinymce(
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
				'lp-question-answer-item-title-input',
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

		$html_answers = '';
		foreach ( $answers as $answer ) {
			$questionAnswerModel = new QuestionAnswerModel( $answer );

			$html_answers .= sprintf(
				'<div class="lp-question-answer-item" data-answer-id="%6$s">
					<span class="drag lp-icon-drag" title="Drag to reorder section"></span>
					<input type="text" class="%1$s" name="%1$s" value="%2$s" />
					<input type="radio" class="lp-input-answer-set-true" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-item-title-input',
				esc_attr( $questionAnswerModel->title ),
				$questionAnswerModel->is_true === 'yes' ? 'checked' : '',
				$name_radio,
				esc_attr( $questionAnswerModel->is_true ),
				esc_attr( $questionAnswerModel->question_answer_id )
			);
		}

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

		$html_answers = '';
		foreach ( $answers as $answer ) {
			$questionAnswerModel = new QuestionAnswerModel( $answer );

			$html_answers .= sprintf(
				'<div class="lp-question-answer-item" data-answer-id="%6$s">
					<span class="drag lp-icon-drag" title="Drag to reorder section"></span>
					<input type="text" class="%1$s" name="%1$s" value="%2$s" />
					<input type="checkbox" class="lp-input-answer-set-true" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-item-title-input',
				esc_attr( $questionAnswerModel->title ),
				$questionAnswerModel->is_true === 'yes' ? 'checked' : '',
				$name_checkbox,
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
	 * Get html edit question type fill in blanks.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_type_fill_in_blanks( QuestionPostModel $questionPostModel ): string {
		$name = 'lp-question-fib-input-' . $questionPostModel->ID;
		$questionPostModel->get_answer_option();

		$section = [
			'input'            => sprintf(
				'<textarea name="%s" rows="5" %s>1231</textarea>',
				esc_attr( $name ),
				'style="width: 100%"'
			),
			'buttons'          => '<div class="lp-question-fib-buttons">',
			'btn_insert_new'   => sprintf(
				'<button type="button" class="lp-btn-fib-insert-blank button">%s</button>',
				__( 'Insert new blank', 'learnpress' )
			),
			'btn_remove_blank' => sprintf(
				'<button type="button" class="lp-btn-fib-remove-blank button">%s</button>',
				__( 'Remove blank', 'learnpress' )
			),
			'btn_clear'        => sprintf(
				'<button type="button" class="lp-btn-fib-clear-content button">%s</button>',
				__( 'Clear content', 'learnpress' )
			),
			'buttons_end'      => '</div>',
		];

		return Template::combine_components( $section );
	}
}
