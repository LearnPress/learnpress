<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\TemplateHooks\Admin\AdminEditQuestionTemplate;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LP_Question_CURD;

class BuilderEditQuestionTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'learn-press/course-builder/questions/overview/layout', [ $this, 'section_overview' ] );
		add_action( 'learn-press/course-builder/questions/settings/layout', [ $this, 'section_settings' ] );
	}

	/**
	 * Allow callback for AJAX.
	 * @use self::render_edit_course_curriculum
	 * @use self::render_html
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = AdminEditCurriculumTemplate::class . ':render_edit_course_curriculum';

		return $callbacks;
	}


	public function section_overview() {
		wp_enqueue_script( 'lp-course-builder' );
		$question_id = CourseBuilder::get_post_id();
		$question_model = ''; 

		if ( $question_id === 'post-new' ) {
			$question_model = '';
		}

		if ( absint( $question_id ) ) {
			$question_model = QuestionPostModel::find( $question_id, true );
			if ( empty( $question_model ) ) {
				return '';
			}
		}

		$html_header     = $this->header_section( $question_model );
		$html_assigned   = $this->assigned_quiz( $question_model );
		$html_edit_title = $this->edit_title( $question_model );
		$html_edit_desc  = $this->edit_desc( $question_model );
		$section         = [
			'wrapper'                    => sprintf( '<div class="cb-section__question-edit" data-question-id="%s">', $question_id ),
			'header'                     => $html_header,
			'wrapper_title_assigned'     => sprintf( '<div class="cb-section__question-title-assigned">' ),
			'edit_title'                 => $html_edit_title,
			'assigned_quiz'              => $html_assigned,
			'wrapper_title_assigned_end' => sprintf( '</div>' ),
			'edit_desc'                  => $html_edit_desc,
			'wrapper_end'                => '</div>',
		];

		echo Template::combine_components( $section );
	}

	public function header_section( $question_model ) {
		$status     = ! empty( $question_model ) ? $question_model->post_status : '';
		$btn_update = sprintf( '<div class="cb-button cb-btn-update__question" data-title-update="%s" data-title-publish="%s">%s</div>', __( 'Update', 'learnpress' ), __( 'Publish', 'learnpress' ), $status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' ) );
		$btn_trash  = ! empty( $question_model ) ? sprintf( '<div class="cb-button cb-btn-trash__question">%s</div>', __( 'Trash', 'learnpress' ) ) : '';
		$header     = [
			'wrapper'          => '<div class="cb-section__header">',
			'wrapper_left'     => '<div class="cb-section__header-left">',
			'question_status'  => ! empty( $status ) ? sprintf( '<span class="question-status %1$s">%1$s</span>', $status ) : '',
			'wrapper_left_end' => '</div>',
			'action_wrapper'   => '<div class="cb-section__header-action">',
			'btn_update'       => $btn_update,
			'btn_trash'        => $btn_trash,
			'action_end'       => '</div>',
			'wrapper_end'      => '</div>',
		];
		return Template::combine_components( $header );
	}

	public function assigned_quiz( $question_model ) {
		$assign_question = ! empty( $question_model ) ? $this->get_assigned_question( $question_model->get_id() ) : '';
		$html_quizzes    = '';
		$assigned        = sprintf( '<span class="question-not-assigned">%s</span>', __( 'Not assigned yet', 'learnpress' ) );
		if ( ! empty( $assign_question ) ) {
			$quizzes = is_array( $assign_question ) && isset( $assign_question['id'] )
				? array( $assign_question )
				: $assign_question;

			$quiz_htmls = array();
			foreach ( $quizzes as $quiz ) {
				$quiz_id    = $quiz['id'] ?? 0;
				$quiz_title = $quiz['title'] ?? '';

				if ( $quiz_id && $quiz_title ) {
					$quiz_link    = BuilderTabQuizTemplate::instance()->get_link_edit( $quiz_id );
					$quiz_htmls[] = sprintf(
						'<span>%s</span>',
						esc_html( $quiz_title )
					);
				}
			}

			if ( ! empty( $quiz_htmls ) ) {
				$assigned = implode( ', ', $quiz_htmls );
			}
		}

		$html_quizzes = sprintf(
			'<div class="question-assigned-quizzes"><span class="label">%s</span> %s</div>',
			__( 'Assigned', 'learnpress' ),
			$assigned
		);

		return $html_quizzes;
	}


	public function edit_title( $question_model ) {
		$title = ! empty( $question_model ) ? $question_model->get_the_title() : '';
		$edit  = [
			'wrapper'     => '<div class="cb-question-edit-title">',
			'label'       => sprintf( '<label for="title" class="cb-question-edit-title__label">%s</label>', __( 'Title', 'learnpress' ) ),
			'input'       => sprintf( '<input type="text" name="question_title" size="30" value="%s" id="title" class="cb-question-edit-title__input">', $title ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_desc( $question_model ) {
		$desc            = ! empty( $question_model ) ? $question_model->get_the_content() : '';
		$editor_id       = 'question_description_editor';
		$editor_settings = array(
			'textarea_name' => 'question_description',
			'textarea_rows' => 10,
			'teeny'         => false,
			'media_buttons' => true,
			'tinymce'       => array(
				'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
				'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
			),
			'quicktags'     => false,
		);

		$edit = [
			'wrapper'     => '<div class="cb-question-edit-desc">',
			'label'       => sprintf( '<label for="question_description" class="cb-question-edit-desc__label">%s</label>', __( 'Description', 'learnpress' ) ),
			'edit'        => AdminTemplate::editor_tinymce(
				$desc,
				$editor_id,
				$editor_settings
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function section_settings() {
		wp_enqueue_style( 'lp-edit-question' );

		$question_id = CourseBuilder::get_post_id();

		if ( $question_id === 'post-new' ) {
			echo __( 'Please save Question before setting question', 'learnpress' );
			return;
		}

		if ( absint( $question_id ) ) {
			$question_model = QuestionPostModel::find( $question_id, true );
			if ( empty( $question_model ) ) {
				return;
			}
		}

		$settings = AdminEditQuestionTemplate::instance()->html_edit_question( $question_model );

		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__question-edit" data-question-id="%s">', $question_id ),
			'form_setting'     => '<form name="lp-form-setting-question" class="lp-form-setting-question" method="post" enctype="multipart/form-data">',
			'settings'         => $settings,
			'form_setting_end' => '</form>',
			'wrapper_end'      => '</div>',
		];

		echo Template::combine_components( $output );
	}

	public function get_assigned_question( $id ) {
		$curd = new LP_Question_CURD();
		$quiz = $curd->get_quiz( $id );

		if ( $quiz ) {
			return array(
				'id'    => $quiz->ID,
				'title' => $quiz->post_title ?? '',
			);
		}

		return false;
	}
}
