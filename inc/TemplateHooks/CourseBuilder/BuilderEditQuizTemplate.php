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
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\Admin\AdminEditQizTemplate;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

class BuilderEditQuizTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'learn-press/course-builder/quizzes/overview/layout', [ $this, 'section_overview' ] );
		add_action( 'learn-press/course-builder/quizzes/question/layout', [ $this, 'section_question' ] );
		add_action( 'learn-press/course-builder/quizzes/settings/layout', [ $this, 'section_settings' ] );
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
		$quiz_id    = CourseBuilder::get_post_id();
		$quiz_model = '';

		if ( $quiz_id === 'post-new' ) {
			$quiz_model = '';
		}

		if ( absint( $quiz_id ) ) {
			$quiz_model = QuizPostModel::find( $quiz_id, true );
			if ( empty( $quiz_model ) ) {
				return '';
			}
		}

		$html_header     = $this->header_section( $quiz_model );
		$html_assigned   = $this->assigned_course( $quiz_model );
		$html_edit_title = $this->edit_title( $quiz_model );
		$html_edit_desc  = $this->edit_desc( $quiz_model );
		$section         = [
			'wrapper'                    => sprintf( '<div class="cb-section__quiz-edit" data-quiz-id="%s">', $quiz_id ),
			'header'                     => $html_header,
			'wrapper_title_assigned'     => sprintf( '<div class="cb-section__quiz-title-assigned">' ),
			'edit_title'                 => $html_edit_title,
			'assigned_course'            => $html_assigned,
			'wrapper_title_assigned_end' => sprintf( '</div>' ),
			'edit_desc'                  => $html_edit_desc,
			'wrapper_end'                => '</div>',
		];

		echo Template::combine_components( $section );
	}

	public function header_section( $quiz_model ) {
		$status     = ! empty( $quiz_model ) ? $quiz_model->post_status : '';
		$btn_update = sprintf( '<div class="cb-button cb-btn-update__quiz" data-title-update="%s" data-title-publish="%s">%s</div>', __( 'Update', 'learnpress' ), __( 'Publish', 'learnpress' ), $status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' ) );
		$btn_trash  = ! empty( $quiz_model ) ? sprintf( '<div class="cb-button cb-btn-trash__quiz">%s</div>', __( 'Trash', 'learnpress' ) ) : '';
		$header     = [
			'wrapper'          => '<div class="cb-section__header">',
			'wrapper_left'     => '<div class="cb-section__header-left">',
			'quiz_status'      => ! empty( $status ) ? sprintf( '<span class="quiz-status %1$s">%1$s</span>', $status ) : '',
			'wrapper_left_end' => '</div>',
			'action_wrapper'   => '<div class="cb-section__header-action">',
			'btn_update'       => $btn_update,
			'btn_trash'        => $btn_trash,
			'action_end'       => '</div>',
			'wrapper_end'      => '</div>',
		];
		return Template::combine_components( $header );
	}

	public function assigned_course( $quiz_model ) {
		$assign_course = ! empty( $quiz_model ) ? $this->get_assigned( $quiz_model->get_id() ) : '';
		$html_courses  = '';
		$assigned      = sprintf( '<span class="quiz-not-assigned">%s</span>', __( 'Not assigned yet', 'learnpress' ) );
		if ( ! empty( $assign_course ) ) {
			$courses = is_array( $assign_course ) && isset( $assign_course['id'] )
				? array( $assign_course )
				: $assign_course;

			$course_htmls = array();
			foreach ( $courses as $course ) {
				$course_id    = $course['id'] ?? 0;
				$course_title = $course['title'] ?? '';

				if ( $course_id && $course_title ) {
					$course_link    = BuilderTabCourseTemplate::instance()->get_link_edit( $course_id );
					$course_htmls[] = sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_url( $course_link ),
						esc_html( $course_title )
					);
				}
			}

			if ( ! empty( $course_htmls ) ) {
				$assigned = implode( ', ', $course_htmls );
			}
		}

		$html_courses = sprintf(
			'<div class="quiz-assigned-courses"><span class="label">%s</span> %s</div>',
			__( 'Assigned', 'learnpress' ),
			$assigned
		);

		return $html_courses;
	}


	public function edit_title( $quiz_model ) {
		$title = ! empty( $quiz_model ) ? $quiz_model->get_the_title() : '';
		$edit  = [
			'wrapper'     => '<div class="cb-quiz-edit-title">',
			'label'       => sprintf( '<label for="title" class="cb-quiz-edit-title__label">%s</label>', __( 'Quiz Title', 'learnpress' ) ),
			'input'       => sprintf( '<input type="text" name="quiz_title" size="30" value="%s" id="title" class="cb-quiz-edit-title__input">', $title ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_desc( $quiz_model ) {
		$desc            = ! empty( $quiz_model ) ? $quiz_model->get_the_content() : '';
		$editor_id       = 'quiz_description_editor';
		$editor_settings = array(
			'textarea_name' => 'quiz_description',
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
			'wrapper'     => '<div class="cb-quiz-edit-desc">',
			'label'       => sprintf( '<label for="quiz_description" class="cb-quiz-edit-desc__label">%s</label>', __( 'Quiz Description', 'learnpress' ) ),
			'edit'        => AdminTemplate::editor_tinymce(
				$desc,
				$editor_id,
				$editor_settings
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function section_question() {
		// Load edit curriculum style
		wp_enqueue_style( 'lp-edit-quiz' );

		$quiz_id    = CourseBuilder::get_post_id();
		$quiz_model = '';
		if ( $quiz_id === 'post-new' ) {
			$quiz_model = '';
		}

		if ( absint( $quiz_id ) ) {
			$quiz_model = QuizPostModel::find( $quiz_id, true );
		}

		if ( empty( $quiz_model ) ) {
			return '';
		}

		$args      = [
			'id_url'  => 'edit-quiz',
			'quiz_id' => $quiz_model->ID,
		];
		$call_back = array(
			'class'  => AdminEditQizTemplate::class,
			'method' => 'render_edit_quiz',
		);

		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
	}

	public function section_settings() {
		$quiz_id = CourseBuilder::get_post_id();

		if ( $quiz_id === 'post-new' ) {
			echo __( 'Please save Quiz before setting quiz', 'learnpress' );
			return;
		}

		if ( absint( $quiz_id ) ) {
			$quiz_model = QuizPostModel::find( $quiz_id, true );
			if ( empty( $quiz_model ) ) {
				return;
			}
		}

		if ( ! class_exists( 'LP_Meta_Box_Quiz' ) ) {
			require_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/quiz/settings.php';
		}

		$metabox = new \LP_Meta_Box_Quiz();
		ob_start();
		$metabox->output( $quiz_model );
		$settings    = ob_get_clean();
		$html_header = $this->header_section( $quiz_model );

		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__quiz-edit" data-quiz-id="%s">', $quiz_id ),
			'header'           => $html_header,
			'form_setting'     => '<form name="lp-form-setting-quiz" class="lp-form-setting-quiz" method="post" enctype="multipart/form-data">',
			'settings'         => $settings,
			'form_setting_end' => '</form>',
			'wrapper_end'      => '</div>',
		];

		echo Template::combine_components( $output );
	}

	public function get_assigned( $id ) {
		$courses = learn_press_get_item_courses( $id );

		if ( empty( $courses ) ) {
			return array();
		}

		return array(
			'id'    => $courses[0]->ID,
			'title' => $courses[0]->post_title ?? '',
		);
	}
}
