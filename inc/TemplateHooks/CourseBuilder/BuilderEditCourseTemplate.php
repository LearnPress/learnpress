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
use LearnPress\Models\CourseModel;
use LearnPress\Models\ListCourseCategories;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

class BuilderEditCourseTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'learn-press/course-builder/courses/overview/layout', [ $this, 'section_overview' ] );
		add_action( 'learn-press/course-builder/courses/curriculum/layout', [ $this, 'section_curriculum' ] );
		add_action( 'learn-press/course-builder/courses/settings/layout', [ $this, 'section_settings' ] );

		// Register filter for adding edit popup button in Course Builder curriculum
		add_filter( 'learn-press/admin/curriculum/section-item/actions', [ $this, 'add_edit_popup_button' ], 10, 5 );
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
		$course_id = CourseBuilder::get_post_id();

		if ( $course_id === 'post-new' ) {
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
			'wrapper_end'            => '</div>',
		];

		echo Template::combine_components( $section );
	}

	public function edit_title( $course_model ) {
		$title      = ! empty( $course_model ) ? $course_model->get_title() : '';
		$char_count = mb_strlen( wp_strip_all_tags( $title ) );
		$edit       = [
			'wrapper'        => '<div class="cb-course-edit-title">',
			'label_wrap'     => '<div class="cb-course-edit-title__label-wrap">',
			'label'          => sprintf( '<label for="title" class="cb-course-edit-title__label">%s <span class="required">*</span></label>', __( 'Course Title', 'learnpress' ) ),
			'char_count'     => sprintf( '<span class="cb-course-edit-title__char-count">%s</span>', sprintf( __( '%d characters', 'learnpress' ), $char_count ) ),
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
		if ( empty( $post_id ) || $post_id === 'post-new' ) {
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

		$full_url = $base_url . $post_name;

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
			esc_html( $full_url ),
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
			esc_attr( $post_name ),
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

	public function edit_desc( $course_model ) {
		$desc            = ! empty( $course_model ) ? $course_model->get_description() : '';
		$word_count      = str_word_count( wp_strip_all_tags( $desc ) );
		$editor_id       = 'course_description_editor';
		$editor_settings = array(
			'textarea_name' => 'course_description',
			'textarea_rows' => 10,
			'teeny'         => false,
			'media_buttons' => true,
			'tinymce'       => array(
				'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
				'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
			),
			'quicktags'     => true,
		);

		$edit = [
			'wrapper'        => '<div class="cb-course-edit-desc">',
			'label_wrap'     => '<div class="cb-course-edit-desc__label-wrap">',
			'label'          => sprintf( '<label for="course_description" class="cb-course-edit-desc__label">%s</label>', __( 'Description', 'learnpress' ) ),
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

		$edit = [
			'wrapper'     => '<div class="cb-course-edit-categories__wrapper">',
			'label'       => sprintf( '<label class="cb-course-edit-categories__label">%s</label>', __( 'Categories', 'learnpress' ) ),
			'content'     => $html_meta_box,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_tags( $course_model ) {
		$course_terms = ! empty( $course_model ) ? $course_model->get_tags() : [];
		$btn_add_cat  = sprintf( '<button class="cb-course-edit-tag__btn-add-new">%s</button>', __( '+ Add A New Course Tag', 'learnpress' ) );
		$btn_cancel   = sprintf( '<button class="cb-course-edit-tag__btn-cancel"  style="display:none;">%s</button>', __( 'Cancel', 'learnpress' ) );
		$tags         = get_terms(
			[
				'taxonomy'   => LP_COURSE_TAXONOMY_TAG,
				'hide_empty' => false,
				'count'      => false,
			]
		);

		$selected_tag_ids = array_map(
			function ( $term ) {
				return (int) $term->term_id;
			},
			$course_terms
		);

		$html_checkbox = '';

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$tag_id         = $tag->term_id;
				$tag_name       = $tag->name;
				$is_checked     = in_array( (int) $tag_id, $selected_tag_ids, true );
				$html_checkbox .= $this->input_checkbox_tag_item( $tag_id, $tag_name, $is_checked );
			}
		}

		$edit = [
			'wrapper'                  => '<div class="cb-course-edit-tags__wrapper">',
			'label'                    => sprintf( '<label for="title" class="cb-course-edit-tags__label">%s</label>', __( 'Tags', 'learnpress' ) ),
			'wrapper_checkbox'         => '<div class="cb-course-edit-tags__checkbox-wrapper">',
			'checkbox'                 => $html_checkbox,
			'wrapper_checkbox_end'     => '</div>',
			'btn_add_new'              => $btn_add_cat,
			'btn_cancel'               => $btn_cancel,
			'form_add_tag_wrapper'     => '<div class="cb-course-edit-terms__form-add-tag" style="display:none;">',
			'input'                    => '<input type="text" class="cb-course-edit-tags__input" placeholder="' . esc_attr__( 'Enter Tag Name', 'learnpress' ) . '"/>',
			'button'                   => '<button type="button" class="cb-course-edit-tags__btn-save">' . esc_html__( 'Add', 'learnpress' ) . '</button>',
			'form_add_tag_wrapper_end' => '</div>',
			'wrapper_end'              => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function input_checkbox_category_item( $term_id, $term_name, $is_checked ) {
		$html  = '<div class="cb-course-edit-categories__checkbox">';
		$html .= sprintf( '<input type="checkbox" name="course_categories[]" value="%s" id="course_category_%s" %s>', $term_id, $term_id, checked( $is_checked, true, false ) );
		$html .= sprintf( '<label for="course_category_%s">%s</label>', $term_id, $term_name );
		$html .= '</div>';

		return $html;
	}

	public function input_checkbox_tag_item( $term_id, $term_name, $is_checked ) {
		$html  = '<div class="cb-course-edit-terms__checkbox">';
		$html .= sprintf( '<input type="checkbox" name="course_tags[]" value="%s" id="course_tag_%s" %s>', $term_id, $term_id, checked( $is_checked, true, false ) );
		$html .= sprintf( '<label for="course_tag_%s">%s</label>', $term_id, $term_name );
		$html .= '</div>';

		return $html;
	}

	public function edit_featured_image( $course_model ) {
		$post_id = ! empty( $course_model ) ? $course_model->get_id() : '';

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
			'wrapper'     => '<div class="cb-course-edit-featured-image">',
			'label'       => sprintf(
				'<label class="cb-course-edit-featured-image__title">%s</label>',
				__( 'Featured Image', 'learnpress' )
			),
			'edit'        => $featured_image_html,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function section_curriculum() {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-admin-learnpress' );

		$course_id = CourseBuilder::get_post_id();

		if ( $course_id === 'post-new' ) {
			echo __( 'Please save Course before add Section' );
			return;
		}

		if ( absint( $course_id ) ) {
			$course_model = CourseModel::find( $course_id, true );
			if ( empty( $course_model ) ) {
				return;
			}
		}

		// Load curriculum with is_course_builder flag
		$this->load_curriculum_for_course_builder( $course_model );
	}

	/**
	 * Load curriculum for Course Builder with special flag.
	 * This method loads curriculum via AJAX with is_course_builder flag.
	 *
	 * @since 4.3.0
	 * @version 1.0.0
	 *
	 * @param CourseModel $courseModel
	 */
	protected function load_curriculum_for_course_builder( CourseModel $courseModel ) {
		wp_enqueue_style( 'lp-edit-curriculum' );
		wp_enqueue_script( 'lp-edit-course' );

		$args = [
			'id_url'            => 'edit-course-curriculum',
			'course_id'         => $courseModel->ID,
			'is_course_builder' => true, // Flag to identify Course Builder context
		];

		$call_back = [
			'class'  => AdminEditCurriculumTemplate::class,
			'method' => 'render_edit_course_curriculum',
		];

		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
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

	public function section_settings() {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-tom-select' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-learnpress' );

		$course_id = CourseBuilder::get_post_id();

		if ( $course_id === 'post-new' ) {
			echo __( 'Please save Course before setting course', 'learnpress' );
			return;
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

		$metabox = new \LP_Meta_Box_Course();
		ob_start();
		$metabox->output( $course_model );
		$settings = ob_get_clean();


		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__course-edit" data-course-id="%s">', $course_id ),
			'form_setting'     => '<form name="lp-form-setting-course" class="lp-form-setting-course" method="post" enctype="multipart/form-data">',
			'settings'         => $settings,
			'form_setting_end' => '</form>',
			'wrapper_end'      => '</div>',
		];

		echo Template::combine_components( $output );
	}
}
