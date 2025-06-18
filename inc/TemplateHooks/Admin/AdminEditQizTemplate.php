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
	 * @since 4.2.8.6
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
	public static function render_edi_quiz( array $data ): stdClass {
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
			'wrap'          => '<div class="lp-list-questions">',
			'list-sections' => $html_questions,
			'section-clone' => $this->html_edit_question(),
			'wrap_end'      => '</div>',
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
	 * @return string
	 */
	public function html_edit_question(): string {
		$html_question = '';

		return $html_question;
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
