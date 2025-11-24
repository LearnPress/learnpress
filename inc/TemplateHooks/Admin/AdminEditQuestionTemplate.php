<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;

/**
 * Template Admin Edit Quiz.
 *
 * @since 4.2.9
 * @version 1.0.0
 */
class AdminEditQuestionTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/admin/edit-question/layout', [ $this, 'edit_layout' ] );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_filter(
			'wp_default_editor',
			function ( $r ) {
				global $post;
				if ( ! $post ) {
					return $r;
				}

				if ( $post->post_type !== LP_QUESTION_CPT ) {
					return $r;
				}

				return 'html';
			},
			1000
		);
	}

	/**
	 * Layout for edit question.
	 *
	 * @use self::render_edit_question
	 *
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function edit_layout( QuestionPostModel $questionPostModel ) {
		wp_enqueue_style( 'lp-edit-question' );
		wp_enqueue_script( 'lp-edit-question' );

		$args      = [
			'id_url'      => 'edit-question',
			'question_id' => $questionPostModel->ID,
		];
		$call_back = array(
			'class'  => self::class,
			'method' => 'render_edit_question',
		);

		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
	}

	/**
	 * Allow callback for AJAX.
	 * @use self::render_edit_quiz
	 * @use self::render_list_items_not_assign
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_edit_question';

		return $callbacks;
	}

	/**
	 * Render edit question html.
	 *
	 * @throws Exception
	 */
	public static function render_edit_question( array $data ): stdClass {
		$question_id       = $data['question_id'] ?? 0;
		$questionPostModel = QuestionPostModel::find( $question_id, true );
		if ( ! $questionPostModel ) {
			throw new Exception( __( 'Question not found', 'learnpress' ) );
		}

		// Check permission
		$questionPostModel->check_capabilities_create_item_course();

		$content          = new stdClass();
		$content->content = self::instance()->html_edit_question( $questionPostModel );

		return $content;
	}

	public function html_edit_question( QuestionPostModel $questionPostModel ): string {
		$section_edit_details = [
			'wrap'         => '<div class="question-edit-details lp-section-toggle">',
			'header'       => sprintf(
				'<div class="lp-question-data-edit-header lp-trigger-toggle">
					<label>%s</label>
					<span class="lp-icon-angle-down"></span><span class="lp-icon-angle-up"></span>
				</div>',
				__( 'Option Details', 'learnpress' )
			),
			'collapse'     => '<div class="lp-section-collapse">',
			'point'        => AdminEditQuestionTemplate::instance()->html_edit_mark( $questionPostModel ),
			'hint'         => AdminEditQuestionTemplate::instance()->html_edit_hint( $questionPostModel ),
			'explanation'  => AdminEditQuestionTemplate::instance()->html_edit_explanation( $questionPostModel ),
			'collapse_end' => '</div>',
			'wrap_end'     => '</div>',
		];

		$section_edit_main = [
			'wrap'     => sprintf(
				'<div class="lp-edit-question-wrap lp-question-edit-main" data-question-id="%s">',
				esc_attr( $questionPostModel->ID )
			),
			'left'     => '<div class="lp-question-edit-left">',
			'answers'  => AdminEditQuestionTemplate::instance()->html_edit_question_by_type( $questionPostModel ),
			'left_end' => '</div>',
			'details'  => Template::combine_components( $section_edit_details ),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section_edit_main );
	}

	/**
	 * HTML for field settings.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function html_field_settings( array $args = [] ): string {
		$section = [
			'wrap'        => '<div class="lp-question-field-settings">',
			'label'       => sprintf(
				'<div class="lp-question-field-settings__label"><label>%s</label></div>',
				$args['label'] ?? ''
			),
			'input'       => $args['input'] ?? '',
			'description' => sprintf(
				'<div class="lp-question-field-settings___desc">%s</div>',
				$args['description'] ?? ''
			),
			'wrap_end'    => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for edit question mark.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_edit_mark( QuestionPostModel $questionPostModel ): string {
		$point = $questionPostModel->get_mark();

		$args = [
			'label'       => __( 'Points', 'learnpress' ),
			'input'       => sprintf(
				'<input type="number" name="lp-question-point-input"
					class="lp-auto-save-question"
					data-key-auto-save="question_mark"
					value="%s" min="0" step="0.01">',
				esc_attr( $point )
			),
			'description' => __( 'Points for choosing the correct answer.', 'learnpress' ),
		];

		return $this->html_field_settings( $args );
	}

	/**
	 * HTML for edit question hint.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_edit_hint( QuestionPostModel $questionPostModel ): string {
		$question_id = $questionPostModel->ID;
		$hint        = $questionPostModel->get_hint();

		$editor_id = 'lp-question-hint-' . $question_id;

		$args = [
			'label'       => __( 'Hint', 'learnpress' ),
			'input'       => AdminTemplate::editor_tinymce(
				$hint,
				$editor_id,
				[
					'editor_height' => 50,
					'editor_class'  => 'lp-editor-tinymce lp-auto-save-question',
				]
			),
			'description' => __( 'The instructions for the user to select the right answer. The text will be shown when users click the "Hint" button.', 'learnpress' ),
		];

		return $this->html_field_settings( $args );
	}

	/**
	 * HTML for edit question explanation.
	 *
	 * @param QuestionPostModel|null $questionPostModel
	 *
	 * @return string
	 */
	public function html_edit_explanation( QuestionPostModel $questionPostModel ): string {
		$question_id = $questionPostModel->ID;
		$explanation = $questionPostModel->get_explanation();
		$editor_id   = 'lp-question-explanation-' . $question_id;

		$args = [
			'label'       => __( 'Explanation', 'learnpress' ),
			'input'       => AdminTemplate::editor_tinymce(
				$explanation,
				$editor_id,
				[
					'editor_height' => 50,
					'editor_class'  => 'lp-editor-tinymce lp-auto-save-question',
				]
			),
			'description' => __( 'The explanation will be displayed when students click the "Check Answer" button.', 'learnpress' ),
		];

		return $this->html_field_settings( $args );
	}

	/**
	 * HTML for edit question description.
	 *
	 * @param QuestionPostModel|null $questionPostModel
	 *
	 * @return string
	 */
	public function html_edit_question_description( QuestionPostModel $questionPostModel ): string {
		$question_id          = $questionPostModel->ID;
		$question_description = $questionPostModel->get_the_content();
		$editor_id            = 'lp-question-description-' . $question_id;

		$section = [
			'wrap'     => '<div class="lp-question-data-edit">',
			'header'   => sprintf(
				'<div class="lp-question-data-edit-header">
					<label>%s</label>
				</div>',
				__( 'Description', 'learnpress' )
			),
			'tinymce'  => AdminTemplate::editor_tinymce(
				$question_description,
				$editor_id,
				[
					'editor_class'  => 'lp-editor-tinymce lp-auto-save-question',
					'editor_height' => 50,
				]
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for edit question by type.
	 *
	 * @param QuestionPostModel|null $questionPostModel
	 *
	 * @return string
	 */
	public function html_edit_question_by_type( QuestionPostModel $questionPostModel ): string {
		$html_answers_config = AdminEditQuestionTemplate::instance()->html_answer_option( $questionPostModel );

		$section = [
			'wrap'           => '<div class="lp-question-data-edit lp-question-by-type">',
			'header'         => sprintf(
				'<div class="lp-question-data-edit-header">
					<label>%s</label>
					<span class="lp-question-type-label">%s</span>
				</div>',
				__( 'Config Your Answer', 'learnpress' ),
				esc_html( $questionPostModel->get_type_label() )
			),
			/*'button-save'    => sprintf(
				'<button type="button" class="lp-btn-update-question-answer button lp-hidden">%s</button>',
				__( 'Save Answer', 'learnpress' )
			),*/
			'answers-config' => $html_answers_config,
			'wrap_end'       => '</div>',
		];

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
	 * Get html edit question type.
	 *
	 * @param QuestionPostModel $questionPostModel
	 *
	 * @return string
	 */
	public function html_answer_option( QuestionPostModel $questionPostModel ): string {
		$type = $questionPostModel->get_type();

		// For addon sorting choice old <= v4.0.1
		if ( class_exists( 'LP_Addon_Sorting_Choice_Preload' ) && $type === 'sorting_choice' ) {
			if ( version_compare( LP_ADDON_SORTING_CHOICE_VER, '4.0.1', '<=' ) ) {
				$type = 'sorting_choice_old';
			}
		}

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
			case 'sorting_choice_old':
				$html = self::html_answer_type_sorting_choice_old( $questionPostModel );
				break;
			case $type:
				$html = apply_filters( 'learn-press/edit-question/type/html', '', $type, $questionPostModel );
				break;
			default:
				$html = '';
				break;
		}

		// For create new question, type is empty.
		if ( empty( $type ) ) {
			$html_question_types = sprintf(
				'<option value="">%s</option>',
				esc_html__( 'Select question type', 'learnpress' )
			);
			$question_types      = QuestionPostModel::get_types();
			foreach ( $question_types as $type => $label ) {
				$html_question_types .= sprintf(
					'<option value="%s">%s</option>',
					esc_attr( $type ),
					esc_html( $label )
				);
			}
			$html  = Template::instance()->nest_elements(
				[
					sprintf(
						'<select class="lp-question-type-new"
						name="lp-question-type-new" data-mess-empty-type="%s">',
						esc_attr__( 'Question type is required', 'learnpress' )
					)
					=> '</select>',
				],
				$html_question_types
			);
			$html .= sprintf(
				'<button class="lp-btn-question-create-type button"
							type="button">%s %s
						</button>',
				'<span class="lp-icon-spinner"></span> ',
				__( 'Create Question Type', 'learnpress' )
			);
			$html  = Template::instance()->nest_elements(
				[ '<div class="lp-question-type-new-wrap">' => '</div>' ],
				$html
			);
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
					<span class="lp-icon-spinner"></span>
					<input type="text" class="%1$s" name="%1$s" value="%2$s" />
					<input type="radio" class="lp-input-answer-set-true lp-auto-save-question-answer" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-title-input lp-auto-save-question-answer',
				esc_attr( $questionAnswerModel->title ),
				$questionAnswerModel->is_true === 'yes' ? 'checked' : '',
				$name_radio,
				esc_attr( $questionAnswerModel->is_true ),
				esc_attr( $questionAnswerModel->question_answer_id )
			);
		}

		$section = [
			'header'  => sprintf(
				'<div class="lp-question-choice-header">
				<span>%s</span><span>%s</span>
			</div>',
				__( 'Answers', 'learnpress' ),
				__( 'Corrects', 'learnpress' )
			),
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
					<input type="radio" class="lp-input-answer-set-true lp-auto-save-question-answer" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-title-input lp-auto-save-question-answer',
				esc_attr( $title ),
				$is_true === 'yes' ? 'checked' : '',
				$name_radio,
				esc_attr( $is_true ),
				esc_attr( $question_answer_id ),
				$is_clone ? 'clone lp-hidden' : '',
				'<span class="lp-icon-spinner"></span>'
			);
		}

		$html_answers .= sprintf(
			'<div class="lp-question-answer-item-add-new">
				<span class="lp-icon-plus" title="Add answer option"></span>
				<input type="text" class="%1$s" name="%1$s" value="" data-mess-empty-title="%2$s" />
				<button type="button" class="button lp-btn-add-question-answer lp-btn-edit-primary">%3$s</button>
			</div>',
			'lp-question-answer-title-new-input',
			esc_attr__( 'Answer title is required', 'learnpress' ),
			__( 'Add Option', 'learnpress' )
		);

		$section = [
			'header'  => sprintf(
				'<div class="lp-question-choice-header">
				<span>%s</span><span>%s</span>
			</div>',
				__( 'Answers', 'learnpress' ),
				__( 'Corrects', 'learnpress' )
			),
			'answers' => $html_answers,
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
					<input type="checkbox" class="lp-input-answer-set-true lp-auto-save-question-answer" name="%4$s" %3$s value="%5$s" />
				</div>',
				'lp-question-answer-title-input lp-auto-save-question-answer',
				esc_attr( $title ),
				$is_true === 'yes' ? 'checked' : '',
				$name_checkbox,
				esc_attr( $is_true ),
				esc_attr( $question_answer_id ),
				$is_clone ? 'clone lp-hidden' : '',
				'<span class="lp-icon-spinner"></span>'
			);
		}

		$html_answers .= sprintf(
			'<div class="lp-question-answer-item-add-new">
				<span class="lp-icon-plus" title="Add answer option"></span>
				<input type="text" class="%1$s" name="%1$s" value="" data-mess-empty-title="%2$s" />
				<button type="button" class="button lp-btn-add-question-answer lp-btn-edit-primary">%3$s</button>
			</div>',
			'lp-question-answer-title-new-input',
			esc_attr__( 'Answer title is required', 'learnpress' ),
			__( 'Add Option', 'learnpress' )
		);

		$section = [
			'header'  => sprintf(
				'<div class="lp-question-choice-header">
				<span>%s</span><span>%s</span>
			</div>',
				__( 'Answers', 'learnpress' ),
				__( 'Corrects', 'learnpress' )
			),
			'answers' => $html_answers,
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
	public function html_answer_type_sorting_choice_old( QuestionPostModel $questionPostModel ): string {
		$answers   = $questionPostModel->get_answer_option();
		$answers[] = null;

		$html_answers = '';
		foreach ( $answers as $answer ) {
			$is_clone           = true;
			$title              = '';
			$question_answer_id = 0;

			if ( ! is_null( $answer ) ) {
				$is_clone            = false;
				$questionAnswerModel = new QuestionAnswerModel( $answer );
				$title               = $questionAnswerModel->title;
				$question_answer_id  = $questionAnswerModel->question_answer_id;
			}

			$html_answers .= sprintf(
				'<div class="lp-question-answer-item %s" data-answer-id="%d">
					<span class="drag lp-icon-drag" title="Drag to reorder section"></span>
					<span class="lp-icon-spinner"></span>
					<input type="text" class="%s" name="%s" value="%s" />
					<span class="lp-icon-trash-o lp-btn-delete-question-answer"></span>
				</div>',
				$is_clone ? 'clone lp-hidden' : '',
				esc_attr( $question_answer_id ),
				'lp-question-answer-title-input lp-auto-save-question-answer',
				'lp-question-answer-title-input',
				esc_attr( $title )
			);
		}

		$html_answers .= sprintf(
			'<div class="lp-question-answer-item-add-new">
				<span class="lp-icon-plus" title="Add answer option"></span>
				<input type="text" class="%1$s" name="%1$s" value="" data-mess-empty-title="%2$s" />
				<button type="button" class="button lp-btn-add-question-answer lp-btn-edit-primary">%3$s</button>
			</div>',
			'lp-question-answer-title-new-input',
			esc_attr__( 'Answer title is required', 'learnpress' ),
			__( 'Add Option', 'learnpress' )
		);

		$section = [
			'header'  => sprintf(
				'<div class="lp-question-choice-header">
				<span>%s</span><span>%s</span>
			</div>',
				__( 'Answers', 'learnpress' ),
				__( 'Corrects', 'learnpress' )
			),
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
					$meta_data,
					$options
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
			'desc'                  => sprintf(
				'<div class="lp-question-fib-desc">%s</div>',
				__( 'Select a word in the passage above and click "Insert a new blank" to make that word a blank for filling.', 'learnpress' )
			),
			'buttons'               => '<div class="lp-question-fib-buttons">',
			'btn-insert-new'        => sprintf(
				'<button type="button" class="lp-btn-fib-insert-blank button"
					data-default-text="%s"
					data-mess-inserted="%s"
					data-mess-require-select-text="%s">%s %s</button>',
				esc_html__( 'Enter answer correct on here', 'learnpress' ),
				__( 'This text inserted to blank', 'learnpress' ),
				__( 'You must select a text or position on Editor to insert new blank.', 'learnpress' ),
				'<span class="lp-icon-spinner"></span>',
				__( 'Insert new blank', 'learnpress' )
			),
			'btn-delete-all-blanks' => sprintf(
				'<button type="button"
							class="lp-btn-fib-delete-all-blanks button"
							data-title="%s"
							data-content="%s"
							title="%s">%s %s
						</button>',
				esc_attr__( 'Are you sure?', 'learnpress' ),
				esc_html__( 'This action will delete all blanks in the editor, only keep text content.', 'learnpress' ),
				esc_attr__( 'Delete all blanks on this editor.', 'learnpress' ),
				'<span class="lp-icon-spinner"></span>',
				__( 'Delete all blanks', 'learnpress' )
			),
			'btn-save'              => sprintf(
				'<button type="button" class="lp-btn-fib-save-content button lp-btn-edit-primary active">%s %s</button>',
				'<span class="lp-icon-spinner"></span> ',
				__( 'Save content', 'learnpress' )
			),
			'btn-clear'             => sprintf(
				'<button type="button"
							class="lp-btn-fib-clear-all-content button"
							data-title="%s"
							data-content="%s"
							title="%s">%s %s
						</button>',
				esc_attr__( 'Are you sure?', 'learnpress' ),
				esc_html__( 'This action will delete all content in the editor.', 'learnpress' ),
				esc_attr__( 'Clear all content on this editor.', 'learnpress' ),
				'<span class="lp-icon-spinner"></span>',
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

			/*if ( $id === 'clone' ) {
				$i = 0;
			}*/

			$section_header = [
				'wrap'       => '<div class="lp-question-fib-option-header">',
				'number'     => sprintf(
					'<span class="lp-question-fib-option-index">%s.</span>',
					$i++
				),
				'loading'    => '<span class="lp-icon-spinner"></span>',
				'title'      => sprintf(
					'<input type="text"
						name="lp-question-fib-option-title-input-%s"
						value="%s"
						class="lp-question-fib-option-title-input" />',
					esc_attr( $id ),
					esc_html( $option['fill'] ?? '' )
				),
				'btn-delete' => sprintf(
					'<span
						class="lp-btn-fib-option-delete lp-icon-trash-o"
						data-title="%s" data-content="%s">
					</span>',
					esc_attr__( 'Are you sure?', 'learnpress' ),
					esc_html__( 'Delete this blank and keep text', 'learnpress' )
				),
				'toggle'     => '<div class="lp-trigger-toggle"><span class="lp-icon-angle-down"></span><span class="lp-icon-angle-up"></span></div>',
				'wrap_end'   => '</div>',
			];

			$section_detail = [
				'wrap'                => '<div class="lp-question-fib-option-detail lp-section-collapse">',
				'match-case'          => sprintf(
					'<label>
						<input type="checkbox" class="lp-question-fib-option-match-case-input" %s name="%s" data-key="%s" value="1" /> %s
					</label>',
					$match_case ? 'checked' : '',
					esc_attr( 'lp-question-fib-option-match-case-input' . $id ),
					'match_case',
					__( 'Match case', 'learnpress' )
				),
				'wrap_match_case'     => sprintf(
					'<div class="lp-question-fib-option-match-case-wrap %s">',
					$match_case ? '' : 'lp-hidden'
				),
				'separator-label'     => sprintf(
					'<div>%s</div>',
					__( 'Comparison', 'learnpress' )
				),
				'equal'               => sprintf(
					'<div>
						<label>
						<input type="radio"
							data-key ="comparison"
							name="lp-question-fib-option-comparison-input-%s"
							class="lp-question-fib-option-comparison-input" value="equal" %s /> %s
						</label>
						<p>%s</p>
					</div>',
					esc_attr( $id ),
					$comparison === 'equal' ? 'checked' : '',
					__( 'Equal', 'learnpress' ),
					__( 'Match two words are equality.', 'learnpress' )
				),
				'range'               => sprintf(
					'<div>
						<label>
							<input type="radio"
							data-key ="comparison"
							name="lp-question-fib-option-comparison-input-%s"
							class="lp-question-fib-option-comparison-input" value="range" %s /> %s
						</label>
						<p>%s</p>
					</div>',
					esc_attr( $id ),
					$comparison === 'range' ? 'checked' : '',
					__( 'Range', 'learnpress' ),
					__( 'Match any number in a range. Use 100, 200 to match any value from 100 to 200.', 'learnpress' )
				),
				'any'                 => sprintf(
					'<div>
						<label>
							<input type="radio"
							data-key ="comparison"
							name="lp-question-fib-option-comparison-input-%s"
							class="lp-question-fib-option-comparison-input" value="any" %s /> %s
						</label>
						<p>%s</p>
					</div>',
					esc_attr( $id ),
					$comparison === 'any' ? 'checked' : '',
					__( 'Any', 'learnpress' ),
					__( 'Match any value in a set of words. Use fill, blank, or question to match any value in the set.', 'learnpress' )
				),
				'wrap_match_case_end' => '</div>', // End match case wrap
				'wrap_end'            => '</div>',
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
	 * Convert content of fill in blanks shortcode to span elements display on Editor.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
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
