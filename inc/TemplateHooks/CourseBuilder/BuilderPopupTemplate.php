<?php
/**
 * Template hooks for Course Builder Popup.
 * Handles AJAX popup loading for lesson, quiz, and question builders.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\TemplateAJAX;
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
	 * Helper to setup context for CourseBuilder::get_post_id()
	 *
	 * @param int $id
	 */
	private function setup_request_context( int $id ) {
		$post_val            = ( $id > 0 ) ? $id : 'post-new';
		$_REQUEST['post']    = $post_val;
		$_REQUEST['post_id'] = $post_val;
		$_GET['post']        = $post_val;
		$_POST['post']       = $post_val;
	}

	/**
	 * Helper to capture output from existing template functions.
	 *
	 * @param callable $callback
	 * @return string
	 */
	private function capture_output( callable $callback ): string {
		ob_start();
		try {
			call_user_func( $callback );
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
		return ob_get_clean();
	}

	/**
	 * Get popup wrapper HTML structure.
	 * Two-row header:
	 * - Row 1: "Edit Lesson/Quiz/Question" + resize + close buttons
	 * - Row 2: Dynamic Title + Status badge + Dropdown Action buttons
	 */
	private function get_popup_wrapper( string $type, int $post_id, string $title, string $status = '' ): array {
		// Status badge HTML
		$status_html = '';
		if ( ! empty( $status ) ) {
			$status_html = sprintf( '<span class="popup-status %s">%s</span>', esc_attr( $status ), esc_html( $status ) );
		}

		// Determine button text based on status
		$is_published  = $status === 'publish';
		$btn_save_text = $is_published ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );

		// Trash dropdown item - only show if item exists (post_id > 0)
		$trash_item = '';
		if ( $post_id > 0 ) {
			$trash_item = sprintf(
				'<div class="cb-dropdown-item cb-btn-trash__%s cb-btn-danger">
					<span class="dashicons dashicons-trash"></span>
					%s
				</div>',
				$type,
				__( 'Move to Trash', 'learnpress' )
			);
		}

		// Build dropdown HTML
		$dropdown_html = sprintf(
			'<div class="cb-header-actions-dropdown">
				<div class="cb-btn-update cb-btn-update__%s cb-btn-primary" data-title-update="%s" data-title-publish="%s">%s</div>
				<button type="button" class="cb-btn-dropdown-toggle" aria-expanded="false" aria-haspopup="true">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
				<div class="cb-dropdown-menu">
					%s
				</div>
			</div>',
			$type,
			esc_attr__( 'Update', 'learnpress' ),
			esc_attr__( 'Publish', 'learnpress' ),
			$btn_save_text,
			$trash_item
		);

		return [
			'overlay'               => '<div class="lp-builder-popup-overlay"></div>',
			'wrapper'               => sprintf( '<div class="lp-builder-popup lp-builder-popup--%s" data-%s-id="%d">', $type, $type, $post_id ),
			// Row 1: Original header with "Edit Lesson/Quiz/Question" + resize + close
			'header'                => '<div class="lp-builder-popup__header">',
			'header_left'           => '<div class="lp-builder-popup__header-left">',
			'header_title'          => sprintf( '<span class="lp-builder-popup__header-title">%s</span>', esc_html( $title ) ),
			'header_left_end'       => '</div>',
			'header_actions'        => '<div class="lp-builder-popup__header-actions">',
			'resize_btn'            => '<button type="button" class="lp-builder-popup__resize" aria-label="' . esc_attr__( 'Toggle fullscreen', 'learnpress' ) . '" title="' . esc_attr__( 'Toggle fullscreen', 'learnpress' ) . '"><i class="lp-icon-expand"></i></button>',
			'close_btn'             => '<button type="button" class="lp-builder-popup__close" aria-label="' . esc_attr__( 'Close', 'learnpress' ) . '">&times;</button>',
			'header_actions_end'    => '</div>',
			'header_end'            => '</div>',
			// Row 2: New subheader with dynamic title + status + dropdown actions
			'subheader'             => '<div class="lp-builder-popup__subheader">',
			'subheader_left'        => '<div class="lp-builder-popup__subheader-left">',
			'title'                 => '<h3 class="lp-builder-popup__title"></h3>',
			'status'                => $status_html,
			'subheader_left_end'    => '</div>',
			'subheader_actions'     => '<div class="lp-builder-popup__subheader-actions">',
			'dropdown'              => $dropdown_html,
			'subheader_actions_end' => '</div>',
			'subheader_end'         => '</div>',
			'body'                  => '<div class="lp-builder-popup__body">',
		];
	}

	/**
	 * Get popup footer HTML structure with Trash and Update/Publish buttons.
	 * Same style as edit lesson/question/quiz pages.
	 */
	private function get_popup_footer( string $type, int $post_id, string $status = '' ): array {
		$btn_save_text = $status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );

		// Trash button - only show if item exists (post_id > 0)
		$btn_trash = '';
		if ( $post_id > 0 ) {
			$btn_trash = sprintf(
				'<button type="button" class="cb-button cb-btn-trash__%s lp-builder-popup__btn lp-builder-popup__btn--trash">%s</button>',
				$type,
				__( 'Trash', 'learnpress' )
			);
		}

		return [
			'body_end'         => '</div>',
			'footer'           => '<div class="lp-builder-popup__footer">',
			'footer_left'      => '<div class="lp-builder-popup__footer-left">',
			'btn_cancel'       => sprintf(
				'<button type="button" class="cb-button lp-builder-popup__btn lp-builder-popup__btn--cancel">%s</button>',
				__( 'Cancel', 'learnpress' )
			),
			'footer_left_end'  => '</div>',
			'footer_right'     => '<div class="lp-builder-popup__footer-right">',
			'btn_trash'        => $btn_trash,
			'btn_save'         => sprintf(
				'<button type="button" class="cb-button cb-btn-update__%s lp-builder-popup__btn lp-builder-popup__btn--save" data-title-update="%s" data-title-publish="%s">%s</button>',
				$type,
				__( 'Update', 'learnpress' ),
				__( 'Publish', 'learnpress' ),
				$btn_save_text
			),
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

		// Setup context
		self::instance()->setup_request_context( $lesson_id );

		$lesson_model = $lesson_id ? LessonPostModel::find( $lesson_id, true ) : null;
		$title        = $lesson_model ? __( 'Edit Lesson', 'learnpress' ) : __( 'New Lesson', 'learnpress' );
		$status       = $lesson_model ? $lesson_model->post_status : '';

		$instance = self::instance();
		$content  = $instance->build_lesson_content( $lesson_id );

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
	private function build_lesson_content( int $lesson_id ): string {
		$template = BuilderEditLessonTemplate::instance();

		$overview_html = $this->capture_output( [ $template, 'section_overview' ] );
		$settings_html = $this->capture_output( [ $template, 'section_settings' ] );

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

		// Setup context
		self::instance()->setup_request_context( $quiz_id );

		$quiz_model = $quiz_id ? QuizPostModel::find( $quiz_id, true ) : null;
		$title      = $quiz_model ? __( 'Edit Quiz', 'learnpress' ) : __( 'New Quiz', 'learnpress' );
		$status     = $quiz_model ? $quiz_model->post_status : '';

		$instance = self::instance();
		$content  = $instance->build_quiz_content( $quiz_id );

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
	private function build_quiz_content( int $quiz_id ): string {
		$template = BuilderEditQuizTemplate::instance();

		$overview_html = $this->capture_output( [ $template, 'section_overview' ] );
		$question_html = $this->capture_output( [ $template, 'section_question' ] );
		$settings_html = $this->capture_output( [ $template, 'section_settings' ] );

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

		// Setup context
		self::instance()->setup_request_context( $question_id );

		$question_model = $question_id ? QuestionPostModel::find( $question_id, true ) : null;
		$title          = $question_model ? __( 'Edit Question', 'learnpress' ) : __( 'New Question', 'learnpress' );
		$status         = $question_model ? $question_model->post_status : '';

		$instance = self::instance();
		$content  = $instance->build_question_content( $question_id );

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
	private function build_question_content( int $question_id ): string {
		$template = BuilderEditQuestionTemplate::instance();

		$overview_html = $this->capture_output( [ $template, 'section_overview' ] );
		$settings_html = $this->capture_output( [ $template, 'section_settings' ] );

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
	private function build_tabs( string $type, array $tabs ): string {
		$tab_labels = [
			'overview'  => __( 'Overview', 'learnpress' ),
			'settings'  => __( 'Settings', 'learnpress' ),
			'questions' => __( 'Questions', 'learnpress' ),
			'answers'   => __( 'Answers', 'learnpress' ),
		];

		apply_filters( "learn-press/course-builder/popup/{$type}", $type, 10, 1 );
		apply_filters( "learn-press/course-builder/popup/{$type}/tabs", $tabs, 10, 1 );
		apply_filters( "learn-press/course-builder/popup/{$type}/tab-labels", $tab_labels, 10, 1 );

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
