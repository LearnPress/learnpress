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
use LearnPress\TemplateHooks\Course\AdminEditCurriculumTemplate;

class BuilderEditCourseTemplate {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		add_action( 'learn-press/course-builder/courses/overview/layout', [ $this, 'section_overview' ] );
		add_action( 'learn-press/course-builder/courses/curriculum/layout', [ $this, 'section_curriculum' ] );
		add_action( 'learn-press/course-builder/courses/settings/layout', [ $this, 'section_settings' ] );
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
		$cousre_id = CourseBuilder::get_post_id();

		if ( $cousre_id === 'post-new' ) {
			$course_model = '';
		}

		if ( absint( $cousre_id ) ) {
			$course_model = CourseModel::find( $cousre_id, true );
			if ( empty( $course_model ) ) {
				return;
			}
		}

		$html_header        = $this->header_overview( $course_model );
		$html_edit_title    = $this->edit_title( $course_model );
		$html_edit_desc     = $this->edit_desc( $course_model );
		$html_edit_cat      = $this->edit_categories( $course_model );
		$html_edit_features = $this->edit_featured_image( $course_model );
		$html_edit_tags     = $this->edit_tags( $course_model );
		$section            = [
			'wrapper'                => sprintf( '<div class="cb-section__course-overview" data-course-id="%s">', $cousre_id ),
			'header'                 => $html_header,
			'edit_title'             => $html_edit_title,
			'edit_desc'              => $html_edit_desc,
			'edit_term_category'     => '<div class="cb-course-edit-terms-categories-wrapper">',
			'edit_cat'               => $html_edit_cat,
			'edit_term'              => $html_edit_tags,
			'edit_term_category_end' => '</div>',
			'edit_features'          => $html_edit_features,
			'wrapper_end'            => '</div>',
		];

