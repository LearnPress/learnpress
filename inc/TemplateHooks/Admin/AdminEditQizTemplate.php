<?php

namespace LearnPress\TemplateHooks\Admin;

use Exception;
use LearnPress\Databases\DataBase;
use LearnPress\Filters\PostFilter;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Database;
use LP_Post_DB;
use LP_Post_Type_Filter;
use stdClass;

/**
 * Template Admin Edit Quiz.
 *
 * @since 4.2.9
 * @version 1.0.0
 */
class AdminEditQizTemplate {
	use Singleton;

	/**
	 * @var QuizPostModel
	 */
	public $quizPostModel;

	public function init() {
		add_action( 'learn-press/admin/edit-quiz/layout', [ $this, 'edit_quiz_layout' ] );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_filter(
			'wp_default_editor',
			function ( $r ) {
				global $post;
				if ( ! $post ) {
					return $r;
				}

				if ( $post->post_type !== LP_QUIZ_CPT ) {
					return $r;
				}

				return 'html';
			},
			1000
		);
	}

	/**
	 * Layout for edit course curriculum.
	 *
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function edit_quiz_layout( QuizPostModel $quizPostModel ) {
		wp_enqueue_style( 'lp-edit-quiz' );
		wp_enqueue_script( 'lp-edit-quiz' );

		$args = [
			'id_url'  => 'edit-quiz',
			'quiz_id' => $quizPostModel->ID,
		];
		/**
		 * @uses self::render_edit_quiz
		 */
		$call_back = array(
			'class'  => self::class,
			'method' => 'render_edit_quiz',
		);

		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
	}

	/**
	 * Allow callback for AJAX.
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

		// Check permission
		$quizPostModel->check_capabilities_create_item_course();

		self::instance()->quizPostModel = $quizPostModel;

		$content          = new stdClass();
		$content->content = self::instance()->html_edit_quiz( $quizPostModel );

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
	public function html_edit_quiz( QuizPostModel $quizPostModel ): string {
		$html_questions = '';

		// Get sections items
		$question_ids    = $quizPostModel->get_question_ids();
		$count_questions = count( $question_ids );

		foreach ( $question_ids as $question_id ) {
			$questionPostModel = QuestionPostModel::find( $question_id, true );
			$html_questions    .= $this->html_edit_question( $questionPostModel );
		}

		$section_questions = [
			'wrap'           => '<div class="lp-edit-list-questions">',
			'list-sections'  => $html_questions,
			'question-clone' => $this->html_edit_question(),
			'wrap_end'       => '</div>',
		];

		$section = [
			'wrap'             => '<div class="lp-edit-quiz-wrap">',
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
				'<div class="lp-question-toggle-all lp-collapse">
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
		$html_edit_main = '';

		if ( $questionPostModel instanceof QuestionPostModel ) {
			$question_id    = $questionPostModel->ID;
			$question_title = $questionPostModel->post_title;

			$section_edit_details = [
				'wrap'         => '<div class="question-edit-details lp-section-toggle">',
				'header'       => sprintf(
					'<div class="lp-question-data-edit-header lp-trigger-toggle">
					<label>%s</label>
					<div class="lp-tinymce-toggle"><span class="lp-icon-angle-down"></span><span class="lp-icon-angle-up"></span></div>
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
				'wrap'        => sprintf(
					'<div class="lp-question-edit-main" data-question-id="%d">',
					$question_id
				),
				'left'        => '<div class="lp-question-edit-left">',
				'description' => AdminEditQuestionTemplate::instance()->html_edit_question_description( $questionPostModel ),
				'answers'     => AdminEditQuestionTemplate::instance()->html_edit_question_by_type( $questionPostModel ),
				'left_end'    => '</div>',
				'details'     => Template::combine_components( $section_edit_details ),
				'wrap_end'    => '</div>',
			];

			$html_edit_main = Template::combine_components( $section_edit_main );
		} else {
			$is_clone = true;
		}

		$section = [
			'wrap'       => sprintf(
				'<div data-question-id="%s"
					class="lp-question-item lp-section-toggle lp-collapse %s"
					data-question-type="%s">',
				$question_id,
				$is_clone ? 'clone lp-hidden' : '',
				$is_clone ? '' : $questionPostModel->get_type()
			),
			'head'       => '<div class="lp-question-head">',
			'drag'       => sprintf(
				'<span class="drag lp-icon-drag" title="%s"></span>',
				__( 'Drag to reorder section', 'learnpress' )
			),
			'loading'    => '<span class="lp-icon-spinner"></span>',
			'title'      => AdminEditQuestionTemplate::instance()->html_input_question_title( $question_title ),
			'btn-update' => sprintf(
				'<button type="button" class="lp-btn-update-question-title button">%s</button>',
				__( 'Update' )
			),
			'btn-cancel' => sprintf(
				'<button type="button" class="lp-btn-cancel-update-question-title button">%s</button>',
				__( 'Cancel' )
			),
			'type'       => sprintf(
				'<span class="lp-question-type-label">%s</span>',
				$questionPostModel instanceof QuestionPostModel ? $questionPostModel->get_type_label() : ''
			),
			'btn-edit'   => sprintf(
				'<a class="lp-btn-edit-question-title lp-icon-edit-square" title="%s" href="%s" target="_blank"></a>',
				__( 'Edit question detail', 'learnpress' ),
				$questionPostModel instanceof QuestionPostModel ? $questionPostModel->get_edit_link() : '#'
			),
			'btn-delete' => sprintf(
				'<span class="lp-btn-remove-question lp-icon-trash-o" title="%s" data-title="%s" data-content="%s"></span>',
				__( 'Remove question', 'learnpress' ),
				__( 'Are you sure?', 'learnpress' ),
				__( 'This question will be removed from this quiz. The question will no longer be assigned to this quiz, but will not be permanently deleted.', 'learnpress' )
			),
			'toggle'     => '<div class="lp-question-toggle"><span class="lp-icon-angle-down"></span><span class="lp-icon-angle-up"></span></div>',
			'head_end'   => '</div>',
			'edit_main'  => $html_edit_main,
			'wrap_end'   => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for add new question.
	 *
	 * @return string
	 */
	public function html_add_new_question(): string {
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
		$html_question_types = Template::instance()->nest_elements(
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

		$section = [
			'wrap'             => '<div class="add-new-question">',
			'icon'             => '<span class="lp-icon-plus"></span>',
			'input'            => sprintf(
				'<input class="lp-question-title-new-input"
					name="lp-question-title-new-input"
					type="text"
					title="%1$s"
					placeholder="%1$s"
					data-mess-empty-title="%2$s"
					data-send="%3$s">',
				esc_attr__( 'Create a new question', 'learnpress' ),
				esc_attr__( 'Question title is required', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'id_url'  => 'create-question-add-to-quiz',
						'quiz_id' => $this->quizPostModel->get_id(),
						'action'  => 'create_question_add_to_quiz',
					]
				),
			),
			'types'            => $html_question_types,
			'button'           => sprintf(
				'<button type="button" class="lp-btn-add-question lp-btn-edit-primary button" title="%s">%s</button>',
				esc_attr__( 'Enter title question and choice type', 'learnpress' ),
				__( 'Add Question', 'learnpress' )
			),
			'btn-select-items' => sprintf(
				'<button type="button"
					class="button lp-btn-show-popup-items-to-select"
					data-template="#lp-tmpl-select-question-bank">%s</button>',
				__( 'Question Bank', 'learnpress' )
			),
			'wrap_end'         => '</div>',
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
		$tabs = [
			LP_QUESTION_CPT => __( 'Questions', 'learnpress' ),
		];

		/**
		 * @uses self::render_list_items_not_assign
		 */
		$html_items = TemplateAJAX::load_content_via_ajax(
			[
				'id_url'             => 'list-questions-not-assign',
				'enableScrollToView' => false,
				'quiz_id'            => $quizPostModel->ID,
				'paged'              => 1,
			],
			[
				'class'  => self::class,
				'method' => 'render_list_items_not_assign',
			]
		);

		$section = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-select-question-bank">',
			'popup'                    => AdminTemplate::html_popup_items_to_select_clone( $tabs, $html_items ),
			'wrap-script-template-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Render list items not assign to any quiz.
	 *
	 * @throws Exception
	 *
	 * @since 4.2.8.7
	 * @version 1.0.1
	 */
	public static function render_list_items_not_assign( $data ): stdClass {
		$content                = new stdClass();
		$quiz_id                = $data['quiz_id'] ?? 0;
		$item_selecting         = $data['item_selecting'] ?? [];
		$search_title           = $data['search_title'] ?? '';
		$paged                  = intval( $data['paged'] ?? 1 );
		$item_selecting_compare = new stdClass();

		$quizPostModel = QuizPostModel::find( $quiz_id, true );
		if ( ! $quizPostModel ) {
			throw new Exception( __( 'Quiz not found', 'learnpress' ) );
		}

		$lp_posts_db         = LP_Post_DB::getInstance();
		$filter              = new PostFilter();
		$filter->only_fields = [
			'DISTINCT(p.ID) AS ID',
			'p.post_title',
			'p.post_type',
		];
		$filter->post_type   = LP_QUESTION_CPT;
		$filter->post_status = [ 'publish' ];
		$filter->order_by    = 'p.ID';
		$filter->page        = $paged;

		if ( ! empty( $search_title ) ) {
			$filter->post_title = $search_title;
		}

		// Old logic: Get all questions not assigned to any quiz.
		// New logic: Get all questions not assigned to the quiz.
		$filter->where[] = $lp_posts_db->wpdb->prepare(
			"AND p.ID NOT IN ( SELECT question_id FROM {$lp_posts_db->tb_lp_quiz_questions} WHERE quiz_id = %d )",
			$quizPostModel->ID
		);


		$total_rows  = 0;
		$posts       = $lp_posts_db->get_posts( $filter, $total_rows );
		$total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );

		$html_lis = '';
		if ( empty( $posts ) ) {
			$html_lis = sprintf( '<li>%s</li>', __( 'No items found', 'learnpress' ) );
		} else {
			if ( ! empty( $item_selecting ) ) {
				foreach ( $item_selecting as $item ) {
					if ( ! isset( $item['id'] ) || ! isset( $item['type'] ) ) {
						continue;
					}

					$item_selecting_compare->{$item['id']} = new stdClass();
				}
			}

			foreach ( $posts as $post ) {
				/**
				 * @var $questionPostModel QuestionPostModel
				 */
				$questionPostModel = QuestionPostModel::find( $post->ID, true );
				if ( ! $questionPostModel ) {
					continue;
				}

				$checked = '';
				if ( isset( $item_selecting_compare->{$post->ID} ) ) {
					$checked = ' checked="checked"';
				}

				$title_display = sprintf(
					'<span class="title">%s<strong>(#%d - %s)</strong></span>',
					$post->post_title,
					$post->ID,
					$questionPostModel->get_type_label()
				);

				$html_lis .= sprintf(
					'<li class="lp-select-item">%s%s</li>',
					sprintf(
						'<input name="lp-select-item"
							data-id="%d" data-type-label="%s"
							data-type="%s"
							data-title="%s" %s data-edit-link="%s"
							data-title-selected="%s"
							type="checkbox" />',
						esc_attr( $post->ID ?? 0 ),
						esc_attr( $questionPostModel->get_type_label() ?? '' ),
						esc_attr( $questionPostModel->get_type() ?? '' ),
						esc_attr( $title_display ), // For JS display on list selected.
						esc_attr( $checked ),
						$questionPostModel->get_edit_link(),
						esc_attr( $questionPostModel->get_the_title() ?? '' )
					),
					$title_display
				);
			}
		}

		$section = [
			'ul'         => '<ul class="list-items">',
			'items'      => $html_lis,
			'ul_end'     => '</ul>',
			'pagination' => Template::instance()->html_pagination(
				[
					'total_pages' => $total_pages,
					'paged'       => $paged,
				]
			),
		];

		$content->content = Template::combine_components( $section );

		return $content;
	}
}
