<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;

/**
 * Template Admin Edit Quiz.
 *
 * @since 4.2.8.8
 * @version 1.0.0
 */
class AdminEditQizTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/admin/edit-quiz/layout', [ $this, 'edit_quiz_layout' ] );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Layout for edit course curriculum.
	 *
	 * @since 4.2.8.8
	 * @version 1.0.0
	 */
	public function edit_quiz_layout( QuizPostModel $quizPostModel ) {
		wp_enqueue_style( 'lp-edit-quiz' );
		wp_enqueue_script( 'lp-edit-quiz' );

		$args      = [
			'id_url'  => 'edit-quiz',
			'quiz_id' => $quizPostModel->ID,
		];
		$call_back = array(
			'class'  => self::class,
			'method' => 'render_edit_quiz',
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
		$callbacks[] = get_class( $this ) . ':render_edit_quiz';
		$callbacks[] = get_class( $this ) . ':render_list_items_not_assign';

		return $callbacks;
	}

	/**
	 * Render edit course curriculum html.
	 *
	 * @throws Exception
	 */
	public static function render_edit_quiz( array $data ): stdClass {
		$quiz_id       = $data['quiz_id'] ?? 0;
		$quizPostModel = QuizPostModel::find( $quiz_id, true );
		if ( ! $quizPostModel ) {
			throw new Exception( __( 'Quiz not found', 'learnpress' ) );
		}

		$content          = new stdClass();
		$content->content = self::instance()->html_edit( $quizPostModel );

		return $content;
	}

	/**
	 * Render html for edit quiz.
	 *
	 * @param QuizPostModel $quizPostModel
	 *
	 * @return string
	 * @throws Exception
	 */
	public function html_edit( QuizPostModel $quizPostModel ): string {
		$html_questions = '';

		// Get sections items
		$question_ids    = $quizPostModel->get_question_ids();
		$count_questions = count( $question_ids );

		foreach ( $question_ids as $question_id ) {
			$questionPostModel = QuestionPostModel::find( $question_id, true );
			$html_questions   .= $this->html_edit_question( $questionPostModel );
		}

		$section_questions = [
			'wrap'           => '<div class="lp-list-questions">',
			'list-sections'  => $html_questions,
			'question-clone' => $this->html_edit_question(),
			'wrap_end'       => '</div>',
		];

		$section = [
			'wrap'             => '<div id="admin-editor-lp_quiz">',
			'heading'          => '<div class="heading">',
			'h4'               => sprintf(
				'<h4>%s</h4>',
				__( 'Details', 'learnpress' )
			),
			'count-questions'  => sprintf(
				'<div class="total-items" data-count="%s">%s</div>',
				$count_questions,
				sprintf(
					__( '<span class="count">%1$s</span> %2$s', 'learnpress' ),
					$count_questions,
					sprintf(
						'<span class="one">%s</span><span class="plural">%s</span>',
						__( 'Question', 'learnpress' ),
						__( 'Questions', 'learnpress' )
					)
				)
			),
			'quiz-toggle'      =>
				'<div class="qiz-toggle-all lp-collapse">
					<span class="lp-icon-angle-down"></span>
					<span class="lp-icon-angle-up"></span>
				</div>',
			'heading_end'      => '</div>',
			'questions'        => Template::combine_components( $section_questions ),
			'add_new_question' => $this->html_add_new_question(),
			'select_items'     => $this->html_popup_items_to_select_clone( $quizPostModel ),
			'wrap_end'         => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for edit question.
	 *
	 * @param $questionPostModel
	 *
	 * @return string
	 */
	public function html_edit_question( $questionPostModel = null ): string {
		$is_clone       = false;
		$question_id    = 0;
		$question_title = '';

		if ( $questionPostModel instanceof QuestionPostModel ) {
			$question_id    = $questionPostModel->ID;
			$question_title = $questionPostModel->post_title;
		} else {
			$is_clone = true;
		}

		$section_edit_details = [
			'wrap'        => '<div class="question-edit-details">',
			'point'       => $this->html_edit_mark( $questionPostModel ),
			'hint'        => $this->html_edit_hint( $questionPostModel ),
			'explanation' => $this->html_edit_explanation_hint( $questionPostModel ),
			'wrap_end'    => '</div>',
		];

		$section_edit_main = [
			'wrap'             => '<div class="question-edit-main">',
			'description'      => $this->html_edit_question_description( $questionPostModel ),
			'question-by-type' => $this->html_edit_question_by_type( $questionPostModel ),
			'details'          => Template::combine_components( $section_edit_details ),
			'wrap_end'         => '</div>',
		];

		$section = [
			'wrap'       => sprintf(
				'<div data-question-id="%s" class="lp-question-item lp-collapse %s">',
				$question_id,
				$is_clone ? 'clone lp-hidden' : ''
			),
			'head'       => '<div class="section-head">',
			'drag'       => sprintf(
				'<span class="drag lp-icon-drag" title="%s"></span>',
				__( 'Drag to reorder section', 'learnpress' )
			),
			'loading'    => '<span class="lp-icon-spinner"></span>',
			'title'      => $this->html_input_question_title( $question_title ),
			'btn-edit'   => sprintf(
				'<span class="lp-btn-edit-question-title lp-icon-edit" title="%s"></span>',
				__( 'Edit question title', 'learnpress' )
			),
			'btn-delete' => sprintf(
				'<button type="button" class="lp-btn-delete-question button" data-title="%s" data-content="%s">%s</button>',
				__( 'Are you sure?', 'learnpress' ),
				__( 'This question will be deleted. The question will no longer be assigned to this quiz, but will not be permanently deleted.', 'learnpress' ),
				__( 'Delete question', 'learnpress' )
			),
			'btn-update' => sprintf(
				'<button type="button" class="lp-btn-update-question-title button">%s</button>',
				__( 'Update' )
			),
			'btn-cancel' => sprintf(
				'<button type="button" class="lp-btn-cancel-update-question-title button">%s</button>',
				__( 'Cancel' )
			),
			'toggle'     => '<div class="question-toggle"><span class="lp-icon-angle-down"></span><span class="lp-icon-angle-up"></span></div>',
			'head_end'   => '</div>',
			'edit_main'  => Template::combine_components( $section_edit_main ),
			'wrap_end'   => '</div>',
		];

		return Template::combine_components( $section );
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
				$editor_id,
				'lp-editor-tinymce'
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_edit_explanation_hint( $questionPostModel = null ): string {
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
				$editor_id,
				'lp-editor-tinymce'
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

		ob_start();
		wp_editor(
			$question_description,
			$editor_id,
			[
				'media_buttons' => true,
				'dfw'           => false,
				'editor_class'  => 'lp-editor-tinymce',
				'editor_height' => 200,
			]
		);
		$html_tinymce = ob_get_clean();

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
			'tinymce'  => $html_tinymce,
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
	 * HTML for popup items to select clone.
	 *
	 * @param QuizPostModel $quizPostModel
	 *
	 * @return string
	 */
	public function html_popup_items_to_select_clone( QuizPostModel $quizPostModel ): string {
		$items = $quizPostModel->get_question_ids();
		if ( empty( $items ) ) {
			return '';
		}

		$html_items = '';
		foreach ( $items as $item_id ) {
			$questionPostModel = QuestionPostModel::find( $item_id, true );
			if ( ! $questionPostModel ) {
				continue;
			}
			$html_items .= sprintf(
				'<li class="lp-item lp-item-question" data-id="%s">%s</li>',
				$item_id,
				$questionPostModel->post_title
			);
		}

		return sprintf(
			'<div class="lp-popup-items-to-select-clone">
				<ul class="lp-list-items">%s</ul>
			</div>',
			$html_items
		);
	}
}