		echo Template::combine_components( $section );
	}

	public function header_overview( $course_model ) {
		$status     = ! empty( $course_model ) ? $course_model->get_status() : '';
		$btn_update = sprintf( '<div class="cb-button cb-btn-update" data-title-update="%s" data-title-publish="%s">%s</div>', __( 'Update', 'learnpress' ), __( 'Publish', 'learnpress' ), $status === 'publish' ? __( 'Update', 'learnpress' ) : __( 'Publish', 'learnpress' ) );
		$btn_draft  = sprintf( '<div class="cb-button cb-btn-darft">%s</div>', __( 'Save Draft', 'learnpress' ) );
		$btn_trash  = sprintf( '<div class="cb-button cb-btn-trash">%s</div>', __( 'Trash', 'learnpress' ) );
		$header     = [
			'wrapper'          => '<div class="cb-section__header">',
			'wrapper_left'     => '<div class="cb-section__header-left">',
			'section_title'    => sprintf( '<h3 class="lp-cb-section__title">%s</h3>', __( 'Edit Course', 'learnpress' ) ),
			'course_status'    => ! empty( $status ) ? sprintf( '<span class="course-status %1$s">%1$s</span>', $status ) : '',
			'wrapper_left_end' => '</div>',
			'action_wrapper'   => '<div class="cb-section__header-action">',
			'btn_update'       => $btn_update,
			'btn_draft'        => $btn_draft,
			'btn_trash'        => $btn_trash,
			'action_end'       => '</div>',
			'wrapper_end'      => '</div>',
		];
		return Template::combine_components( $header );
	}

	public function edit_title( $course_model ) {
		$title = ! empty( $course_model ) ? $course_model->get_title() : '';
		$edit  = [
			'wrapper'     => '<div class="cb-course-edit-title">',
			'label'       => sprintf( '<label for="title" class="cb-course-edit-title__label">%s</label>', __( 'Course Title', 'learnpress' ) ),
			'input'       => sprintf( '<input type="text" name="course_title" size="30" value="%s" id="title" class="cb-course-edit-title__input">', $title ),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_desc( $course_model ) {
		$desc            = ! empty( $course_model ) ? $course_model->get_description() : '';
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
			'quicktags'     => false,
		);

		ob_start();
		wp_editor( $desc, $editor_id, $editor_settings );
		$editor_html = ob_get_clean();

		$edit = [
			'wrapper'     => '<div class="cb-course-edit-desc">',
			'label'       => sprintf( '<label for="course_description" class="cb-course-edit-desc__label">%s</label>', __( 'Course Description', 'learnpress' ) ),
			'edit'        => $editor_html,
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_categories( $course_model ) {
		$course_cat  = ! empty( $course_model ) ? $course_model->get_categories() : [];
		$categories  = ListCourseCategories::get_all_categories_id_name(
			[
				'hide_empty' => false,
			]
		);
		$btn_add_cat = sprintf( '<button class="cb-course-edit-category__btn-add-new">%s</button>', __( 'Add New Category', 'learnpress' ) );
		$btn_cancel  = sprintf( '<button class="cb-course-edit-category__btn-cancel"  style="display:none;">%s</button>', __( 'Cancel', 'learnpress' ) );

		$selected_cat_ids = array_map(
			function ( $term ) {
				return (int) $term->term_id;
			},
			$course_cat
		);

		$html_checkbox = '';

		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category_id => $category_name ) {
				$is_checked     = in_array( (int) $category_id, $selected_cat_ids, true );
				$html_checkbox .= $this->input_checkbox_category_item( $category_id, $category_name, $is_checked );
			}
		}

		$edit = [
			'wrapper'                       => '<div class="cb-course-edit-categories__wrapper">',
			'label'                         => sprintf( '<label for="title" class="cb-course-edit-categories__label">%s</label>', __( 'Course Categories', 'learnpress' ) ),
			'wrapper_checkbox'              => '<div class="cb-course-edit-categories__checkbox-wrapper">',
			'checkbox'                      => $html_checkbox,
			'wrapper_checkbox_end'          => '</div>',
			'btn_add_new'                   => $btn_add_cat,
			'btn_cancel'                    => $btn_cancel,
			'form_add_category_wrapper'     => '<div class="cb-course-edit-terms__form-add-category" style="display:none;">',
			'input'                         => '<input type="text" class="cb-course-edit-category__input" placeholder="' . esc_attr__( 'Enter Category Name', 'learnpress' ) . '"/>',
			'button'                        => '<button type="button" class="cb-course-edit-category__btn-save">' . esc_html__( 'Add', 'learnpress' ) . '</button>',
			'form_add_category_wrapper_end' => '</div>',
			'wrapper_end'                   => '</div>',
		];

		return Template::combine_components( $edit );
	}

	public function edit_tags( $course_model ) {
		$course_terms = ! empty( $course_model ) ? $course_model->get_tags() : [];
		$btn_add_cat  = sprintf( '<button class="cb-course-edit-tag__btn-add-new">%s</button>', __( 'Add New Tag', 'learnpress' ) );
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
			'label'                    => sprintf( '<label for="title" class="cb-course-edit-tags__label">%s</label>', __( 'Course Tags', 'learnpress' ) ),
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

		$featured_image_html = '<div class="cb-featured-image-container">';

		$featured_image_html .= '<div class="cb-featured-image-preview">';
		if ( $thumbnail_url ) {
			$featured_image_html .= sprintf(
				'<img src="%s" alt="%s" class="cb-featured-image-preview__img">',
				esc_url( $thumbnail_url ),
				esc_attr( $thumbnail_alt )
			);
		} else {
			$featured_image_html .= sprintf(
				'<div class="cb-featured-image-placeholder">%s</div>',
				__( 'No image selected', 'learnpress' )
			);
		}
		$featured_image_html .= '</div>';

		$featured_image_html .= sprintf(
			'<input type="hidden" name="course_thumbnail_id" id="course_thumbnail_id" value="%s">',
			esc_attr( $thumbnail_id )
		);

		$featured_image_html .= '<div class="cb-featured-image-actions">';
		$featured_image_html .= sprintf(
			'<button type="button" class="button button-primary cb-set-featured-image" data-post-id="%s">%s</button>',
			esc_attr( $post_id ),
			$thumbnail_id ? __( 'Change Image', 'learnpress' ) : __( 'Set Featured Image', 'learnpress' )
		);

		$featured_image_html .= sprintf(
			'<button type="button" class="button cb-remove-featured-image" %s>%s</button>',
			$thumbnail_id ? '' : 'style="display:none;"',
			__( 'Remove Image', 'learnpress' ),
		);

		$featured_image_html .= '</div>';

		$featured_image_html .= '</div>';

		$edit = [
			'wrapper'     => '<div class="cb-course-edit-featured-image">',
			'label'       => sprintf(
				'<h3 class="cb-course-edit-featured-image__title">%s</h3>',
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

		$cousre_id = CourseBuilder::get_post_id();

		if ( $cousre_id === 'post-new' ) {
			echo __( 'Please save Course before add Section' );
			return;
		}

		if ( absint( $cousre_id ) ) {
			$course_model = CourseModel::find( $cousre_id, true );
			if ( empty( $course_model ) ) {
				return;
			}
		}

		include_once LP_PLUGIN_PATH . 'inc/admin/class-lp-admin-assets.php';
		include_once LP_PLUGIN_PATH . 'inc/admin/class-lp-admin.php';

		\LP_Admin_Assets::instance();
		AdminEditCurriculumTemplate::instance();

		do_action( 'learn-press/admin/edit-curriculum/layout', $course_model );
	}


	public function section_settings() {
		wp_enqueue_script( 'lp-cb-edit-curriculum' );
		wp_enqueue_style( 'lp-cb-edit-curriculum' );
		wp_enqueue_script( 'lp-cb-learnpress' );

		$cousre_id = CourseBuilder::get_post_id();

		if ( $cousre_id === 'post-new' ) {
			echo __( 'Please save Course before setting course', 'learnpress' );
			return;
		}

		if ( absint( $cousre_id ) ) {
			$course_model = CourseModel::find( $cousre_id, true );
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
			'wrapper'     => '<div id="course-settings">',
			'settings'    => $settings,
			'wrapper_end' => '</div>',
		];

		echo Template::combine_components( $output );
	}
}
