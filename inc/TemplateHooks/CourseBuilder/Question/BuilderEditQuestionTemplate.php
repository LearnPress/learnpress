<?php
/**
 * Template hooks Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Question;

use Exception;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\CourseBuilder\CourseBuilderAccessPolicy;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\PostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Admin\AdminEditQuestionTemplate;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\TemplateHooks\CourseBuilder\Quiz\BuilderQuizTemplate;
use LP_Question_CURD;
use LP_Settings;
use Throwable;
use WP_User;

class BuilderEditQuestionTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Display layout edit/create question.
	 *
	 * @param array $data [ 'userModel' => UserModel, 'item_id' => int|string ]
	 *
	 * @throws Exception
	 */
	public function layout( array $data = [] ) {
		try {
			$userModel = $data['userModel'] ?? false;
			if ( ! $userModel || ! $userModel->is_instructor() ) {
				throw new Exception( __( 'You do not have permission to create or edit questions', 'learnpress' ) );
			}

			$item_id = $data['item_id'] ?? '';
			if ( empty( $item_id ) ) {
				throw new Exception( __( 'Invalid question ID', 'learnpress' ) );
			}

			if ( ! CourseBuilderAccessPolicy::can_access_tab_post( 'questions', $item_id ) ) {
				throw new Exception( __( "Sorry, you don't have permission to access this content", 'learnpress' ) );
			}

			$is_create_new = $item_id === CourseBuilder::POST_NEW;
			$questionModel = false;

			if ( ! $is_create_new ) {
				$questionModel = QuestionPostModel::find( (int) $item_id, true );
				if ( ! $questionModel ) {
					throw new Exception( __( 'Question not found', 'learnpress' ) );
				}

				if ( $questionModel->post_status === PostModel::STATUS_TRASH ) {
					throw new Exception(
						__(
							'You cannot edit this item because it is in the Trash. Please restore it and try again.',
							'learnpress'
						)
					);
				}
			}

			$data['questionModel'] = $questionModel;

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

		$questionModel        = $data['questionModel'] ?? false;
		$hide_instructor_access_admin_screen = LP_Settings::is_hide_instructor_access_admin_screen();
		$more_actions_icon    = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-more.svg' );
		$title                = $questionModel ? $questionModel->get_the_title() : __( 'Add New Question', 'learnpress' );
		$status               = $questionModel ? $questionModel->post_status : '';
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
			'status_badge'       => $questionModel && $status ? sprintf(
				'<span class="question-status %1$s">%2$s</span>',
				esc_attr( $status ),
				esc_html( $status_label )
			) : '',
			'link_edit_on_wp'    => $questionModel && ! $hide_wp_edit_link ? sprintf(
				'<a href="%1$s" class="lp-cb-admin-link" target="_blank" title="%2$s"%3$s>
					<span class="dashicons dashicons-wordpress"></span>
					<span>%2$s</span>
				</a>',
				esc_url( admin_url( 'post.php?post=' . $questionModel->get_id() . '&action=edit' ) ),
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
			'expanded_actions'   => $questionModel ? sprintf(
				'<div class="cb-header-action-expanded">
					<button type="button" class="lp-button course-action-expanded" aria-haspopup="true" aria-expanded="false" aria-label="%1$s">
						%2$s
					</button>
					<div class="cb-header-action-expanded__items">
						<div class="cb-header-action-expanded__duplicate lp-button cb-btn-duplicate-question"
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
				esc_attr__( 'Are you sure you want to duplicate this question?', 'learnpress' ),
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
			'learn-press/course-builder/questions/edit/tabs',
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

		$question_model = $data['questionModel'] ?? false;
		$question_id    = $data['item_id'] ?? ( $question_model ? $question_model->get_id() : CourseBuilder::POST_NEW );

		if ( absint( $question_id ) && ! $question_model ) {
			$question_model = QuestionPostModel::find( absint( $question_id ), true );
			if ( empty( $question_model ) ) {
				return '';
			}
		}

		$html_assigned   = $this->assigned_quiz( $question_model );
		$html_edit_title = $this->edit_title( $question_model );
		$html_permalink  = $this->edit_permalink( $question_model );
		$html_publish    = $this->edit_publish( $question_model );
		$html_edit_desc  = $this->edit_desc( $question_model );
		$section         = [
			'wrapper'             => sprintf( '<div class="cb-section__question-edit" data-question-id="%s">', $question_id ),
			'content_wrapper'     => '<div class="cb-item-edit-content">',
			'left_column'         => '<div class="cb-item-edit-column cb-item-edit-column--left">',
			'edit_title'          => $html_edit_title,
			'assigned_quiz'       => $html_assigned,
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

	public function html_tab_settings( array $data = [] ): string {
		wp_enqueue_style( 'lp-edit-question' );

		$question_model = $data['questionModel'] ?? false;
		$question_id    = $data['item_id'] ?? ( $question_model ? $question_model->get_id() : CourseBuilder::POST_NEW );

		if ( $question_id === CourseBuilder::POST_NEW || absint( $question_id ) <= 0 ) {
			return sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Question before setting question', 'learnpress' ) );
		}

		if ( ! $question_model ) {
			$question_model = QuestionPostModel::find( absint( $question_id ), true );
			if ( empty( $question_model ) ) {
				return '';
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
					$quiz_link    = BuilderQuizTemplate::instance()->get_link_edit( $quiz_id );
					$quiz_htmls[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( $quiz_link ),
						esc_html( $quiz_title )
					);
				}
			}

			if ( ! empty( $quiz_htmls ) ) {
				$assigned = implode( ', ', $quiz_htmls );
			}
		}

		$html_quizzes = sprintf(
			'<div class="cb-item-edit-assigned question-assigned-quizzes"><span class="label">%s</span> %s</div>',
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
				'content_style' => "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; font-size: 14px; line-height: 1.6; color: #1e1e1e; }",
				'toolbar1'      => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
				'toolbar2'      => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
			),
			'quicktags'     => true,
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

	public function edit_permalink( $question_model ): string {
		$post_id = ! empty( $question_model ) ? absint( $question_model->get_id() ) : 0;

		if ( ! $post_id ) {
			return Template::combine_components(
				[
					'wrapper'     => '<div class="cb-item-edit-permalink">',
					'label'       => sprintf( '<span class="cb-item-edit-permalink__label">%s</span>', __( 'Permalink', 'learnpress' ) ),
					'content'     => sprintf(
						'<span class="cb-item-edit-permalink__placeholder">%s</span>',
						__( 'Permalink will be available after saving.', 'learnpress' )
					),
					'wrapper_end' => '</div>',
				]
			);
		}

		$post         = get_post( $post_id );
		$post_name    = $post ? $post->post_name : '';
		$full_url     = urldecode( (string) get_permalink( $post_id ) );
		$display_data = $this->get_question_display_permalink_data( $post_id, (string) $post_name );
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

		if ( empty( $full_url ) ) {
			return Template::combine_components(
				[
					'wrapper'     => '<div class="cb-item-edit-permalink">',
					'label'       => sprintf( '<span class="cb-item-edit-permalink__label">%s</span>', __( 'Permalink', 'learnpress' ) ),
					'content'     => sprintf(
						'<span class="cb-item-edit-permalink__placeholder">%s</span>',
						__( 'Permalink is not available for this question.', 'learnpress' )
					),
					'wrapper_end' => '</div>',
				]
			);
		}

		$base_url = $display_url;
		if (
			! empty( $editor_slug ) &&
			false === strpos( $display_url, '?p=' ) &&
			false === strpos( $display_url, '&p=' ) &&
			false === strpos( $display_url, '?lp_question=' ) &&
			false === strpos( $display_url, '&lp_question=' )
		) {
			$base_url = trailingslashit( preg_replace( '/' . preg_quote( $editor_slug, '/' ) . '\/?$/', '', $display_url ) );
		}

		$state_a = sprintf(
			'<span class="cb-item-edit-permalink__label">%s</span>
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
					<input type="text" name="question_permalink" id="question_permalink" value="%s" class="cb-permalink-slug-input" placeholder="%s">
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

		$view = [
			'wrapper'     => '<div class="cb-item-edit-permalink cb-course-edit-permalink">',
			'state_a'     => $state_a,
			'state_b'     => $state_b,
			'hidden_base' => $hidden_base,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $view );
	}

	/**
	 * Get permalink display URL in publish-style format for editing slug.
	 *
	 * @param int $post_id
	 * @param string $post_name
	 *
	 * @return array<string, string>
	 */
	private function get_question_display_permalink_data( int $post_id, string $post_name = '' ): array {
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

		return [
			'url'  => urldecode( $display_url ),
			'slug' => urldecode( (string) $sample_slug ),
		];
	}

	public function edit_publish( $question_model ): string {
		$post_id        = ! empty( $question_model ) ? absint( $question_model->get_id() ) : 0;
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
					<label for="cb-question-publish-status" class="cb-item-edit-publish__label">%1$s</label>
					<select id="cb-question-publish-status" name="cb_question_publish_status" class="cb-item-edit-publish__control">%2$s</select>
				</div>',
				esc_html__( 'Status', 'learnpress' ),
				$status_options_html
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $publish );
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
