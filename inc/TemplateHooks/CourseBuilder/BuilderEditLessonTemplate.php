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
use LearnPress\Models\LessonPostModel;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;

class BuilderEditLessonTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'learn-press/course-builder/lessons/overview/layout', [ $this, 'section_overview' ] );
		add_action( 'learn-press/course-builder/lessons/settings/layout', [ $this, 'section_settings' ] );
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
		$lesson_id = CourseBuilder::get_post_id();

		if ( $lesson_id === 'post-new' ) {
			$lesson_model = '';
		}

		if ( absint( $lesson_id ) ) {
			$lesson_model = LessonPostModel::find( $lesson_id, true );
			if ( empty( $lesson_model ) ) {
				return;
			}
		}

		$html_header     = $this->header_overview( $lesson_model );
		$html_assigned   = $this->assigned_course( $lesson_model );
		$html_edit_title = $this->edit_title( $lesson_model );
		$html_edit_desc  = $this->edit_desc( $lesson_model );
		$section         = [
			'wrapper'                    => sprintf( '<div class="cb-section__lesson-overview" data-lesson-id="%s">', $lesson_id ),
			'header'                     => $html_header,
			'wrapper_title_assigned'     => sprintf( '<div class="cb-section__lesson-title-assigned">' ),
			'edit_title'                 => $html_edit_title,
			'assigned_course'            => $html_assigned,
			'wrapper_title_assigned_end' => sprintf( '</div>' ),
			'edit_desc'                  => $html_edit_desc,
			'wrapper_end'                => '</div>',
		];

		echo Template::combine_components( $section );
	}

	public function header_overview( $lesson_model ) {
		$status     = ! empty( $lesson_model ) ? $lesson_model->post_status : '';
		$btn_update = sprintf( '<div class="cb-button cb-btn-update__lesson">%s</div>', __( 'Update', 'learnpress' ) );
		$btn_trash  = sprintf( '<div class="cb-button cb-btn-trash__lesson">%s</div>', __( 'Trash', 'learnpress' ) );
		$header     = [
			'wrapper'          => '<div class="cb-section__header">',
			'wrapper_left'     => '<div class="cb-section__header-left">',
			'section_title'    => sprintf( '<h3 class="lp-cb-section__title">%s</h3>', __( 'Edit Lesson', 'learnpress' ) ),
			'lesson_status'    => ! empty( $status ) ? sprintf( '<span class="lesson-status %1$s">%1$s</span>', $status ) : '',
			'wrapper_left_end' => '</div>',
			'action_wrapper'   => '<div class="cb-section__header-action">',
			'btn_update'       => $btn_update,
			'btn_trash'        => $btn_trash,
			'action_end'       => '</div>',
			'wrapper_end'      => '</div>',
		];
		return Template::combine_components( $header );
	}

	public function assigned_course( $lesson_model ) {
		$assign_course = ! empty( $lesson_model ) ? $this->get_assigned( $lesson_model->get_id() ) : '';
		$html_courses  = '';
		$assigned      = sprintf( '<span class="lesson-not-assigned">%s</span>', __( 'Not assigned yet', 'learnpress' ) );
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
			'<div class="lesson-assigned-courses"><span class="label">%s</span> %s</div>',
			__( 'Assigned', 'learnpress' ),
			$assigned
		);

		return $html_courses;
	}

	public function edit_title( $lesson_model ) {
		$title = ! empty( $lesson_model ) ? $lesson_model->get_the_title() : '';
		$edit  = [
			'wrapper'     => '<div class="cb-lesson-edit-title">',
			'label'       => sprintf( '<label for="title" class="cb-lesson-edit-title__label">%s</label>', __( 'Lesson Title', 'learnpress' ) ),
			'input'       => sprintf( '<input type="text" name="lesson_title" size="30" value="%s" id="title" class="cb-lesson-edit-title__input">', $title ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_desc( $lesson_model ) {
		$desc            = ! empty( $lesson_model ) ? $lesson_model->get_the_content() : '';
		$editor_id       = 'lesson_description_editor';
		$editor_settings = array(
			'textarea_name' => 'lesson_description',
			'textarea_rows' => 10,
			'teeny'         => false,
			'media_buttons' => true,
			'tinymce'       => array(
				'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
				'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
			),
			'quicktags'     => false,
		);

		ob_start();
		wp_editor( $desc, $editor_id, $editor_settings );
		$editor_html = ob_get_clean();

		$edit = [
			'wrapper'     => '<div class="cb-lesson-edit-desc">',
			'label'       => sprintf( '<label for="lesson_description" class="cb-lesson-edit-desc__label">%s</label>', __( 'Lesson Description', 'learnpress' ) ),
			'edit'        => $editor_html,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function section_settings() {
		// wp_enqueue_script( 'lp-cb-edit-curriculum' );
		// wp_enqueue_style( 'lp-cb-edit-curriculum' );
		// wp_enqueue_script( 'lp-cb-learnpress' );

		$lesson_id = CourseBuilder::get_post_id();

		if ( $lesson_id === 'post-new' ) {
			echo __( 'Please save Lesson before setting lesson', 'learnpress' );
			return;
		}

		if ( absint( $lesson_id ) ) {
			$lesson_model = LessonPostModel::find( $lesson_id, true );
			if ( empty( $lesson_model ) ) {
				return;
			}
		}

		if ( ! class_exists( 'LP_Meta_Box_Lesson' ) ) {
			require_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/lesson/settings.php';
		}

		$metabox = new \LP_Meta_Box_Lesson();
		ob_start();
		$metabox->output( $lesson_model );
		$settings = ob_get_clean();

		$output = [
			'wrapper'     => '<div id="lesson-settings">',
			'settings'    => $settings,
			'wrapper_end' => '</div>',
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
