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

		echo Template::combine_components( $section );
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

	public function section_question() {
		// Load edit curriculum style
		wp_enqueue_style( 'lp-edit-quiz' );

		$quiz_id    = CourseBuilder::get_post_id();
		$quiz_model = '';
		if ( $quiz_id === 'post-new' ) {
			$message = sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Quiz before add question', 'learnpress' ) );
			echo $message;
			return;
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
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-tom-select' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-learnpress' );

		$quiz_id = CourseBuilder::get_post_id();

		if ( $quiz_id === 'post-new' ) {
			$message = sprintf( '<span class="lp-message lp-message--info">%s</span>', __( 'Please save Quiz before setting quiz', 'learnpress' ) );
			echo $message;
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
		$settings = ob_get_clean();

		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__quiz-edit" data-quiz-id="%s">', $quiz_id ),
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
