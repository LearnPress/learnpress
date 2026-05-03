<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Course;

use Exception;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Admin\AI\AdminEditCourseCurriculumWithAITemplate;
use LearnPress\TemplateHooks\Admin\AI\AdminEditWithAITemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\TemplateHooks\CourseBuilder\BuilderPopupTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Settings;
use Throwable;
use WP_User;

class BuilderEditCourseTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );

		// Register filter for adding edit popup button in Course Builder curriculum
		add_filter( 'learn-press/admin/curriculum/section-item/actions', [ $this, 'add_edit_popup_button' ], 10, 5 );
	}

	/**
	 * Display layout edit/create course
	 *
	 * @param array $data [ 'userModel' => UserModel, 'courseModel' => CourseModel, 'item_id' => int ]
	 *
	 * @throws Exception
	 */
	public function layout( array $data = [] ) {
		try {
			// Check permission
			$userModel = $data['userModel'] ?? false;
			if ( ! $userModel || ! $userModel->is_instructor() ) {
				throw new Exception( __( 'You do not have permission to create or edit courses', 'learnpress' ) );
			}

			$userCoursePostModel = new CoursePostModel();
			if ( ! $userCoursePostModel->check_capabilities_create() ) {
				throw new Exception( __( 'You do not have permission to create or edit courses', 'learnpress' ) );
			}

			$item_id = $data['item_id'] ?? '';
			if ( empty( $item_id ) ) {
				throw new Exception( __( 'Invalid course ID', 'learnpress' ) );
			}

			if ( ! CourseBuilderAccessPolicy::can_access_tab_post( 'courses', $item_id ) ) {
				throw new Exception( __( "Sorry, you don't have permission to access this content", 'learnpress' ) );
			}

			$is_create_new = $item_id === CourseBuilder::POST_NEW;
			$courseModel   = false;

			if ( ! $is_create_new ) {
				$courseModel = CourseModel::find( (int) $item_id, true );
				if ( ! $courseModel ) {
					throw new Exception( __( 'Course not found', 'learnpress' ) );
				}

				if ( $courseModel->get_status() === PostModel::STATUS_TRASH ) {
					throw new Exception(
						__(
							'You cannot edit this item because it is in the Trash. Please restore it and try again.',
							'learnpress'
						)
					);
				}
			}

			$data['courseModel'] = $courseModel;

			$section = [
				'wrap'     => sprintf(
					'<div class="lp-cb-content" data-post-id="%1$s">',
					esc_attr( $item_id ),
				),
				'header'   => $this->html_header( $data ),
				'tabs'     => $this->html_tabs( $data ),
				'wrap_end' => '</div>',
			];

			echo Template::combine_components( $section );
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), 'error' );
		}
	}

	/**
	 * HTML header
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_header( array $data = [] ): string {
		/** @var UserModel $userModel */
		$userModel = $data['userModel'] ?? false;
		if ( ! $userModel instanceof UserModel ) {
			return '';
		}

		/** @var CourseModel|false $courseModel */
		$courseModel          = $data['courseModel'] ?? false;
		$hide_instructor_access_admin_screen = LP_Settings::is_hide_instructor_access_admin_screen();
		$more_actions_icon    = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-more.svg' );
		$title                = $courseModel ? $courseModel->get_title() : __( 'Add New Course', 'learnpress' );
		$status_badge         = $courseModel ? $courseModel->get_status() : '';
		$status               = '';
		if ( $courseModel ) {
			$status = $courseModel->get_status();
		}
		$main_action_status = in_array(
			$status,
			array(
				'publish',
				'draft',
				'pending',
				'future',
				'private',
			),
			true
		) ? $status : 'publish';
		$wp_user      = new WP_User( $userModel ) ;
		$hide_wp_edit_link  = $hide_instructor_access_admin_screen && user_can( $wp_user, UserModel::ROLE_INSTRUCTOR );

		$section = [
			'header_wrap'        => '<div class="lp-cb-header">',
			'header_left'        => '<div class="lp-cb-header__left">',
			'title'              => sprintf(
				'<h1 class="lp-cb-header__title">%s</h1>',
				esc_html( $title )
			),
			'status_badge'       => $courseModel ? sprintf(
				'<span class="course-status %s">%s</span>',
				$status_badge,
				esc_html( $courseModel->get_post_model()->get_status_i18n() )
			) : '',
			'link_edit_on_wp'    => $courseModel && ! $hide_wp_edit_link ? sprintf(
				'<a href="%1$s" class="lp-cb-admin-link" target="_blank" title="%2$s"%3$s>
					<span class="dashicons dashicons-wordpress"></span>
					<span>%2$s</span>
				</a>',
				esc_url( admin_url( "post.php?post={$courseModel->get_id()}&action=edit" ) ),
				esc_attr__( 'Edit with WordPress', 'learnpress' ),
				PostModel::STATUS_TRASH === $status ? ' style="display:none"' : ''
			) : '',
			'header_left_end'    => '</div>',
			'header_actions'     => '<div class="lp-cb-header__actions">',
			'preview_btn'        => $courseModel && $courseModel->get_status() !== PostModel::STATUS_TRASH ? sprintf(
				'<a href="%1$s" class="cb-button cb-btn-preview cb-btn-secondary lp-button" target="_blank">%2$s</a>',
				esc_url( get_permalink( $courseModel->get_id() ) ),
				esc_html__( 'Preview', 'learnpress' )
			) : '',
			'dropdown_wrap'      => '<div class="cb-header-actions-dropdown cb-header-actions-dropdown--single">',
			'update_btn'         => sprintf(
				'<div class="cb-btn-update cb-btn-primary cb-btn-main-action lp-button"
					data-status="%1$s"
					data-title-update="%2$s"
					data-title-publish="%3$s"
					data-title-draft="%4$s"
					data-title-submit-review="%5$s">%6$s</div>',
				esc_attr( $main_action_status ),
				esc_attr__( 'Update', 'learnpress' ),
				esc_attr__( 'Publish', 'learnpress' ),
				esc_attr__( 'Save Draft', 'learnpress' ),
				esc_attr__( 'Submit for Review', 'learnpress' ),
				esc_html__( 'Update', 'learnpress' )
			),
			'dropdown_wrap_end'  => '</div>',
			'expanded_actions'   => $courseModel ? sprintf(
				'<div class="cb-header-action-expanded">
					<button type="button" class="course-action-expanded lp-button" aria-haspopup="true" aria-expanded="false" aria-label="%1$s">
						%2$s
					</button>
					<div class="cb-header-action-expanded__items">
						<div class="cb-header-action-expanded__duplicate cb-btn-duplicate-course lp-button"
							data-title="%3$s"
							data-content="%4$s">
							<span class="dashicons dashicons-admin-page"></span>
							%5$s
						</div>
						<div class="cb-header-action-expanded__trash cb-btn-trash lp-button">
							<span class="dashicons dashicons-trash"></span>
							%6$s
						</div>
					</div>
				</div>',
				esc_attr__( 'More actions', 'learnpress' ),
				$more_actions_icon,
				esc_attr__( 'Are you sure?', 'learnpress' ),
				esc_attr__( 'Are you sure you want to duplicate this course?', 'learnpress' ),
				esc_html__( 'Duplicate', 'learnpress' ),
				esc_html__( 'Move to Trash', 'learnpress' )
			) : '',
			'header_actions_end' => '</div>',
			'header_end'         => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Render tabs
	 *
	 * @param array $data
	 *
	 * @return string
	 */
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
			'learn-press/course-builder/course/edit/tabs',
			[
				'overview'   => [
					'title' => esc_html__( 'Overview', 'learnpress' ),
					'html'  => $this->html_tab_overview( $data ),
				],
				'curriculum' => [
					'title' => esc_html__( 'Curriculum', 'learnpress' ),
					'html'  => $this->html_tab_curriculum( $data ),
				],
				'settings'   => [
					'title' => esc_html__( 'Settings', 'learnpress' ),
					'html'  => $this->html_tab_settings( $data ),
				],
			],
			$data
		);

		$tab_active = array_key_first( $tabs );
		if ( isset( $_GET['tab'] ) ) {
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

			/**
			 * @uses html_tab_overview
			 * @uses html_tab_curriculum
			 * @uses html_tab_settings
			 */
			$section_content['content'] .= sprintf(
				'<div class="lp-cb-tab-panel %s" data-section="%s">%s</div>',
				$is_active ? '' : 'lp-hidden',
				esc_attr( $key ),
				$tab['html']
			);
		}

		$section = [
			'tabs'     => Template::combine_components( $section_tab ),
			'contents' => Template::combine_components( $section_content ),
		];

		return Template::combine_components( $section );
	}

	public function html_tab_overview( array $data = [] ) {
		wp_enqueue_script( 'lp-course-builder' );

		$courseMoel = $data['courseModel'] ?? null;
		$course_id  = 0;
		if ( $courseMoel instanceof CourseModel ) {
			$course_id = $courseMoel->get_id();
		}

		$html_edit_title     = $this->edit_title( $courseMoel );
		$html_edit_permalink = $this->edit_permalink( $courseMoel );
		$html_edit_features  = $this->edit_featured_image( $courseMoel );
		$html_edit_publish   = $this->edit_publish( $courseMoel );
		$html_edit_desc      = $this->edit_desc( $courseMoel );
		$html_edit_cat       = $this->edit_categories( $courseMoel );
		$html_edit_tags      = $this->edit_tags( $courseMoel );

		$section = [
			'wrapper'                => sprintf( '<div class="cb-section__course-edit" data-course-id="%s">', $course_id ),
			'content_wrapper'        => '<div class="cb-course-edit-content">',
			// Left column
			'left_column'            => '<div class="cb-course-edit-column cb-course-edit-column--left">',
			'edit_title'             => $html_edit_title,
			'edit_permalink'         => $html_edit_permalink,
			'edit_publish'           => $html_edit_publish,
			'edit_features'          => $html_edit_features,
			'left_column_end'        => '</div>',
			// Right column
			'right_column'           => '<div class="cb-course-edit-column cb-course-edit-column--right">',
			'edit_desc'              => $html_edit_desc,
			'edit_term_category'     => '<div class="cb-course-edit-terms-categories-wrapper">',
			'edit_cat'               => $html_edit_cat,
			'edit_term'              => $html_edit_tags,
			'edit_term_category_end' => '</div>',
			'right_column_end'       => '</div>',
			'content_wrapper_end'    => '</div>',
			'ai_templates'           => $this->html_overview_ai_templates(),
			'wrapper_end'            => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML Curriculum
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_tab_curriculum( array $data = [] ): string {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-admin-learnpress' );

		$course_id = CourseBuilder::get_item_id();

		if ( $course_id === CourseBuilder::POST_NEW ) {
			return Template::print_message( __( 'Please save Course before add Section', 'learnpress' ), 'info', false );
		}

		$courseModel = $data['courseModel'] ?? null;
		if ( ! $courseModel instanceof CourseModel ) {
			return Template::print_message( __( 'Course is invalid!', 'learnpress' ), 'error', false );
		}

		return $this->html_curriculum( $courseModel );
	}

	public function html_tab_settings( array $data = [] ) {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-tom-select' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-learnpress' );

		$course_id = CourseBuilder::get_item_id();

		if ( $course_id === CourseBuilder::POST_NEW ) {
			return Template::print_message( __( 'Please save Course before setting course', 'learnpress' ), 'info', false );
		}

		$courseModel = $data['courseModel'] ?? null;
		if ( ! $courseModel instanceof CourseModel ) {
			return Template::print_message( __( 'Course is invalid!', 'learnpress' ), 'error', false );
		}

		if ( ! class_exists( 'LP_Meta_Box_Course' ) ) {
			require_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/course/settings.php';
		}

		add_filter( 'learnpress/course/metabox/tabs', [ $this, 'filter_course_builder_settings_tabs' ], 999 );
		add_filter(
			'learn-press/course/meta-box/assessment/final-quiz/edit-link',
			[
				$this,
				'filter_course_builder_assessment_final_quiz_edit_link',
			],
			10,
			2
		);

		$metabox = \LP_Meta_Box_Course::instance();
		ob_start();
		$metabox->output( $courseModel );
		$settings = ob_get_clean();

		remove_filter( 'learnpress/course/metabox/tabs', [ $this, 'filter_course_builder_settings_tabs' ], 999 );
		remove_filter(
			'learn-press/course/meta-box/assessment/final-quiz/edit-link',
			[
				$this,
				'filter_course_builder_assessment_final_quiz_edit_link',
			],
			10
		);

		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__course-edit" data-course-id="%s">', $course_id ),
			'form_setting'     => '<form name="lp-form-setting-course" class="lp-form-setting-course" method="post" enctype="multipart/form-data">',
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

	public function edit_title( $course_model ) {
		$title = '';
		if ( ! empty( $course_model ) ) {
			$post_id = absint( $course_model->get_id() );
			$title   = $post_id ? (string) get_post_field( 'post_title', $post_id, 'raw' ) : '';
		}
		$char_count = mb_strlen( wp_strip_all_tags( $title ) );
		$ai_button  = $this->html_overview_ai_button( '#lp-tmpl-edit-title-ai' );
		$edit       = [
			'wrapper'        => '<div class="cb-course-edit-title">',
			'label_wrap'     => '<div class="cb-course-edit-title__label-wrap">',
			'label'          => sprintf( '<label for="title" class="cb-course-edit-title__label">%s <span class="required">*</span></label>', __( 'Course Title', 'learnpress' ) ),
			'char_count'     => sprintf( '<span class="cb-course-edit-title__char-count">%s</span>', sprintf( __( '%d characters', 'learnpress' ), $char_count ) ),
			'ai_button'      => $ai_button,
			'label_wrap_end' => '</div>',
			'input'          => sprintf( '<input type="text" name="course_title" size="30" value="%s" id="title" class="cb-course-edit-title__input" placeholder="%s">', esc_attr( $title ), esc_attr__( 'example', 'learnpress' ) ),
			'wrapper_end'    => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_permalink( $course_model ) {
		$post_id   = ! empty( $course_model ) ? $course_model->get_id() : '';
		$post_name = '';

		// Hide permalink for new courses
		if ( empty( $post_id ) || $post_id === CourseBuilder::POST_NEW ) {
			return '';
		}

		if ( $post_id ) {
			$post      = get_post( $post_id );
			$post_name = $post ? $post->post_name : '';
		}

		// Get base URL for courses
		$courses_page_id = learn_press_get_page_id( 'courses' );
		$base_url        = '';
		if ( $courses_page_id ) {
			$base_url = trailingslashit( get_permalink( $courses_page_id ) );
		} else {
			$base_url = trailingslashit( home_url() ) . 'courses/';
		}

		$full_url     = urldecode( (string) get_permalink( $post_id ) );
		$display_data = $this->get_course_display_permalink_data( (int) $post_id, (string) $post_name );
		$display_url  = (string) ( $display_data['url'] ?? '' );
		$editor_slug  = (string) ( $display_data['slug'] ?? '' );

		if ( empty( $display_url ) ) {
			$display_url = $full_url;
		}

		if ( empty( $full_url ) ) {
			$full_url = $display_url;
		}

		if ( empty( $editor_slug ) ) {
			$editor_slug = (string) $post_name;
		}

		if ( empty( $editor_slug ) && ! empty( $display_url ) ) {
			$display_path = parse_url( $display_url, PHP_URL_PATH );
			if ( is_string( $display_path ) && '' !== $display_path ) {
				$editor_slug = basename( untrailingslashit( $display_path ) );
			}
		}

		// Use publish-style permalink as editable base, but keep href as current permalink.
		if (
			! empty( $editor_slug ) &&
			false === strpos( $display_url, '?p=' ) &&
			false === strpos( $display_url, '&p=' ) &&
			false === strpos( $display_url, '?lp_course=' ) &&
			false === strpos( $display_url, '&lp_course=' )
		) {
			$base_url = trailingslashit( preg_replace( '/' . preg_quote( $editor_slug, '/' ) . '\/?$/', '', $display_url ) );
		}

		$state_a = sprintf(
			'<span class="cb-permalink-label">%s</span>
            <div class="cb-permalink-display">
                <a href="%s" target="_blank" class="cb-permalink-url">%s</a>
                <button type="button" class="cb-permalink-edit-btn" title="%s">
                    <span class="dashicons dashicons-edit"></span>
                </button>
            </div>',
			__( 'Permalink', 'learnpress' ),
			esc_url( $full_url ),
			esc_html( $display_url ),
			__( 'Edit', 'learnpress' )
		);

		$state_b = sprintf(
			'<div class="cb-permalink-editor lp-hidden">
                <span class="cb-permalink-prefix">%s</span>
                <div class="cb-permalink-input-row">
                    <input type="text" name="course_permalink" id="course_permalink" value="%s" class="cb-permalink-slug-input" placeholder="%s">
                    <div class="cb-permalink-actions">
                        <button type="button" class="cb-permalink-ok-btn">%s</button>
                        <button type="button" class="cb-permalink-cancel-btn">%s</button>
                    </div>
                </div>
			</div>',
			esc_html( $base_url ),
			esc_attr( $editor_slug ),
			esc_attr__( 'your-slug', 'learnpress' ),
			__( 'OK', 'learnpress' ),
			__( 'Cancel', 'learnpress' )
		);

		$hidden_base = sprintf(
			'<input type="hidden" id="cb-permalink-base-url" value="%s">',
			esc_attr( $base_url )
		);

		$edit = [
			'wrapper'     => '<div class="cb-course-edit-permalink">',
			'state_a'     => $state_a,
			'state_b'     => $state_b,
			'hidden_base' => $hidden_base,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	/**
	 * Get permalink display URL in publish-style format for editing slug.
	 *
	 * @param int $post_id
	 * @param string $post_name
	 *
	 * @return array<string, string>
	 */
	private function get_course_display_permalink_data( int $post_id, string $post_name = '' ): array {
		$display_url = urldecode( (string) get_permalink( $post_id ) );
		$sample_slug = $post_name;

		if ( ! function_exists( 'get_sample_permalink' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}

		if ( function_exists( 'get_sample_permalink' ) ) {
			$sample_permalink = get_sample_permalink( $post_id );
			if ( is_array( $sample_permalink ) && ! empty( $sample_permalink[0] ) ) {
				$sample_slug = ! empty( $sample_permalink[1] ) ? (string) $sample_permalink[1] : $sample_slug;
				$display_url = str_replace(
					[ '%postname%', '%pagename%' ],
					$sample_slug,
					(string) $sample_permalink[0]
				);
			}
		}

		$post = get_post( $post_id );
		if ( $post instanceof \WP_Post ) {
			$display_url = \LP_Helper::handle_lp_permalink_structure( $display_url, $post );
		}

		return [
			'url'  => urldecode( $display_url ),
			'slug' => urldecode( (string) $sample_slug ),
		];
	}

	public function edit_desc( $course_model ) {
		$desc            = ! empty( $course_model ) ? $course_model->get_description() : '';
		$word_count      = str_word_count( wp_strip_all_tags( $desc ) );
		$editor_id       = 'course_description_editor';
		$ai_button       = $this->html_overview_ai_button( '#lp-tmpl-edit-description-ai' );
		$editor_settings = array(
			'textarea_name' => 'course_description',
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

		$edit = [
			'wrapper'        => '<div class="cb-course-edit-desc">',
			'label_wrap'     => '<div class="cb-course-edit-desc__label-wrap">',
			'label'          => sprintf( '<label for="course_description" class="cb-course-edit-desc__label">%s</label>', __( 'Description', 'learnpress' ) ),
			'ai_button'      => $ai_button,
			'label_wrap_end' => '</div>',
			'edit'           => AdminTemplate::editor_tinymce(
				$desc,
				$editor_id,
				$editor_settings
			),
			'wrapper_end'    => '</div>',
		];

		return Template::combine_components( $edit );
	}

	/**
	 * Check if Overview AI button and templates should be shown.
	 *
	 * @return bool
	 */
	protected function can_show_overview_ai_button(): bool {
		return \LP_Settings::get_option( 'enable_open_ai', 'no' ) === 'yes'
				&& ! empty( \LP_Settings::get_option( 'open_ai_secret_key', '' ) );
	}

	/**
	 * Render icon-only AI button in Course Builder overview.
	 *
	 * @param string $template_id
	 *
	 * @return string
	 */
	protected function html_overview_ai_button( string $template_id ): string {
		if ( ! $this->can_show_overview_ai_button() ) {
			return '';
		}

		$button_label = esc_html__( 'Generate with AI', 'learnpress' );

		return sprintf(
			'<button type="button" class="cb-course-edit-ai-btn lp-btn-generate-with-ai" data-template="%1$s" title="%2$s" aria-label="%2$s"><i class="lp-ico-ai" aria-hidden="true"></i></button>',
			esc_attr( $template_id ),
			esc_attr( $button_label )
		);
	}

	/**
	 * Render AI popup templates for Course Builder overview edit page.
	 *
	 * @return string
	 */
	protected function html_overview_ai_templates(): string {
		if ( ! $this->can_show_overview_ai_button() ) {
			return '';
		}

		try {
			return AdminEditWithAITemplate::instance()->render_for_frontend( [ 'title', 'description', 'image' ] );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return '';
	}

	/**
	 * Render AI popup template for curriculum edit in Course Builder.
	 *
	 * @return string
	 */
	protected function html_curriculum_ai_templates(): string {
		if ( ! $this->can_show_overview_ai_button() ) {
			return '';
		}

		try {
			return AdminEditCourseCurriculumWithAITemplate::instance()->render_for_frontend();
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return '';
	}

	public function edit_categories( $course_model ) {
		if ( ! function_exists( 'post_categories_meta_box' ) ) {
			require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';
		}
		if ( ! function_exists( 'wp_popular_terms_checklist' ) ) {
			require_once ABSPATH . 'wp-admin/includes/template.php';
		}

		$post_id = ! empty( $course_model ) ? $course_model->get_id() : get_the_ID();
		$post    = get_post( $post_id );

		$force_checked_ontop_false = function ( $args ) {
			if ( isset( $args['taxonomy'] ) && 'course_category' === $args['taxonomy'] ) {
				$args['checked_ontop'] = false;
			}

			return $args;
		};

		ob_start();

		add_filter( 'wp_terms_checklist_args', $force_checked_ontop_false );

		if ( function_exists( 'post_categories_meta_box' ) ) {
			\post_categories_meta_box(
				$post,
				array(
					'id'       => 'course_categorydiv',
					'title'    => __( 'Categories', 'learnpress' ),
					'callback' => 'post_categories_meta_box',
					'args'     => array(
						'taxonomy'      => 'course_category',
						'checked_ontop' => false,
					),
				)
			);
		}

		remove_filter( 'wp_terms_checklist_args', $force_checked_ontop_false );
		$html_meta_box = ob_get_clean();

		// Build add new category form (between header and content)
		$parent_terms   = get_terms(
			[
				'taxonomy'   => 'course_category',
				'hide_empty' => false,
			]
		);
		$parent_options = sprintf( '<option value="0">— %s —</option>', __( 'Parent Category', 'learnpress' ) );
		if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) ) {
			foreach ( $parent_terms as $term ) {
				$parent_options .= sprintf(
					'<option value="%d">%s</option>',
					$term->term_id,
					esc_html( $term->name )
				);
			}
		}

		$form_add_category = sprintf(
			'<div class="cb-course-edit-terms__form-add-category" style="display:none;">
                <input type="text" class="cb-course-edit-category__input" placeholder="%s" id="cb-newcourse_category" />
                <select class="cb-course-edit-category__select-parent" id="cb-newcourse_category_parent">%s</select>
                <button type="button" class="cb-course-edit-category__btn-cancel">%s</button>
                <button type="button" class="cb-course-edit-category__btn-save" id="cb-course_category-add-submit">%s</button>
            </div>',
			esc_attr__( 'Enter Category Name', 'learnpress' ),
			$parent_options,
			esc_html__( 'Cancel', 'learnpress' ),
			esc_html__( 'Add', 'learnpress' ),
		);

		$edit = [
			'wrapper'           => '<div class="cb-course-edit-categories__wrapper">',
			'header'            => '<div class="cb-terms-header">',
			'label_wrap'        => '<div class="cb-terms-header__label-wrap">',
			'label'             => sprintf( '<label class="cb-terms-header__label">%s</label>', __( 'Categories', 'learnpress' ) ),
			'btn_search'        => sprintf(
				'<button type="button" class="cb-terms-header__btn-search" data-toggle-target="#cb-course-edit-categories-search-toolbar" aria-expanded="false" aria-label="%s">
                    <i class="lp-icon-search"></i>
                </button>',
				esc_attr__( 'Search categories', 'learnpress' )
			),
			'label_wrap_end'    => '</div>',
			'btn_add_new'       => sprintf( '<button class="cb-course-edit-category__btn-add-new cb-terms-header__btn-add-new">%s</button>', __( 'Add New', 'learnpress' ) ),
			'header_end'        => '</div>',
			'form_add_category' => $form_add_category,
			'search'            => sprintf(
				'<div class="cb-course-edit-categories__toolbar cb-terms-search-toolbar" id="cb-course-edit-categories-search-toolbar">
                    <label class="cb-course-edit-categories__search-wrap">
                        <span class="screen-reader-text">%1$s</span>
                        <input type="search" class="cb-course-edit-category__search-input" placeholder="%2$s" />
                    </label>
                </div>',
				esc_html__( 'Search categories', 'learnpress' ),
				esc_attr__( 'Search categories', 'learnpress' )
			),
			'content'           => $html_meta_box,
			'wrapper_end'       => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_tags( $course_model ) {
		$course_terms = ! empty( $course_model ) ? $course_model->get_tags() : [];
		$tags         = get_terms(
			[
				'taxonomy'   => LP_COURSE_TAXONOMY_TAG,
				'hide_empty' => false,
			]
		);

		$selected_tag_ids = array_map(
			function ( $term ) {
				return (int) $term->term_id;
			},
			$course_terms
		);

		$html_selected_chips  = '';
		$html_available_chips = '';

		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				$tag_id     = $tag->term_id;
				$tag_name   = $tag->name;
				$tag_count  = $tag->count;
				$is_checked = in_array( (int) $tag_id, $selected_tag_ids, true );
				$html_chip  = $this->input_checkbox_tag_item( $tag_id, $tag_name, $is_checked, $tag_count );

				if ( $is_checked ) {
					$html_selected_chips .= $html_chip;
				} else {
					$html_available_chips .= $html_chip;
				}
			}
		}

		$html_chips    = $html_selected_chips . $html_available_chips;
		$count_all     = substr_count( $html_chips, 'class="cb-tag-chip"' );
		$empty_default = esc_html__( 'No tags found.', 'learnpress' );
		$empty_search  = esc_html__( 'No matching tags.', 'learnpress' );

		$toolbar = sprintf(
			'<div class="cb-course-edit-tags__toolbar cb-terms-search-toolbar" id="cb-course-edit-tags-search-toolbar">
                <label class="cb-course-edit-tags__search-wrap">
                    <span class="screen-reader-text">%1$s</span>
                    <input type="search" class="cb-course-edit-tags__search-input" placeholder="%2$s" />
                </label>
            </div>',
			esc_html__( 'Search tags', 'learnpress' ),
			esc_attr__( 'Search tags', 'learnpress' )
		);

		$edit = [
			'wrapper'                  => '<div class="cb-course-edit-tags__wrapper">',
			'header'                   => '<div class="cb-terms-header">',
			'label_wrap'               => '<div class="cb-terms-header__label-wrap">',
			'label'                    => sprintf( '<label class="cb-terms-header__label">%s</label>', __( 'Tags', 'learnpress' ) ),
			'btn_search'               => sprintf(
				'<button type="button" class="cb-terms-header__btn-search" data-toggle-target="#cb-course-edit-tags-search-toolbar" aria-expanded="false" aria-label="%s">
                    <i class="lp-icon-search"></i>
                </button>',
				esc_attr__( 'Search tags', 'learnpress' )
			),
			'label_wrap_end'           => '</div>',
			'btn_add_new'              => sprintf( '<button class="cb-course-edit-tag__btn-add-new cb-terms-header__btn-add-new">%s</button>', __( 'Add New', 'learnpress' ) ),
			'header_end'               => '</div>',
			'form_add_tag_wrapper'     => '<div class="cb-course-edit-terms__form-add-tag" style="display:none;">',
			'input'                    => '<input type="text" class="cb-course-edit-tags__input" placeholder="' . esc_attr__( 'Enter Tag Name', 'learnpress' ) . '"/>',
			'btn_cancel'               => sprintf( '<button type="button" class="cb-course-edit-tag__btn-cancel">%s</button>', __( 'Cancel', 'learnpress' ) ),
			'button'                   => '<button type="button" class="cb-course-edit-tags__btn-save">' . esc_html__( 'Add', 'learnpress' ) . '</button>',
			'form_add_tag_wrapper_end' => '</div>',
			'toolbar'                  => $toolbar,
			'wrapper_checkbox'         => '<div class="cb-course-edit-tags__checkbox-wrapper">',
			'checkbox'                 => $html_chips,
			'wrapper_checkbox_end'     => '</div>',
			'empty'                    => sprintf(
				'<p class="cb-course-edit-tags__empty%1$s" data-empty-default="%2$s" data-empty-search="%3$s">%2$s</p>',
				$count_all > 0 ? ' lp-hidden' : '',
				esc_attr( $empty_default ),
				esc_attr( $empty_search )
			),
			'wrapper_end'              => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function input_checkbox_tag_item( $term_id, $term_name, $is_checked, $count = 0 ) {
		if ( 0 === $count ) {
			$tag_obj = get_term( $term_id, LP_COURSE_TAXONOMY_TAG );
			if ( $tag_obj && ! is_wp_error( $tag_obj ) ) {
				$count = $tag_obj->count;
			}
		}

		$tag_name_search = wp_strip_all_tags( $term_name );
		if ( function_exists( 'mb_strtolower' ) ) {
			$tag_name_search = mb_strtolower( $tag_name_search );
		} else {
			$tag_name_search = strtolower( $tag_name_search );
		}

		$html  = sprintf(
			'<div class="cb-tag-chip" data-tag-name="%s" data-term-id="%d">',
			esc_attr( $tag_name_search ),
			(int) $term_id
		);
		$html .= sprintf(
			'<input type="checkbox" name="course_tags[]" value="%s" id="course_tag_%s" %s>',
			$term_id,
			$term_id,
			checked( $is_checked, true, false )
		);
		$html .= sprintf(
			'<label for="course_tag_%s"><span class="cb-tag-chip__name">%s</span><span class="cb-tag-chip__count">(%d)</span><span class="cb-tag-chip__remove">&times;</span></label>',
			$term_id,
			esc_html( $term_name ),
			$count
		);
		$html .= '</div>';

		return $html;
	}

	public function edit_featured_image( $course_model ) {
		$post_id   = ! empty( $course_model ) ? $course_model->get_id() : '';
		$ai_button = $this->html_overview_ai_button( '#lp-tmpl-edit-image-ai' );

		$thumbnail_id  = ! empty( $post_id ) ? get_post_thumbnail_id( $post_id ) : '';
		$thumbnail_url = '';
		$thumbnail_alt = '';

		if ( $thumbnail_id ) {
			$thumbnail_url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
			$thumbnail_alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
		}

		$has_image = ! empty( $thumbnail_url );

		$featured_image_html = '<div class="cb-featured-image-container">';

		// Upload area
		$featured_image_html .= sprintf(
			'<div class="cb-featured-image-dropzone %s" data-post-id="%s">',
			$has_image ? 'has-image' : '',
			esc_attr( $post_id )
		);

		if ( $has_image ) {
			$featured_image_html .= sprintf(
				'<img src="%s" alt="%s" class="cb-featured-image-preview__img">',
				esc_url( $thumbnail_url ),
				esc_attr( $thumbnail_alt )
			);
		} else {
			$featured_image_html .= '<div class="cb-featured-image-upload-content">';
			$featured_image_html .= '<span class="cb-featured-image-icon"><span class="cb-featured-image-icon__image" aria-hidden="true"></span></span>';
			$featured_image_html .= sprintf(
				'<p class="cb-featured-image-text"><a href="#" class="cb-featured-image-link">%s</a></p>',
				__( 'Click to upload', 'learnpress' )
			);
			$featured_image_html .= sprintf(
				'<p class="cb-featured-image-hint">%s</p>',
				__( 'JPG, JPEG, PNG less than 1MB', 'learnpress' )
			);
			$featured_image_html .= '</div>';
		}

		$featured_image_html .= '</div>';

		$featured_image_html .= sprintf(
			'<input type="hidden" name="course_thumbnail_id" id="course_thumbnail_id" value="%s">',
			esc_attr( $thumbnail_id )
		);

		// Action buttons wrapper
		$featured_image_html .= '<div class="cb-featured-image-actions">';

		// Remove button (only show when has image)
		if ( $has_image ) {
			$featured_image_html .= sprintf(
				'<button type="button" class="cb-remove-featured-image">%s</button>',
				'<span class="cb-remove-featured-image__icon" aria-hidden="true"></span>'
			);

			$featured_image_html .= sprintf(
				'<button type="button" class="cb-change-featured-image">%s</button>',
				__( 'Replace', 'learnpress' )
			);
		}

		$featured_image_html .= '</div>'; // End actions wrapper
		$featured_image_html .= '</div>'; // End container

		$edit = [
			'wrapper'        => '<div class="cb-course-edit-featured-image">',
			'label_wrap'     => '<div class="cb-course-edit-featured-image__label-wrap">',
			'label'          => sprintf(
				'<label class="cb-course-edit-featured-image__title">%s</label>',
				__( 'Featured Image', 'learnpress' )
			),
			'ai_button'      => $ai_button,
			'label_wrap_end' => '</div>',
			'edit'           => $featured_image_html,
			'wrapper_end'    => '</div>',
		];

		return Template::combine_components( $edit );
	}

	/**
	 * Render publish panel (status, visibility, publish date, danger zone) for Course Builder overview.
	 *
	 * @param CourseModel|string $course_model
	 *
	 * @return string
	 */
	public function edit_publish( $course_model ): string {
		$post_id = ! empty( $course_model ) ? absint( $course_model->get_id() ) : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		$current_status = 'draft';
		if ( $post && ! empty( $post->post_status ) ) {
			$current_status = sanitize_key( $post->post_status );
		}

		$status_for_select = in_array( $current_status, [ 'publish', 'draft', 'pending', 'future' ], true )
			? $current_status
			: 'publish';
		$current_password  = $post ? (string) ( $post->post_password ?? '' ) : '';
		$visibility        = 'private' === $current_status
			? 'private'
			: ( ! empty( $current_password ) ? 'password' : 'public' );
		$published_on      = '';

		if ( $post && ! empty( $post->post_date ) && '0000-00-00 00:00:00' !== $post->post_date ) {
			$published_on = wp_date( 'Y-m-d\TH:i', strtotime( $post->post_date ), wp_timezone() );
		}

		$has_future_publish_date = false;
		if ( $post && ! empty( $post->post_date ) && '0000-00-00 00:00:00' !== $post->post_date ) {
			$has_future_publish_date = strtotime( $post->post_date ) > current_time( 'timestamp' );
		}

		$is_scheduled_status    = 'future' === $status_for_select
			|| ( in_array( $status_for_select, [ 'draft', 'pending' ], true ) && $has_future_publish_date );
		$primary_status_value   = $is_scheduled_status ? 'future' : 'publish';
		$primary_status_label   = $is_scheduled_status
			? __( 'Scheduled', 'learnpress' )
			: __( 'Published', 'learnpress' );
		$selected_status_for_ui = in_array( $status_for_select, [ 'draft', 'pending' ], true )
			? $status_for_select
			: $primary_status_value;
		$status_options         = [
			$primary_status_value => $primary_status_label,
			'draft'               => __( 'Draft', 'learnpress' ),
			'pending'             => __( 'Pending Review', 'learnpress' ),
		];

		$publish_date_label = $has_future_publish_date
			? __( 'Scheduled for', 'learnpress' )
			: __( 'Published on', 'learnpress' );

		$status_options_html = '';
		foreach ( $status_options as $value => $label ) {
			$status_options_html .= sprintf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $selected_status_for_ui, $value, false ),
				esc_html( $label )
			);
		}

		$visibility_options_html = sprintf(
			'<option value="public" %1$s>%2$s</option><option value="private" %3$s>%4$s</option><option value="password" %5$s>%6$s</option>',
			selected( $visibility, 'public', false ),
			esc_html__( 'Public', 'learnpress' ),
			selected( $visibility, 'private', false ),
			esc_html__( 'Private', 'learnpress' ),
			selected( $visibility, 'password', false ),
			esc_html__( 'Password protected', 'learnpress' )
		);

		$edit = [
			'wrapper'          => '<div class="cb-course-edit-publish">',
			'title'            => sprintf( '<h3 class="cb-course-edit-publish__title">%s</h3>', esc_html__( 'Publish', 'learnpress' ) ),
			'status_row'       => sprintf(
				'<div class="cb-course-edit-publish__row">
                    <label for="cb-course-publish-status" class="cb-course-edit-publish__label">%1$s</label>
                    <select id="cb-course-publish-status" name="cb_course_publish_status" class="cb-course-edit-publish__control" data-publish-label="%3$s" data-future-label="%4$s" data-primary-status="%5$s">%2$s</select>
                </div>',
				esc_html__( 'Status', 'learnpress' ),
				$status_options_html,
				esc_attr__( 'Published', 'learnpress' ),
				esc_attr__( 'Scheduled', 'learnpress' ),
				esc_attr( $primary_status_value )
			),
			'visibility_row'   => sprintf(
				'<div class="cb-course-edit-publish__row">
                    <label for="cb-course-publish-visibility" class="cb-course-edit-publish__label">%1$s</label>
                    <select id="cb-course-publish-visibility" name="cb_course_publish_visibility" class="cb-course-edit-publish__control">%2$s</select>
                </div>',
				esc_html__( 'Visibility', 'learnpress' ),
				$visibility_options_html
			),
			'password_row'     => sprintf(
				'<div class="cb-course-edit-publish__row cb-course-edit-publish__password-row %1$s">
                    <label for="cb-course-publish-password" class="cb-course-edit-publish__label">%2$s</label>
                    <input type="text" id="cb-course-publish-password" name="cb_course_publish_password" class="cb-course-edit-publish__control" value="%3$s" autocomplete="new-password">
                </div>',
				'password' === $visibility ? '' : 'lp-hidden',
				esc_html__( 'Password', 'learnpress' ),
				esc_attr( $current_password )
			),
			'published_on_row' => sprintf(
				'<div class="cb-course-edit-publish__row">
                    <label for="cb-course-publish-date" id="cb-course-publish-date-label" class="cb-course-edit-publish__label">%1$s</label>
                    <input type="datetime-local" id="cb-course-publish-date" name="cb_course_publish_date" class="cb-course-edit-publish__control" value="%2$s">
                </div>',
				esc_html( $publish_date_label ),
				esc_attr( $published_on )
			),
			'wrapper_end'      => '</div>',
		];

		return Template::combine_components( $edit );
	}

	protected function html_curriculum( CourseModel $course_model ): string {
		ob_start();
		AdminEditCurriculumTemplate::instance()->edit_course_curriculum_layout(
			$course_model,
			[ 'is_course_builder' => true ]
		);
		$html_curriculum = ob_get_clean();

		return $html_curriculum
				. $this->html_curriculum_popup_templates( $course_model )
				. $this->html_curriculum_ai_templates();
	}

	protected function html_curriculum_popup_templates( CourseModel $course_model ): string {
		$popup_templates = apply_filters(
			'learn-press/course-builder/courses/curriculum/popup-templates',
			[
				'lesson' => sprintf(
					'<script type="text/template" id="%1$s"><div class="lp-builder-popup-overlay"></div><div class="lp-builder-popup lp-builder-popup--loading">%2$s</div></script>',
					esc_attr(
						sprintf(
							'lp-tmpl-builder-popup-curriculum-lesson-course-%d',
							$course_model->get_id()
						)
					),
					TemplateAJAX::load_content_via_ajax(
						[
							'id_url'                  => 'builder-popup-lesson-curriculum',
							'lesson_id'               => 0,
							'html_no_load_ajax_first' => sprintf(
								'<div class="lp-builder-popup__loader"><div class="lp-loading-circle"></div><span>%s</span></div>',
								esc_html__( 'Loading...', 'learnpress' )
							),
						],
						[
							'class'  => BuilderPopupTemplate::class,
							'method' => 'render_lesson_popup',
						]
					)
				),
				'quiz'   => sprintf(
					'<script type="text/template" id="%1$s"><div class="lp-builder-popup-overlay"></div><div class="lp-builder-popup lp-builder-popup--loading">%2$s</div></script>',
					esc_attr(
						sprintf(
							'lp-tmpl-builder-popup-curriculum-quiz-course-%d',
							$course_model->get_id()
						)
					),
					TemplateAJAX::load_content_via_ajax(
						[
							'id_url'                  => 'builder-popup-quiz-curriculum',
							'quiz_id'                 => 0,
							'html_no_load_ajax_first' => sprintf(
								'<div class="lp-builder-popup__loader"><div class="lp-loading-circle"></div><span>%s</span></div>',
								esc_html__( 'Loading...', 'learnpress' )
							),
						],
						[
							'class'  => BuilderPopupTemplate::class,
							'method' => 'render_quiz_popup',
						]
					)
				),
			],
			$course_model
		);

		return Template::combine_components( $popup_templates );
	}


	/**
	 * Add edit popup button for lesson and quiz items in Course Builder curriculum.
	 * Replace the default edit button with popup button for lesson and quiz items.
	 *
	 * @param array $section_action Array of action buttons.
	 * @param object|null $item Item data.
	 * @param PostModel|null $itemModel Item model.
	 * @param CourseModel $courseModel .
	 * @param array $context_data Context data passed from AJAX.
	 *
	 * @return array
	 * @since 4.3.0
	 * @version 1.0.2
	 *
	 */
	public function add_edit_popup_button( array $section_action, $item, $itemModel, $courseModel, $context_data = [] ): array {
		// Check if we are in Course Builder context via the flag passed in AJAX args
		$is_course_builder = ! empty( $context_data['is_course_builder'] );

		if ( ! $is_course_builder ) {
			return $section_action;
		}

		$item_id   = $item->item_id ?? 0;
		$item_type = $item->item_type ?? '';

		$popup_data_attr = '';
		$popup_type      = '';
		if ( $item_type === LP_LESSON_CPT ) {
			$popup_data_attr = sprintf( 'data-popup-lesson="%s"', $item_id );
			$popup_type      = 'lesson';
		} elseif ( $item_type === LP_QUIZ_CPT ) {
			$popup_data_attr = sprintf( 'data-popup-quiz="%s"', $item_id );
			$popup_type      = 'quiz';
		}

		$popup_type = sanitize_key(
			(string) apply_filters(
				'learn-press/course-builder/courses/curriculum/popup-item-type',
				$popup_type,
				$item_type,
				$item,
				$itemModel,
				$courseModel,
				$context_data
			)
		);
		if ( empty( $popup_type ) ) {
			return $section_action;
		}

		$popup_template_id = sprintf(
			'lp-tmpl-builder-popup-curriculum-%1$s-course-%2$d',
			$popup_type,
			$courseModel->get_id()
		);

		// Replace edit button with popup button - use lp-icon-edit-square instead of lp-icon-expand
		$section_action['edit'] = sprintf(
			'<li title="%s" class="lp-btn-edit-item-popup"
                data-course-id="%s"
                data-template="#%s"
                data-popup-type="%s"
                data-popup-id="%d"
                %s>
                <a class="lp-icon-edit-square edit-link edit-popup-link"></a>
            </li>',
			__( 'Edit in popup', 'learnpress' ),
			$courseModel->get_id(),
			esc_attr( $popup_template_id ),
			esc_attr( $popup_type ),
			$item_id,
			$popup_data_attr
		);

		return $section_action;
	}

	/**
	 * Keep supported course settings tabs in Course Builder.
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function filter_course_builder_settings_tabs( array $tabs ): array {
		$allowed_tabs = [ 'general', 'offline', 'price', 'extra', 'assessment', 'author', 'material' ];

		foreach ( array_keys( $tabs ) as $tab_key ) {
			if ( ! in_array( $tab_key, $allowed_tabs, true ) ) {
				unset( $tabs[ $tab_key ] );
			}
		}

		return apply_filters( 'learn-press/course-builder/edit-course/settings/tabs', $tabs );
	}

	/**
	 * Convert final quiz edit link to Course Builder quiz settings URL.
	 *
	 * @param string $url
	 * @param int $final_quiz_id
	 *
	 * @return string
	 */
	public function filter_course_builder_assessment_final_quiz_edit_link( string $url, int $final_quiz_id ): string {
		$final_quiz_id = absint( $final_quiz_id );
		if ( ! $final_quiz_id ) {
			return $url;
		}

		return CourseBuilder::get_tab_link( 'quizzes', $final_quiz_id, 'settings' ) . '#_lp_passing_grade';
	}
}
