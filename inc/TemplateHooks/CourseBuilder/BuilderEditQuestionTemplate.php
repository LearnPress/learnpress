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

	public function section_overview( $question_id = null ) {
		wp_enqueue_script( 'lp-course-builder' );

		$question_id    = empty( $question_id ) ? CourseBuilder::get_item_id() : $question_id;
		$question_model = '';

		if ( $question_id === CourseBuilder::POST_NEW ) {
			$question_model = '';
		}

		if ( absint( $question_id ) ) {
			$question_model = QuestionPostModel::find( $question_id, true );
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

		echo Template::combine_components( $section );
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

	public function section_settings( $question_id = null ) {
		wp_enqueue_style( 'lp-edit-question' );

		$question_id    = empty( $question_id ) ? CourseBuilder::get_item_id() : $question_id;
		$question_model = '';

		if ( $question_id === CourseBuilder::POST_NEW || absint( $question_id ) <= 0 ) {
			$message = sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Question before setting question', 'learnpress' ) );
			echo $message;
			return;
		}

		$question_model = QuestionPostModel::find( absint( $question_id ), true );
		if ( empty( $question_model ) ) {
			return;
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
