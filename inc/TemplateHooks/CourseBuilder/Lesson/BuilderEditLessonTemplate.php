<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Lesson;

use Exception;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderCourseTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LP_Settings;
use WP_User;

class BuilderEditLessonTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Display layout edit/create lesson.
	 *
	 * @param array $data [ 'userModel' => UserModel, 'item_id' => int|string ]
	 *
	 * @throws Exception
	 */
	public function layout( array $data = [] ) {
		$userModel = $data['userModel'] ?? false;
		if ( ! $userModel || ! $userModel->is_instructor() ) {
			throw new Exception( __( 'You do not have permission to create or edit lessons', 'learnpress' ) );
		}

		$item_id = $data['item_id'] ?? '';
		if ( empty( $item_id ) ) {
			throw new Exception( __( 'Invalid lesson ID', 'learnpress' ) );
		}

		if ( ! CourseBuilderAccessPolicy::can_access_tab_post( 'lessons', $item_id ) ) {
			throw new Exception( __( "Sorry, you don't have permission to access this content", 'learnpress' ) );
		}

		$is_create_new = $item_id === CourseBuilder::POST_NEW;
		$lessonModel   = false;

		if ( ! $is_create_new ) {
			$lessonModel = LessonPostModel::find( (int) $item_id, true );
			if ( ! $lessonModel ) {
				throw new Exception( __( 'Lesson not found', 'learnpress' ) );
			}
		}

		$data['lessonModel'] = $lessonModel;

		$section = [
			'wrap'     => sprintf(
				'<div class="lp-cb-content" data-post-id="%1$s">',
				esc_attr( $item_id )
			),
			'header'   => $this->html_header( $data ),
			'tabs'     => $this->html_tabs( $data ),
			'wrap_end' => '</div>',
		];

		echo Template::combine_components( $section );
	}

	public function html_header( array $data = [] ): string {
		$userModel            = $data['userModel'] ?? false;
		if ( ! $userModel instanceof UserModel ) {
			return '';
		}

		$wp_user = new WP_User( $userModel );
		$lessonModel          = $data['lessonModel'] ?? false;
		$hide_instructor_access_admin_screen = LP_Settings::is_hide_instructor_access_admin_screen();
		$title                = $lessonModel ? $lessonModel->get_the_title() : __( 'Add New Lesson', 'learnpress' );
		$status               = $lessonModel ? sanitize_key( (string) $lessonModel->post_status ) : '';
		$status_label         = 'future' === $status ? __( 'scheduled', 'learnpress' ) : $status;
		$hide_wp_edit_link    = $hide_instructor_access_admin_screen && user_can( $wp_user, UserModel::ROLE_INSTRUCTOR );
		$main_action_label    = $lessonModel && 'publish' === $status ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' );

		$section = [
			'header_wrap'        => '<div class="lp-cb-header">',
			'header_left'        => '<div class="lp-cb-header__left">',
			'title'              => sprintf(
				'<h1 class="lp-cb-header__title">%s</h1>',
				esc_html( $title )
			),
			'status_badge'       => $lessonModel && $status ? sprintf(
				'<span class="lesson-status %1$s">%2$s</span>',
				esc_attr( $status ),
				esc_html( $status_label )
			) : '',
			'link_edit_on_wp'    => $lessonModel && ! $hide_wp_edit_link ? sprintf(
				'<a href="%1$s" class="lp-cb-admin-link" target="_blank" title="%2$s"%3$s>
					<span class="dashicons dashicons-wordpress"></span>
					<span>%2$s</span>
				</a>',
				esc_url( admin_url( 'post.php?post=' . $lessonModel->get_id() . '&action=edit' ) ),
				esc_attr__( 'Edit with WordPress', 'learnpress' ),
				'trash' === $status ? ' style="display:none"' : ''
			) : '',
			'header_left_end'    => '</div>',
			'header_actions'     => '<div class="lp-cb-header__actions">',
			'dropdown_wrap'      => '<div class="cb-header-actions-dropdown cb-header-actions-dropdown--single">',
			'update_btn'         => sprintf(
				'<div class="cb-btn-update cb-btn-primary cb-btn-main-action"
					data-status="%1$s"
					data-title-update="%2$s"
					data-title-publish="%3$s">%4$s</div>',
				esc_attr( 'publish' ),
				esc_attr__( 'Update', 'learnpress' ),
				esc_attr__( 'Publish', 'learnpress' ),
				esc_html( $main_action_label )
			),
			'dropdown_wrap_end'  => '</div>',
			'header_actions_end' => '</div>',
			'header_end'         => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_tabs( array $data = [] ): string {
		$section_tab = [
			'tabs_wrap' => '<div class="lp-cb-tabs">',
			'tabs'      => '',
			'tabs_end'  => '</div>',
		];

		$section_content = [
			'wrap'     => '<div class="lp-cb-tab-content">',
			'content'  => '',
			'wrap-end' => '</div>',
		];

		$tabs = apply_filters(
			'learn-press/course-builder/lessons/edit/tabs',
			[
				'overview' => [
					'title' => esc_html__( 'Overview', 'learnpress' ),
					'html'  => $this->html_tab_overview( $data ),
				],
				'settings' => [
					'title' => esc_html__( 'Settings', 'learnpress' ),
					'html'  => $this->html_tab_settings( $data ),
				],
			],
			$data
		);

		$tab_active = array_key_first( $tabs );
		if ( isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ) {
			$tab_active = $_GET['tab'];
		}

		foreach ( $tabs as $key => $tab ) {
			$is_active = $key === $tab_active;

			$section_tab['tabs'] .= sprintf(
				'<a href="#" class="lp-cb-tabs__item %s" data-tab-section="%s">%s</a>',
				$is_active ? 'is-active' : '',
				esc_attr( $tab['slug'] ?? $key ),
				esc_html( $tab['title'] ?? '' )
			);

			$section_content['content'] .= sprintf(
				'<div class="lp-cb-tab-panel %s" data-section="%s">%s</div>',
				$is_active ? '' : 'lp-hidden',
				esc_attr( $tab['slug'] ?? $key ),
				$tab['html'] ?? ''
			);
		}

		return Template::combine_components(
			[
				'tabs'     => Template::combine_components( $section_tab ),
				'contents' => Template::combine_components( $section_content ),
			]
		);
	}

	public function html_tab_overview( array $data = [] ): string {
		wp_enqueue_script( 'lp-course-builder' );

		$lesson_model = $data['lessonModel'] ?? false;
		$lesson_id    = $data['item_id'] ?? ( $lesson_model ? $lesson_model->get_id() : CourseBuilder::POST_NEW );

		if ( absint( $lesson_id ) && ! $lesson_model ) {
			$lesson_model = LessonPostModel::find( absint( $lesson_id ), true );
			if ( empty( $lesson_model ) ) {
				return '';
			}
		}

		$html_assigned   = $this->assigned_course( $lesson_model );
		$html_edit_title = $this->edit_title( $lesson_model );
		$html_permalink  = $this->edit_permalink( $lesson_model );
		$html_edit_desc  = $this->edit_desc( $lesson_model );
		$section         = [
			'wrapper'                    => sprintf( '<div class="cb-section__lesson-edit" data-lesson-id="%s">', $lesson_id ),
			'edit_title'                 => $html_edit_title,
			'wrapper_title_assigned'     => '<div class="cb-section__lesson-title-assigned">',
			'assigned_course'            => $html_assigned,
			'wrapper_title_assigned_end' => '</div>',
			'edit_permalink'             => $html_permalink,
			'edit_desc'                  => $html_edit_desc,
			'wrapper_end'                => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_tab_settings( array $data = [] ): string {
		$lesson_model = $data['lessonModel'] ?? false;
		$lesson_id    = $data['item_id'] ?? ( $lesson_model ? $lesson_model->get_id() : CourseBuilder::POST_NEW );

		if ( $lesson_id === CourseBuilder::POST_NEW || absint( $lesson_id ) <= 0 ) {
			return sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Lesson before setting lesson', 'learnpress' ) );
		}

		if ( ! $lesson_model ) {
			$lesson_model = LessonPostModel::find( absint( $lesson_id ), true );
			if ( empty( $lesson_model ) ) {
				return '';
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
			'wrapper'          => sprintf( '<div class="cb-section__lesson-edit" data-lesson-id="%s">', $lesson_id ),
			'form_setting'     => '<form name="lp-form-setting-lesson" class="lp-form-setting-lesson" method="post" enctype="multipart/form-data">',
			'settings'         => $settings,
			'form_setting_end' => '</form>',
			'wrapper_end'      => '</div>',
		];

		return Template::combine_components( $output );
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
					$course_link    = BuilderCourseTemplate::instance()->get_link_edit( $course_id );
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
