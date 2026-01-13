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

		$html_header        = $this->header_section( $course_model );
		$html_edit_title    = $this->edit_title( $course_model );
		$html_edit_desc     = $this->edit_desc( $course_model );
		$html_edit_cat      = $this->edit_categories( $course_model );
		$html_edit_features = $this->edit_featured_image( $course_model );
		$html_edit_tags     = $this->edit_tags( $course_model );
		$section            = [
			'wrapper'                => sprintf( '<div class="cb-section__course-edit" data-course-id="%s">', $course_id ),
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

	public function header_section( $course_model ) {
		$header = [
			'wrapper'          => '<div class="cb-section__header">',
			'wrapper_left'     => '<div class="cb-section__header-left">',
			'section_title'    => sprintf( '<h3 class="lp-cb-section__title">%s</h3>', __( 'Edit Course', 'learnpress' ) ),
			'wrapper_left_end' => '</div>',
			'wrapper_end'      => '</div>',
		];
		return Template::combine_components( $header );
	}

	public function edit_title( $course_model ) {
		$title      = ! empty( $course_model ) ? $course_model->get_title() : '';
		$char_count = mb_strlen( wp_strip_all_tags( $title ) );
		$edit       = [
			'wrapper'        => '<div class="cb-course-edit-title">',
			'label_wrap'     => '<div class="cb-course-edit-title__label-wrap">',
			'label'          => sprintf( '<label for="title" class="cb-course-edit-title__label">%s</label>', __( 'Title', 'learnpress' ) ),
			'char_count'     => sprintf( '<span class="cb-course-edit-title__char-count">%s</span>', sprintf( __( '%d characters', 'learnpress' ), $char_count ) ),
			'label_wrap_end' => '</div>',
			'input'          => sprintf( '<input type="text" name="course_title" size="30" value="%s" id="title" class="cb-course-edit-title__input">', esc_attr( $title ) ),
			'wrapper_end'    => '</div>',
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
			'word_count'     => sprintf( '<span class="cb-course-edit-desc__word-count">%s</span>', sprintf( __( '%d words', 'learnpress' ), $word_count ) ),
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
					'title'    => __( 'Course Categories', 'learnpress' ),
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
			'label'       => sprintf( '<label class="cb-course-edit-categories__label">%s</label>', __( 'Course Categories', 'learnpress' ) ),
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

		$featured_image_html .= sprintf( '<div class="cb-featured-image-preview" data-content-placholder="%s">', __( 'No image selected', 'learnpress' ) );
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

		$html_header = $this->header_section( $course_model );

		$output = [
			'wrapper'          => sprintf( '<div class="cb-section__course-edit" data-course-id="%s">', $course_id ),
			'header'           => $html_header,
			'form_setting'     => '<form name="lp-form-setting-course" class="lp-form-setting-course" method="post" enctype="multipart/form-data">',
			'settings'         => $settings,
			'form_setting_end' => '</form>',
			'wrapper_end'      => '</div>',
		];

		echo Template::combine_components( $output );
	}
}
