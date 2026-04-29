<?php
/**
 * Template hooks for Course Builder Popup.
 * Handles AJAX popup loading for lesson, quiz, and question builders.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use Exception;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\CourseBuilder\Lesson\BuilderEditLessonTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Question\BuilderEditQuestionTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Quiz\BuilderEditQuizTemplate;
use stdClass;

class BuilderPopupTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Allow callback for AJAX.
	 *
	 * @param array $callbacks
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = self::class . ':render_lesson_popup';
		$callbacks[] = self::class . ':render_quiz_popup';
		$callbacks[] = self::class . ':render_question_popup';

		return $callbacks;
	}

	/**
	 * Validate object-level permission before rendering popup content.
	 *
	 * @param string $item_type
	 * @param int    $item_id
	 *
	 * @throws Exception
	 */
	private static function ensure_popup_access( string $item_type, int $item_id ) {
		if ( $item_id > 0 ) {
			if ( ! CourseBuilderAccessPolicy::can_edit_item( $item_type, $item_id ) ) {
				throw new Exception( __( "Sorry, you don't have permission to access this content", 'learnpress' ) );
			}

			return;
		}

		if ( ! CourseBuilderAccessPolicy::can_create_item_type( $item_type ) ) {
			throw new Exception( __( "Sorry, you don't have permission to create this item", 'learnpress' ) );
		}
	}

	/**
	 * Get popup wrapper HTML structure.
	 */
	public function get_popup_wrapper( string $type, int $post_id, string $title, string $status = '' ): array {
		$status_html = '';
		if ( ! empty( $status ) ) {
			$status_html = sprintf( '<span class="%s-status %s">%s</span>', $type, esc_attr( $status ), esc_html( $status ) );
		}

		return [
			'overlay'            => '<div class="lp-builder-popup-overlay"></div>',
			'wrapper'            => sprintf( '<div class="lp-builder-popup lp-builder-popup--%s" data-%s-id="%d">', $type, $type, $post_id ),
			'header'             => '<div class="lp-builder-popup__header">',
			'header_left'        => '<div class="lp-builder-popup__header-left">',
			'title'              => sprintf( '<h3 class="lp-builder-popup__title">%s</h3>', esc_html( $title ) ),
			'status'             => $status_html,
			'header_left_end'    => '</div>',
			'header_actions'     => '<div class="lp-builder-popup__header-actions">',
			'resize_btn'         => '<button type="button" class="lp-builder-popup__resize" aria-label="' . esc_attr__( 'Toggle fullscreen', 'learnpress' ) . '" title="' . esc_attr__( 'Toggle fullscreen', 'learnpress' ) . '"><i class="lp-icon-expand"></i></button>',
			'close_btn'          => '<button type="button" class="lp-builder-popup__close" aria-label="' . esc_attr__( 'Close', 'learnpress' ) . '">&times;</button>',
			'header_actions_end' => '</div>',
			'header_end'         => '</div>',
			'body'               => '<div class="lp-builder-popup__body">',
		];
	}

	/**
	 * Get popup footer HTML structure with Trash and Update/Publish buttons.
	 * Same style as edit lesson/question/quiz pages.
	 */
	public function get_popup_footer( string $type, int $post_id, string $status = '' ): array {
		$btn_save_text = $status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );

		// Trash button - only show if item exists (post_id > 0)
		$btn_trash = '';
		// Save Draft button
		$btn_draft = '';
		$btn_save  = '';
		if ( $status !== 'trash' ) {
			$btn_draft = sprintf(
				'<button type="button" class="cb-button lp-button cb-button--secondary cb-btn-draft__%s lp-builder-popup__btn lp-builder-popup__btn--draft" data-confirm-unpublish="%s">%s</button>',
				$type,
				esc_attr__( 'Saving as draft will unpublish this item from the course. Are you sure?', 'learnpress' ),
				__( 'Save Draft', 'learnpress' )
			);

			$btn_save = sprintf(
				'<button type="button" class="cb-button lp-button cb-btn-update__%s lp-builder-popup__btn lp-builder-popup__btn--save" data-title-update="%s" data-title-publish="%s">%s</button>',
				$type,
				__( 'Update', 'learnpress' ),
				__( 'Publish', 'learnpress' ),
				$btn_save_text
			);

			if ( $post_id > 0 ) {
				$btn_trash = sprintf(
					'<button type="button" class="cb-button lp-button cb-btn-trash__%s lp-builder-popup__btn lp-builder-popup__btn--trash">%s</button>',
					$type,
					__( 'Move to trash', 'learnpress' )
				);
			}
		}

		return [
			'body_end'         => '</div>',
			'footer'           => '<div class="lp-builder-popup__footer">',
			'footer_left'      => '<div class="lp-builder-popup__footer-left">',
			'btn_trash'        => $btn_trash,
			'footer_left_end'  => '</div>',
			'footer_right'     => '<div class="lp-builder-popup__footer-right">',
			'btn_cancel'       => sprintf(
				'<button type="button" class="cb-button lp-builder-popup__btn lp-builder-popup__btn--cancel">%s</button>',
				__( 'Cancel', 'learnpress' )
			),
			'btn_draft'        => $btn_draft,
			'btn_save'         => $btn_save,
			'footer_right_end' => '</div>',
			'footer_end'       => '</div>',
			'wrapper_end'      => '</div>',
		];
	}

	/**
	 * Render Lesson Popup
	 */
	public static function render_lesson_popup( array $args = [] ): stdClass {
		$response  = new stdClass();
		$lesson_id = absint( $args['lesson_id'] ?? 0 );
		self::ensure_popup_access( LP_LESSON_CPT, $lesson_id );

		$lesson_model = $lesson_id ? LessonPostModel::find( $lesson_id, true ) : null;
		$title        = $lesson_model ? __( 'Edit Lesson', 'learnpress' ) : __( 'New Lesson', 'learnpress' );
		$status       = $lesson_model ? $lesson_model->post_status : '';

		$instance = self::instance();
		$content  = $instance->build_lesson_content( $lesson_id, $lesson_model );

		$html = array_merge(
			$instance->get_popup_wrapper( 'lesson', $lesson_id, $title, $status ),
			[ 'content' => $content ],
			$instance->get_popup_footer( 'lesson', $lesson_id, $status )
		);

		$response->content = Template::combine_components( $html );

		return $response;
	}

	/**
	 * Build lesson content
	 */
	private function build_lesson_content( int $lesson_id, $lesson_model ): string {
		if ( $lesson_model && $lesson_model->post_status === PostModel::STATUS_TRASH ) {
			$message = __(
				'You cannot edit this item because it is in the Trash. Please restore it and try again.',
				'learnpress'
			);

			return Template::print_message( $message, 'error', false );
		}

		$template          = BuilderEditLessonTemplate::instance();
		$lesson_context_id = $lesson_id > 0 ? $lesson_id : CourseBuilder::POST_NEW;
		$lesson_data       = [
			'item_id'     => $lesson_context_id,
			'lessonModel' => $lesson_model ?? false,
		];
		$overview_html     = $template->html_tab_overview( $lesson_data );
		$settings_html     = $template->html_tab_settings( $lesson_data );

		$sections = [
			'wrapper'           => sprintf( '<div class="cb-section__lesson-edit" data-lesson-id="%d">', $lesson_id ),
			'tabs'              => $this->build_tabs( 'lesson', [ 'overview', 'settings' ] ),
			'tab_content_start' => '<div class="lp-builder-popup__tab-content">',
			'overview'          => sprintf( '<div class="lp-builder-popup__tab-pane active" data-tab="overview">%s</div>', $overview_html ),
			'settings'          => sprintf( '<div class="lp-builder-popup__tab-pane" data-tab="settings">%s</div>', $settings_html ),
			'tab_content_end'   => '</div>',
			'wrapper_end'       => '</div>',
		];

		return Template::combine_components( $sections );
	}

	/**
	 * Render Quiz Popup
	 */
	public static function render_quiz_popup( array $args = [] ): stdClass {
		$response = new stdClass();
		$quiz_id  = absint( $args['quiz_id'] ?? 0 );
		self::ensure_popup_access( LP_QUIZ_CPT, $quiz_id );

		$quiz_model = $quiz_id ? QuizPostModel::find( $quiz_id, true ) : null;
		$title      = $quiz_model ? __( 'Edit Quiz', 'learnpress' ) : __( 'New Quiz', 'learnpress' );
		$status     = $quiz_model ? $quiz_model->post_status : '';

		$instance = self::instance();
		$content  = $instance->build_quiz_content( $quiz_id, $quiz_model );

		$html = array_merge(
			$instance->get_popup_wrapper( 'quiz', $quiz_id, $title, $status ),
			[ 'content' => $content ],
			$instance->get_popup_footer( 'quiz', $quiz_id, $status )
		);

		$response->content = Template::combine_components( $html );

		return $response;
	}

	/**
	 * Build quiz content
	 */
	private function build_quiz_content( int $quiz_id, $quiz_model = false ): string {
		$template        = BuilderEditQuizTemplate::instance();
		$quiz_context_id = $quiz_id > 0 ? $quiz_id : CourseBuilder::POST_NEW;
		$quiz_data       = [
			'item_id'   => $quiz_context_id,
			'quizModel' => $quiz_model,
		];
		$overview_html   = $template->html_tab_overview( $quiz_data );
		$question_html   = $template->html_tab_question( $quiz_data );
		$settings_html   = $template->html_tab_settings( $quiz_data );

		$sections = [
			'wrapper'           => sprintf( '<div class="cb-section__quiz-edit" data-quiz-id="%d">', $quiz_id ),
			'tabs'              => $this->build_tabs( 'quiz', [ 'overview', 'questions', 'settings' ] ),
			'tab_content_start' => '<div class="lp-builder-popup__tab-content">',
			'overview'          => sprintf( '<div class="lp-builder-popup__tab-pane active" data-tab="overview">%s</div>', $overview_html ),
			'questions'         => sprintf( '<div class="lp-builder-popup__tab-pane" data-tab="questions" data-require-js="quiz-questions">%s</div>', $question_html ),
			'settings'          => sprintf( '<div class="lp-builder-popup__tab-pane" data-tab="settings">%s</div>', $settings_html ),
			'tab_content_end'   => '</div>',
			'wrapper_end'       => '</div>',
		];

		return Template::combine_components( $sections );
	}

	/**
	 * Render Question Popup
	 */
	public static function render_question_popup( array $args = [] ): stdClass {
		$response    = new stdClass();
		$question_id = absint( $args['question_id'] ?? 0 );
		self::ensure_popup_access( LP_QUESTION_CPT, $question_id );

		$question_model = $question_id ? QuestionPostModel::find( $question_id, true ) : null;
		$title          = $question_model ? __( 'Edit Question', 'learnpress' ) : __( 'New Question', 'learnpress' );
		$status         = $question_model ? $question_model->post_status : '';

		$instance = self::instance();
		$content  = $instance->build_question_content( $question_id, $question_model );

		$html = array_merge(
			$instance->get_popup_wrapper( 'question', $question_id, $title, $status ),
			[ 'content' => $content ],
			$instance->get_popup_footer( 'question', $question_id, $status )
		);

		$response->content = Template::combine_components( $html );

		return $response;
	}

	/**
	 * Build question content
	 */
	private function build_question_content( int $question_id, $question_model = false ): string {
		$template            = BuilderEditQuestionTemplate::instance();
		$question_context_id = $question_id > 0 ? $question_id : CourseBuilder::POST_NEW;
		$question_data       = [
			'item_id'       => $question_context_id,
			'questionModel' => $question_model,
		];
		$overview_html       = $template->html_tab_overview( $question_data );
		$settings_html       = $template->html_tab_settings( $question_data );

		$sections = [
			'wrapper'           => sprintf( '<div class="cb-section__question-edit" data-question-id="%d">', $question_id ),
			'tabs'              => $this->build_tabs( 'question', [ 'overview', 'settings' ] ),
			'tab_content_start' => '<div class="lp-builder-popup__tab-content">',
			'overview'          => sprintf( '<div class="lp-builder-popup__tab-pane active" data-tab="overview">%s</div>', $overview_html ),
			'settings'          => sprintf( '<div class="lp-builder-popup__tab-pane" data-tab="settings">%s</div>', $settings_html ),
			'tab_content_end'   => '</div>',
			'wrapper_end'       => '</div>',
		];

		return Template::combine_components( $sections );
	}

	/**
	 * Build tabs navigation.
	 */
	public function build_tabs( string $type, array $tabs ): string {
		$tab_labels = apply_filters(
			"learn-press/course-builder/popup/{$type}/tab-labels",
			[
				'overview'  => __( 'Overview', 'learnpress' ),
				'settings'  => __( 'Settings', 'learnpress' ),
				'questions' => __( 'Questions', 'learnpress' ),
				'answers'   => __( 'Answers', 'learnpress' ),
			],
			$type,
			$tabs
		);

		$tabs_html = '<ul class="lp-builder-popup__tabs">';
		foreach ( $tabs as $index => $tab ) {
			$active     = $index === 0 ? ' active' : '';
			$label      = $tab_labels[ $tab ] ?? ucfirst( $tab );
			$tabs_html .= sprintf(
				'<li class="lp-builder-popup__tab%s" data-tab="%s">%s</li>',
				$active,
				esc_attr( $tab ),
				esc_html( $label )
			);
		}
		$tabs_html .= '</ul>';

		return $tabs_html;
	}
}
