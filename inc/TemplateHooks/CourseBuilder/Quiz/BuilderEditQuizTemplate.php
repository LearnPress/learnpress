<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Quiz;

use Exception;
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\CourseBuilder\Course\BuilderCourseTemplate;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\PostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\TemplateHooks\Admin\AdminEditQizTemplate;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Settings;
use Throwable;
use WP_User;

class BuilderEditQuizTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Display layout edit/create quiz.
	 *
	 * @param array $data [ 'userModel' => UserModel, 'item_id' => int|string ]
	 *
	 * @throws Exception
	 */
	public function layout( array $data = [] ) {
		try {
			$userModel = $data['userModel'] ?? false;
			if ( ! $userModel || ! $userModel->is_instructor() ) {
				throw new Exception( __( 'You do not have permission to create or edit quizzes', 'learnpress' ) );
			}

			$item_id = $data['item_id'] ?? '';
			if ( empty( $item_id ) ) {
				throw new Exception( __( 'Invalid quiz ID', 'learnpress' ) );
			}

			if ( ! CourseBuilderAccessPolicy::can_access_tab_post( 'quizzes', $item_id ) ) {
				throw new Exception( __( "Sorry, you don't have permission to access this content", 'learnpress' ) );
			}

			$is_create_new = $item_id === CourseBuilder::POST_NEW;
			$quizModel     = false;

			if ( ! $is_create_new ) {
				$quizModel = QuizPostModel::find( (int) $item_id, true );
				if ( ! $quizModel ) {
					throw new Exception( __( 'Quiz not found', 'learnpress' ) );
				}

				if ( $quizModel->post_status === PostModel::STATUS_TRASH ) {
					throw new Exception(
						__(
							'You cannot edit this item because it is in the Trash. Please restore it and try again.',
							'learnpress'
						)
					);
				}
			}

			$data['quizModel'] = $quizModel;

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

		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), 'error' );
		}
	}

	public function html_header( array $data = [] ): string {
		$userModel            = $data['userModel'] ?? false;
		if ( ! $userModel instanceof UserModel ) {
			return '';
		}

		$quizModel            = $data['quizModel'] ?? false;
		$hide_instructor_access_admin_screen = LP_Settings::is_hide_instructor_access_admin_screen();
		$more_actions_icon    = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-more.svg' );
		$title                = $quizModel ? $quizModel->get_the_title() : __( 'Add New Quiz', 'learnpress' );
		$status               = $quizModel ? $quizModel->post_status : '';
		$status_label         = 'future' === $status ? __( 'scheduled', 'learnpress' ) : $status;
		$main_action_status   = in_array( $status, [ 'publish', 'draft', 'pending', 'future', 'private' ], true ) ? $status : 'publish';
		$wp_user              = new WP_User( $userModel );
		$hide_wp_edit_link    = $hide_instructor_access_admin_screen && user_can( $wp_user, UserModel::ROLE_INSTRUCTOR );

		$section = [
			'header_wrap'        => '<div class="lp-cb-header">',
			'header_left'        => '<div class="lp-cb-header__left">',
			'title'              => sprintf(
				'<h1 class="lp-cb-header__title">%s</h1>',
				esc_html( $title )
			),
			'status_badge'       => $quizModel && $status ? sprintf(
				'<span class="quiz-status %1$s">%2$s</span>',
				esc_attr( $status ),
				esc_html( $status_label )
			) : '',
			'link_edit_on_wp'    => $quizModel && ! $hide_wp_edit_link ? sprintf(
				'<a href="%1$s" class="lp-cb-admin-link" target="_blank" title="%2$s"%3$s>
					<span class="dashicons dashicons-wordpress"></span>
					<span>%2$s</span>
				</a>',
				esc_url( admin_url( 'post.php?post=' . $quizModel->get_id() . '&action=edit' ) ),
				esc_attr__( 'Edit with WordPress', 'learnpress' ),
				'trash' === $status ? ' style="display:none"' : ''
			) : '',
			'header_left_end'    => '</div>',
			'header_actions'     => '<div class="lp-cb-header__actions">',
			'dropdown_wrap'      => '<div class="cb-header-actions-dropdown cb-header-actions-dropdown--single">',
			'update_btn'         => sprintf(
				'<div class="cb-btn-update lp-button cb-btn-primary cb-btn-main-action"
					data-status="%1$s"
					data-title-update="%2$s"
					data-title-publish="%3$s"
					data-title-draft="%4$s">%5$s</div>',
				esc_attr( $main_action_status ),
				esc_attr__( 'Update', 'learnpress' ),
				esc_attr__( 'Publish', 'learnpress' ),
				esc_attr__( 'Save Draft', 'learnpress' ),
				esc_html__( 'Update', 'learnpress' )
			),
			'dropdown_wrap_end'  => '</div>',
			'expanded_actions'   => $quizModel ? sprintf(
				'<div class="cb-header-action-expanded">
					<button type="button" class="lp-button course-action-expanded" aria-haspopup="true" aria-expanded="false" aria-label="%1$s">
						%2$s
					</button>
					<div class="cb-header-action-expanded__items">
						<div class="cb-header-action-expanded__duplicate lp-button cb-btn-duplicate-quiz"
							data-title="%3$s"
							data-content="%4$s">
							<span class="dashicons dashicons-admin-page"></span>
							%5$s
						</div>
						<div class="cb-header-action-expanded__trash lp-button cb-btn-trash">
							<span class="dashicons dashicons-trash"></span>
							%6$s
						</div>
					</div>
				</div>',
				esc_attr__( 'More actions', 'learnpress' ),
				$more_actions_icon,
				esc_attr__( 'Are you sure?', 'learnpress' ),
				esc_attr__( 'Are you sure you want to duplicate this quiz?', 'learnpress' ),
				esc_html__( 'Duplicate', 'learnpress' ),
				esc_html__( 'Move to Trash', 'learnpress' )
			) : '',
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
			'learn-press/course-builder/quizzes/edit/tabs',
			[
				'overview' => [
					'title' => esc_html__( 'Overview', 'learnpress' ),
					'html'  => $this->html_tab_overview( $data ),
				],
				'question' => [
					'title' => esc_html__( 'Question', 'learnpress' ),
					'html'  => $this->html_tab_question( $data ),
				],
				'settings' => [
					'title' => esc_html__( 'Settings', 'learnpress' ),
					'html'  => $this->html_tab_settings( $data ),
				],
			],
			$data
		);

		$tab_sections = [];
		foreach ( $tabs as $key => $tab ) {
			$tab_sections[ $key ] = $tab['slug'] ?? $key;
		}

		$tab_active = (string) reset( $tab_sections );
		if ( isset( $_GET['tab'] ) ) {
			$requested_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			if ( in_array( $requested_tab, $tab_sections, true ) ) {
				$tab_active = $requested_tab;
			}
		}

		foreach ( $tabs as $key => $tab ) {
			$tab_section = $tab_sections[ $key ];
			$is_active   = $tab_section === $tab_active;

			$section_tab['tabs'] .= sprintf(
				'<a href="#" class="lp-cb-tabs__item %s" data-tab-section="%s">%s</a>',
				$is_active ? 'is-active' : '',
				esc_attr( $tab_section ),
				esc_html( $tab['title'] ?? '' )
			);

			/**
			 * @uses html_tab_overview
			 * @uses html_tab_question
			 * @uses html_tab_settings
			 */
			$section_content['content'] .= sprintf(
				'<div class="lp-cb-tab-panel %s" data-section="%s">%s</div>',
				$is_active ? '' : 'lp-hidden',
				esc_attr( $tab_section ),
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

		$quiz_model = $data['quizModel'] ?? false;
		$quiz_id    = $data['item_id'] ?? ( $quiz_model ? $quiz_model->get_id() : CourseBuilder::POST_NEW );

		if ( absint( $quiz_id ) && ! $quiz_model ) {
			$quiz_model = QuizPostModel::find( absint( $quiz_id ), true );
			if ( empty( $quiz_model ) ) {
				return '';
			}
		}

		$html_assigned   = $this->assigned_course( $quiz_model );
		$html_edit_title = $this->edit_title( $quiz_model );
		$html_permalink  = $this->edit_permalink( $quiz_model );
		$html_publish    = $this->edit_publish( $quiz_model );
		$html_edit_desc  = $this->edit_desc( $quiz_model );
		$section         = [
			'wrapper'             => sprintf( '<div class="cb-section__quiz-edit" data-quiz-id="%s">', $quiz_id ),
			'content_wrapper'     => '<div class="cb-item-edit-content">',
			'left_column'         => '<div class="cb-item-edit-column cb-item-edit-column--left">',
			'edit_title'          => $html_edit_title,
			'assigned_course'     => $html_assigned,
			'edit_permalink'      => $html_permalink,
			'edit_publish'        => $html_publish,
			'left_column_end'     => '</div>',
			'right_column'        => '<div class="cb-item-edit-column cb-item-edit-column--right">',
			'edit_desc'           => $html_edit_desc,
			'right_column_end'    => '</div>',
			'content_wrapper_end' => '</div>',
			'wrapper_end'         => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_tab_question( array $data = [] ): string {
		wp_enqueue_style( 'lp-edit-quiz' );

		$quiz_model = $data['quizModel'] ?? false;
		$quiz_id    = $data['item_id'] ?? ( $quiz_model ? $quiz_model->get_id() : CourseBuilder::POST_NEW );

		if ( $quiz_id === CourseBuilder::POST_NEW || absint( $quiz_id ) <= 0 ) {
			return sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Quiz before add question', 'learnpress' ) );
		}

		if ( ! $quiz_model ) {
			$quiz_model = QuizPostModel::find( absint( $quiz_id ), true );
			if ( empty( $quiz_model ) ) {
				return '';
			}
		}

		$args      = [
			'id_url'  => 'edit-quiz',
			'quiz_id' => $quiz_model->ID,
		];
		$call_back = [
			'class'  => AdminEditQizTemplate::class,
			'method' => 'render_edit_quiz',
		];

		return TemplateAJAX::load_content_via_ajax( $args, $call_back );
	}

	public function html_tab_settings( array $data = [] ): string {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-tom-select' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-learnpress' );

		$quiz_model = $data['quizModel'] ?? false;
		$quiz_id    = $data['item_id'] ?? ( $quiz_model ? $quiz_model->get_id() : CourseBuilder::POST_NEW );

		if ( $quiz_id === CourseBuilder::POST_NEW || absint( $quiz_id ) <= 0 ) {
			return sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Quiz before setting quiz', 'learnpress' ) );
		}

		if ( ! $quiz_model ) {
			$quiz_model = QuizPostModel::find( absint( $quiz_id ), true );
			if ( empty( $quiz_model ) ) {
				return '';
			}
		}

		if ( ! class_exists( 'LP_Meta_Box_Quiz' ) ) {
			require_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/quiz/settings.php';
		}

		$metabox = new \LP_Meta_Box_Quiz();
		ob_start();
		$metabox->output( $quiz_model );
		$settings = ob_get_clean();

		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__quiz-edit" data-quiz-id="%s">', $quiz_id ),
			'form_setting'     => '<form name="lp-form-setting-quiz" class="lp-form-setting-quiz" method="post" enctype="multipart/form-data">',
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
			'<div class="cb-item-edit-assigned quiz-assigned-courses"><span class="label">%s</span> %s</div>',
			__( 'Assigned', 'learnpress' ),
			$assigned
		);

		return $html_courses;
	}


	public function edit_title( $quiz_model ) {
		$title = ! empty( $quiz_model ) ? $quiz_model->get_the_title() : '';
		$edit  = [
			'wrapper'     => '<div class="cb-quiz-edit-title">',
			'label'       => sprintf( '<label for="title" class="cb-quiz-edit-title__label">%s</label>', __( 'Title', 'learnpress' ) ),
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
				'content_style' => "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; font-size: 14px; line-height: 1.6; color: #1e1e1e; }",
				'toolbar1'      => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
				'toolbar2'      => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
			),
			'quicktags'     => true,
		);

		$edit = [
			'wrapper'     => '<div class="cb-quiz-edit-desc">',
			'label'       => sprintf( '<label for="quiz_description" class="cb-quiz-edit-desc__label">%s</label>', __( 'Description', 'learnpress' ) ),
			'edit'        => AdminTemplate::editor_tinymce(
				$desc,
				$editor_id,
				$editor_settings
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_permalink( $quiz_model ): string {
		$post_id           = ! empty( $quiz_model ) ? absint( $quiz_model->get_id() ) : 0;
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
					<input type="text" name="quiz_permalink" id="quiz_permalink" value="%s" class="cb-permalink-slug-input" placeholder="%s">
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

	public function edit_publish( $quiz_model ): string {
		$post_id        = ! empty( $quiz_model ) ? absint( $quiz_model->get_id() ) : 0;
		$post           = $post_id ? get_post( $post_id ) : null;
		$current_status = $post && ! empty( $post->post_status ) ? sanitize_key( $post->post_status ) : 'draft';
		$status_value   = 'draft' === $current_status ? 'draft' : 'publish';

		$status_options_html = sprintf(
			'<option value="publish" %1$s>%2$s</option><option value="draft" %3$s>%4$s</option>',
			selected( $status_value, 'publish', false ),
			esc_html__( 'Published', 'learnpress' ),
			selected( $status_value, 'draft', false ),
			esc_html__( 'Draft', 'learnpress' )
		);

		$publish = [
			'wrapper'     => '<div class="cb-item-edit-publish">',
			'title'       => sprintf( '<h3 class="cb-item-edit-publish__title">%s</h3>', esc_html__( 'Publish', 'learnpress' ) ),
			'status_row'  => sprintf(
				'<div class="cb-item-edit-publish__row">
					<label for="cb-quiz-publish-status" class="cb-item-edit-publish__label">%1$s</label>
					<select id="cb-quiz-publish-status" name="cb_quiz_publish_status" class="cb-item-edit-publish__control">%2$s</select>
				</div>',
				esc_html__( 'Status', 'learnpress' ),
				$status_options_html
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $publish );
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
