<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use Exception;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Admin\AI\AdminEditCourseCurriculumWithAITemplate;
use LearnPress\TemplateHooks\Admin\AI\AdminEditWithAITemplate;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LP_Settings;
use Throwable;

class BuilderEditCourseTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/course/edit/layout', [ $this, 'layout' ] );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		/*add_action( 'learn-press/course-builder/courses/overview/layout', [ $this, 'section_overview' ] );
		add_action( 'learn-press/course-builder/courses/curriculum/layout', [ $this, 'section_curriculum' ] );
		add_action( 'learn-press/course-builder/courses/settings/layout', [ $this, 'section_settings' ] );*/

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

		$is_create_new = $item_id === CourseBuilder::POST_NEW;
		$courseModel   = false;

		if ( ! $is_create_new ) {
			$courseModel = CourseModel::find( (int) $item_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
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
	}

	/**
	 * HTML header
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_header( array $data = [] ): string {
		$userModel            = $data['userModel'] ?? false;
		$courseModel          = $data['courseModel'] ?? false;
		$enable_wp_admin_mode = LP_Settings::get_option( 'enable_cb_admin_mode', 'no' ) === 'yes';
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
			'link_edit_on_wp'    => ( $enable_wp_admin_mode && user_can( $userModel->get_id(), UserModel::ROLE_ADMINISTRATOR ) )
									&& ( $courseModel && $courseModel->get_status() === PostModel::STATUS_TRASH ) ? sprintf(
										'<a href="%1$s" class="lp-cb-admin-link" target="_blank" title="%2$s">
					<span class="dashicons dashicons-wordpress"></span>
					<span>%2$s</span>
				</a>',
										esc_url( admin_url( "post.php?post={$courseModel->get_id()}&action=edit" ) ),
										esc_attr__( 'Edit with WordPress', 'learnpress' ),
									) : '',
			'header_left_end'    => '</div>',
			'header_actions'     => '<div class="lp-cb-header__actions">',
			'preview_btn'        => $courseModel && $courseModel->get_status() !== PostModel::STATUS_TRASH ? sprintf(
				'<a href="%1$s" class="cb-button cb-btn-preview cb-btn-secondary" target="_blank">%2$s</a>',
				esc_url( get_permalink( $courseModel->get_id() ) ),
				esc_html__( 'Preview', 'learnpress' )
			) : '',
			'dropdown_wrap'      => '<div class="cb-header-actions-dropdown cb-header-actions-dropdown--single">',
			'update_btn'         => sprintf(
				'<div class="cb-btn-update cb-btn-primary cb-btn-main-action"
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
					<button type="button" class="course-action-expanded" aria-haspopup="true" aria-expanded="false" aria-label="%1$s">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
						</svg>
					</button>
					<div class="cb-header-action-expanded__items">
						%2$s
						<div class="cb-header-action-expanded__trash cb-btn-trash">
							<span class="dashicons dashicons-trash"></span>
							%3$s
						</div>
					</div>
				</div>',
				esc_attr__( 'More actions', 'learnpress' ),
				! empty( $header_expanded_duplicate_class ) ? sprintf(
					'<div class="cb-header-action-expanded__duplicate %1$s" data-title="%2$s" data-content="%3$s">
						<span class="dashicons dashicons-admin-page"></span>
						%4$s
					</div>',
					esc_attr( $header_expanded_duplicate_class ),
					esc_attr__( 'Are you sure?', 'learnpress' ),
					esc_attr__( 'Are you sure you want to duplicate this question?', 'learnpress' ),
					esc_html__( 'Duplicate', 'learnpress' )
				) : '',
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
		$course_id = CourseBuilder::get_item_id();

		if ( $course_id === CourseBuilder::POST_NEW ) {
			$course_model = '';
		}

		if ( absint( $course_id ) ) {
			$course_model = CourseModel::find( $course_id, true );
			if ( empty( $course_model ) ) {
				return;
			}
		}

		$html_edit_title     = $this->edit_title( $course_model );
		$html_edit_permalink = $this->edit_permalink( $course_model );
		$html_edit_features  = $this->edit_featured_image( $course_model );
		$html_edit_publish   = $this->edit_publish( $course_model );
		$html_edit_desc      = $this->edit_desc( $course_model );
		$html_edit_cat       = $this->edit_categories( $course_model );
		$html_edit_tags      = $this->edit_tags( $course_model );

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

	public function html_tab_curriculum( array $data = [] ) {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-admin-learnpress' );

		$course_id = CourseBuilder::get_item_id();

		if ( $course_id === CourseBuilder::POST_NEW ) {
			$message = sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Course before add Section' ) );
			return $message;
		}

		if ( absint( $course_id ) ) {
			$course_model = CourseModel::find( $course_id, true );
			if ( empty( $course_model ) ) {
				return;
			}
		}

		// Load curriculum with is_course_builder flag
		ob_start();
		AdminEditCurriculumTemplate::instance()->edit_course_curriculum_layout( $course_model );
		$html_curriculum = ob_get_clean();

		return $html_curriculum . $this->html_curriculum_ai_templates();
	}

	public function html_tab_settings( array $data = [] ) {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-tom-select' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-learnpress' );

		$course_id = CourseBuilder::get_item_id();

		if ( $course_id === CourseBuilder::POST_NEW ) {
			$message = sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Course before setting course' ) );
			return $message;
		}

		if ( absint( $course_id ) ) {
			$course_model = CourseModel::find( $course_id, true );
			if ( empty( $course_model ) ) {
				return;
			}
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
		$metabox->output( $course_model );
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
			$featured_image_html .= '<span class="cb-featured-image-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.29095 14.5488C3.29099 14.8637 3.33747 15.1785 3.44915 15.5527L3.48138 15.6592C3.95542 17.0735 5.26094 18.0234 6.72845 18.0234H20.0361L19.2099 20.6787C18.9761 21.5815 18.1423 22.1942 17.2294 22.1943C17.0513 22.1942 16.8735 22.1714 16.7011 22.126L2.5263 18.2881C1.43276 17.9832 0.78131 16.8383 1.06732 15.7344L3.29095 8.22949V14.5488ZM20.7079 1.80469C21.9711 1.80474 22.9998 2.84505 22.9999 4.12207V14.3164C22.9999 15.5936 21.9712 16.6337 20.7079 16.6338H6.95794C5.69489 16.6338 4.66595 15.5936 4.66595 14.3164V4.12207C4.66604 2.84502 5.69495 1.80469 6.95794 1.80469H20.7079ZM6.95794 3.65918C6.70507 3.65918 6.49903 3.86625 6.49896 4.12207V12.8701L9.02923 10.3135C9.65534 9.67964 10.6757 9.67964 11.3027 10.3135L12.412 11.4316L15.8163 7.2998C16.1206 6.93103 16.5663 6.71857 17.041 6.71582C17.5185 6.72681 17.9633 6.92104 18.2704 7.28516L21.166 10.7012V4.12207C21.1659 3.8663 20.961 3.65923 20.7079 3.65918H6.95794ZM9.24896 4.58496C10.2601 4.58496 11.0829 5.4172 11.0829 6.43945C11.0827 7.4615 10.26 8.29297 9.24896 8.29297C8.23818 8.29274 7.4162 7.46132 7.41595 6.43945C7.41595 5.41738 8.23798 4.58519 9.24896 4.58496Z" fill="#CFCFCF"/></svg></span>';
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
				'<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_5385_4628)"><path d="M11.9 1.66699C12.2498 1.66708 12.5907 1.77723 12.8744 1.98183C13.1581 2.18643 13.3703 2.47512 13.4808 2.80699L13.9333 4.16699H16.6667C16.8877 4.16699 17.0996 4.25479 17.2559 4.41107C17.4122 4.56735 17.5 4.77931 17.5 5.00033C17.5 5.22134 17.4122 5.4333 17.2559 5.58958C17.0996 5.74586 16.8877 5.83366 16.6667 5.83366L16.6642 5.89283L15.9417 16.012C15.8966 16.6425 15.6143 17.2325 15.1517 17.6633C14.6891 18.0941 14.0805 18.3336 13.4483 18.3337H6.55167C5.91955 18.3336 5.31092 18.0941 4.84831 17.6633C4.38569 17.2325 4.10342 16.6425 4.05833 16.012L3.33583 5.89199C3.33433 5.87258 3.33349 5.85313 3.33333 5.83366C3.11232 5.83366 2.90036 5.74586 2.74408 5.58958C2.5878 5.4333 2.5 5.22134 2.5 5.00033C2.5 4.77931 2.5878 4.56735 2.74408 4.41107C2.90036 4.25479 3.11232 4.16699 3.33333 4.16699H6.06667L6.51917 2.80699C6.62975 2.47498 6.84203 2.1862 7.12592 1.98159C7.4098 1.77697 7.75089 1.66691 8.10083 1.66699H11.9ZM14.9975 5.83366H5.0025L5.72083 15.8928C5.73579 16.103 5.82981 16.2997 5.98397 16.4433C6.13812 16.587 6.34096 16.6669 6.55167 16.667H13.4483C13.659 16.6669 13.8619 16.587 14.016 16.4433C14.1702 16.2997 14.2642 16.103 14.2792 15.8928L14.9975 5.83366ZM8.33333 8.33366C8.53744 8.33369 8.73445 8.40862 8.88698 8.54425C9.03951 8.67989 9.13695 8.86678 9.16083 9.06949L9.16667 9.16699V13.3337C9.16643 13.5461 9.0851 13.7504 8.93929 13.9048C8.79349 14.0592 8.59421 14.1522 8.38217 14.1646C8.17014 14.1771 7.96135 14.1081 7.79847 13.9718C7.6356 13.8354 7.53092 13.6421 7.50583 13.4312L7.5 13.3337V9.16699C7.5 8.94598 7.5878 8.73402 7.74408 8.57774C7.90036 8.42146 8.11232 8.33366 8.33333 8.33366ZM11.6667 8.33366C11.8877 8.33366 12.0996 8.42146 12.2559 8.57774C12.4122 8.73402 12.5 8.94598 12.5 9.16699V13.3337C12.5 13.5547 12.4122 13.7666 12.2559 13.9229C12.0996 14.0792 11.8877 14.167 11.6667 14.167C11.4457 14.167 11.2337 14.0792 11.0774 13.9229C10.9211 13.7666 10.8333 13.5547 10.8333 13.3337V9.16699C10.8333 8.94598 10.9211 8.73402 11.0774 8.57774C11.2337 8.42146 11.4457 8.33366 11.6667 8.33366ZM11.9 3.33366H8.1L7.8225 4.16699H12.1775L11.9 3.33366Z" fill="currentColor"/></g><defs><clipPath id="clip0_5385_4628"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>'
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

	/**
	 * Add edit popup button for lesson and quiz items in Course Builder curriculum.
	 * Replace the default edit button with popup button for lesson and quiz items.
	 *
	 * @since 4.3.0
	 * @version 1.0.2
	 *
	 * @param array $section_action Array of action buttons.
	 * @param object|null $item Item data.
	 * @param PostModel|null $itemModel Item model.
	 * @param CourseModel $courseModel.
	 * @param array $context_data Context data passed from AJAX.
	 *
	 * @return array
	 */
	public function add_edit_popup_button( array $section_action, $item, $itemModel, $courseModel, $context_data = [] ): array {
		// Check if we are in Course Builder context via the flag passed in AJAX args
		$is_course_builder = ! empty( $context_data['is_course_builder'] );

		if ( ! $is_course_builder ) {
			return $section_action;
		}

		$item_id   = $item->item_id ?? 0;
		$item_type = $item->item_type ?? '';

		// Only replace edit button for lesson and quiz items
		if ( ! in_array( $item_type, [ LP_LESSON_CPT, LP_QUIZ_CPT ], true ) ) {
			return $section_action;
		}

		// Build popup data attribute based on item type
		$popup_data_attr = '';
		if ( $item_type === LP_LESSON_CPT ) {
			$popup_data_attr = sprintf( 'data-popup-lesson="%s"', $item_id );
		} elseif ( $item_type === LP_QUIZ_CPT ) {
			$popup_data_attr = sprintf( 'data-popup-quiz="%s"', $item_id );
		}

		// Replace edit button with popup button - use lp-icon-edit-square instead of lp-icon-expand
		$section_action['edit'] = sprintf(
			'<li title="%s" class="lp-btn-edit-item-popup"
                data-item-id="%s"
                data-item-type="%s"
                data-course-id="%s"
                %s>
                <a class="lp-icon-edit-square edit-popup-link"></a>
            </li>',
			__( 'Edit in popup', 'learnpress' ),
			$item_id,
			$item_type,
			$courseModel->get_id(),
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
	 * @param int    $final_quiz_id
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
