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

	public function section_overview( $lesson_id = null ) {
		wp_enqueue_script( 'lp-course-builder' );
		$lesson_id    = empty( $lesson_id ) ? CourseBuilder::get_item_id() : $lesson_id;
		$lesson_model = '';

		if ( $lesson_id === CourseBuilder::POST_NEW ) {
			$lesson_model = '';
		}

		if ( absint( $lesson_id ) ) {
			$lesson_model = LessonPostModel::find( $lesson_id, true );
			if ( empty( $lesson_model ) ) {
				return;
			}
		}

		$html_assigned   = $this->assigned_course( $lesson_model );
		$html_edit_title = $this->edit_title( $lesson_model );
		$html_permalink  = $this->edit_permalink( $lesson_model );
		$html_edit_desc  = $this->edit_desc( $lesson_model );
		$section         = [
			'wrapper'                    => sprintf( '<div class="cb-section__lesson-edit" data-lesson-id="%s">', $lesson_id ),
			'edit_title'                 => $html_edit_title,
			'wrapper_title_assigned'     => sprintf( '<div class="cb-section__lesson-title-assigned">' ),
			'assigned_course'            => $html_assigned,
			'wrapper_title_assigned_end' => sprintf( '</div>' ),
			'edit_permalink'             => $html_permalink,
			'edit_desc'                  => $html_edit_desc,
			'wrapper_end'                => '</div>',
		];

		echo Template::combine_components( $section );
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
			'<div class="cb-item-edit-assigned lesson-assigned-courses"><span class="label">%s</span> %s</div>',
			__( 'Assigned', 'learnpress' ),
			$assigned
		);

		return $html_courses;
	}

	public function edit_permalink( $lesson_model ): string {
		$post_id           = ! empty( $lesson_model ) ? absint( $lesson_model->get_id() ) : 0;
		$post              = $post_id ? get_post( $post_id ) : null;
		$post_name         = $post && ! empty( $post->post_name ) ? (string) $post->post_name : '';
		$full_url          = '';
		$base_url          = '';
		$display_classes   = 'cb-permalink-display';
		$placeholder_class = 'cb-item-edit-permalink__placeholder';
		$notice_no_link    = __(
			'Permalink is only available if the item is already assigned to a course.',
			'learnpress'
		);
		$placeholder_text  = __( 'Permalink will be available after saving.', 'learnpress' );
		$show_unavailable  = true;

		if ( $post_id ) {
			$current_status = $post && ! empty( $post->post_status ) ? sanitize_key( $post->post_status ) : '';
			if ( 'draft' !== $current_status ) {
				$course_id_of_item = \LP_Course_DB::getInstance()->get_course_by_item_id( $post_id );
				if ( $course_id_of_item ) {
					$course = learn_press_get_course( $course_id_of_item );
					if ( $course ) {
						$full_url         = urldecode( $course->get_item_link( $post_id ) );
						$base_url         = $full_url;
						$show_unavailable = false;

						if ( ! empty( $post_name ) ) {
							$base_url = trailingslashit( preg_replace( '/' . preg_quote( $post_name, '/' ) . '\/?$/', '', $full_url ) );
						}
					}
				}
			}
		}

		if ( $show_unavailable && $post_id ) {
			$placeholder_text = $notice_no_link;
		}

		if ( $show_unavailable ) {
			$display_classes .= ' lp-hidden';
		} else {
			$placeholder_class .= ' lp-hidden';
		}

		$state_a = sprintf(
			'<span class="cb-item-edit-permalink__label">%s</span>
			<div class="%s">
				<a href="%s" target="_blank" class="cb-permalink-url">%s</a>
				<button type="button" class="cb-permalink-edit-btn" title="%s">
					<span class="dashicons dashicons-edit"></span>
				</button>
			</div>',
			__( 'Permalink', 'learnpress' ),
			esc_attr( $display_classes ),
			esc_url( $full_url ),
			esc_html( $full_url ),
			__( 'Edit', 'learnpress' )
		);

		$state_b = sprintf(
			'<div class="cb-permalink-editor lp-hidden">
				<span class="cb-permalink-prefix">%s</span>
				<div class="cb-permalink-input-row">
					<input type="text" name="lesson_permalink" id="lesson_permalink" value="%s" class="cb-permalink-slug-input" placeholder="%s">
					<div class="cb-permalink-actions">
						<button type="button" class="cb-permalink-ok-btn">%s</button>
						<button type="button" class="cb-permalink-cancel-btn">%s</button>
					</div>
				</div>
			</div>',
			esc_html( $base_url ),
			esc_attr( $post_name ),
			esc_attr__( 'your-slug', 'learnpress' ),
			__( 'OK', 'learnpress' ),
			__( 'Cancel', 'learnpress' )
		);

		$hidden_base = sprintf(
			'<input type="hidden" id="cb-permalink-base-url" value="%s">',
			esc_attr( $base_url )
		);

		$placeholder = sprintf(
			'<span class="%s">%s</span>',
			esc_attr( $placeholder_class ),
			esc_html( $placeholder_text )
		);

		$view = [
			'wrapper'     => '<div class="cb-item-edit-permalink cb-course-edit-permalink">',
			'state_a'     => $state_a,
			'state_b'     => $state_b,
			'hidden_base' => $hidden_base,
			'placeholder' => $placeholder,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $view );
	}

	public function edit_title( $lesson_model ) {
		$title = ! empty( $lesson_model ) ? $lesson_model->get_the_title() : '';
		$edit  = [
			'wrapper'     => '<div class="cb-lesson-edit-title">',
			'label'       => sprintf( '<label for="title" class="cb-lesson-edit-title__label">%s</label>', __( 'Title', 'learnpress' ) ),
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
				'content_style' => "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; font-size: 14px; line-height: 1.6; color: #1e1e1e; }",
				'toolbar1'      => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
				'toolbar2'      => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
			),
			'quicktags'     => true,
		);

		ob_start();
		wp_editor( $desc, $editor_id, $editor_settings );
		$editor_html = ob_get_clean();

		$edit = [
			'wrapper'     => '<div class="cb-lesson-edit-desc">',
			'label'       => sprintf( '<label for="lesson_description" class="cb-lesson-edit-desc__label">%s</label>', __( 'Description', 'learnpress' ) ),
			'edit'        => $editor_html,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function section_settings( $lesson_id = null ) {
		$lesson_id    = empty( $lesson_id ) ? CourseBuilder::get_item_id() : $lesson_id;
		$lesson_model = '';

		if ( $lesson_id === CourseBuilder::POST_NEW || absint( $lesson_id ) <= 0 ) {
			$message = sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Lesson before setting lesson', 'learnpress' ) );
			echo $message;
			return;
		}

		$lesson_model = LessonPostModel::find( absint( $lesson_id ), true );
		if ( empty( $lesson_model ) ) {
			return;
		}

		if ( ! class_exists( 'LP_Meta_Box_Lesson' ) ) {
			require_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/lesson/settings.php';
		}

		$metabox = new \LP_Meta_Box_Lesson();
		ob_start();
		$metabox->output( $lesson_model );
		$settings = ob_get_clean();

		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__lesson-edit" data-lesson-id="%s">', $lesson_id ),
			'form_setting'     => '<form name="lp-form-setting-lesson" class="lp-form-setting-lesson" method="post" enctype="multipart/form-data">',
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
