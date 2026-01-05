<?php

namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Databases\DataBase;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\PostFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Database;
use LP_Post_DB;
use LP_Post_Type_Filter;
use stdClass;

/**
 * Template hooks Admin Edit Course Curriculum.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */
class AdminEditCurriculumTemplate {
	use Singleton;

	/**
	 * Course model.
	 *
	 * @var CourseModel
	 */
	public $courseModel;

	public function init() {
		add_action( 'learn-press/admin/edit-curriculum/layout', [ $this, 'edit_course_curriculum_layout' ] );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Layout for edit course curriculum.
	 *
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public function edit_course_curriculum_layout( CourseModel $courseModel ) {
		wp_enqueue_style( 'lp-edit-curriculum' );
		wp_enqueue_script( 'lp-edit-course' );

		$args      = [
			'id_url'    => 'edit-course-curriculum',
			'course_id' => $courseModel->ID,
		];
		$call_back = array(
			'class'  => self::class,
			'method' => 'render_edit_course_curriculum',
		);

		echo TemplateAJAX::load_content_via_ajax( $args, $call_back );
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
		$callbacks[] = get_class( $this ) . ':render_edit_course_curriculum';
		$callbacks[] = get_class( $this ) . ':render_list_items_not_assign';

		return $callbacks;
	}

	/**
	 * Render edit course curriculum html.
	 *
	 * @throws Exception
	 */
	public static function render_edit_course_curriculum( array $data ): stdClass {
		$course_id   = $data['course_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		self::instance()->courseModel = $courseModel;

		$coursePostModel = new CoursePostModel( $courseModel );
		if ( ! $coursePostModel->check_capabilities_create() ) {
			throw new Exception( __( 'You do not have permission to edit this course', 'learnpress' ) );
		}

		$content          = new stdClass();
		$content->content = self::instance()->html_edit_curriculum( $courseModel );

		return $content;
	}

	/**
	 * HTML for edit curriculum.
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 */
	public function html_edit_curriculum( CourseModel $courseModel ): string {
		$html_sections = '';

		// Get sections items
		$sections_items = $courseModel->get_section_items();
		$count_sections = count( $sections_items );

		foreach ( $sections_items as $section_items ) {
			$html_sections .= $this->html_edit_section( $courseModel, $section_items );
		}

		$sections = [
			'wrap'          => '<div class="curriculum-sections">',
			'list-sections' => $html_sections,
			'section-clone' => $this->html_edit_section( $courseModel ),
			'wrap_end'      => '</div>',
		];

		$section = [
			'wrap'            => '<div id="lp-course-edit-curriculum">',
			'heading'         => '<div class="heading">',
			'h4'              => sprintf(
				'<h4>%s</h4>',
				__( 'Details', 'learnpress' )
			),
			'count-sections'  => sprintf(
				'<div class="count-sections" data-count="%s">%s</div>',
				$count_sections,
				sprintf(
					__( '<span class="count">%1$s</span> %2$s', 'learnpress' ),
					$count_sections,
					sprintf(
						'<span class="one">%s</span><span class="plural">%s</span>',
						__( 'Section', 'learnpress' ),
						__( 'Sections', 'learnpress' )
					)
				)
			),
			'count-items'     => sprintf(
				'<div class="total-items" data-count="%s">%s</div>',
				$courseModel->count_items(),
				sprintf(
					__( '<span class="count">%1$s</span> %2$s', 'learnpress' ),
					$courseModel->count_items(),
					sprintf(
						'<span class="one">%s</span><span class="plural">%s</span>',
						__( 'Item', 'learnpress' ),
						__( 'Items', 'learnpress' )
					)
				)
			),
			'section-toggle'  =>
				'<div class="course-toggle-all-sections lp-collapse">
					<span class="lp-icon-angle-down"></span>
					<span class="lp-icon-angle-up"></span>
				</div>',
			'heading_end'     => '</div>',
			'sections'        => Template::combine_components( $sections ),
			'add_new_section' => $this->html_add_new_section(),
			'select_items'    => $this->html_popup_items_to_select_clone( $courseModel ),
			'wrap_end'        => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for edit section.
	 *
	 * @param CourseModel $courseModel
	 * @param null|object $section_items
	 *
	 * @return string
	 */
	public function html_edit_section( CourseModel $courseModel, $section_items = null ): string {
		$is_clone     = is_null( $section_items );
		$total_items  = 0;
		$items        = [];
		$html_items   = '';
		$section_id   = $section_items->section_id ?? 0;
		$section_name = $section_items->section_name ?? '';

		if ( ! $is_clone ) {
			$total_items = count( $section_items->items ?? [] );
			$items       = $section_items->items ?? [];

			foreach ( $items as $item ) {
				$html_items .= $this->html_section_item( $courseModel, $item );
			}
		}

		$section_list_items = [
			'wrap'       => '<ul class="section-list-items">',
			'items'      => $html_items,
			'item_clone' => $this->html_section_item( $courseModel ),
			'wrap_end'   => '</ul>',
		];

		$section = [
			'wrap'                 => sprintf(
				'<div data-section-id="%s" class="section lp-collapse %s">',
				$section_id,
				$is_clone ? 'clone lp-hidden' : ''
			),
			'head'                 => '<div class="section-head">',
			'drag'                 => sprintf(
				'<span class="drag lp-icon-drag" title="%s"></span>',
				__( 'Drag to reorder section', 'learnpress' )
			),
			'loading'              => '<span class="lp-icon-spinner"></span>',
			'title'                => $this->html_input_section_title( $section_name ),
			'btn-edit'             => sprintf(
				'<span class="lp-btn-edit-section-title lp-icon-edit" title="%s"></span>',
				__( 'Edit section title', 'learnpress' )
			),
			'btn-delete'           => sprintf(
				'<button type="button" class="lp-btn-delete-section button"
					data-send="%s"
					data-title="%s" data-content="%s">%s
				</button>',
				Template::convert_data_to_json(
					[
						'action'    => 'course_delete_section',
						'course_id' => $courseModel->get_id(),
						'id_url'    => 'course-delete-section',
					]
				),
				__( 'Are you sure?', 'learnpress' ),
				__( 'This section will be deleted. The items in this section will no longer be assigned to this course, but will not be permanently deleted.', 'learnpress' ),
				__( 'Delete Section', 'learnpress' )
			),
			'btn-update'           => sprintf(
				'<button type="button" class="lp-btn-update-section-title button">%s</button>',
				__( 'Update' )
			),
			'btn-cancel'           => sprintf(
				'<button type="button" class="lp-btn-cancel-update-section-title button">%s</button>',
				__( 'Cancel' )
			),
			'count-items'          => sprintf(
				'<div class="section-items-counts" data-count="%s">%s</div>',
				$total_items,
				sprintf(
					__( '<span class="count">%1$s</span> %2$s', 'learnpress' ),
					$total_items,
					sprintf(
						'<span class="one">%s</span><span class="plural">%s</span>',
						__( 'Item', 'learnpress' ),
						__( 'Items', 'learnpress' )
					)
				)
			),
			'toggle'               => '<div class="section-toggle"><span class="lp-icon-angle-down"></span><span class="lp-icon-angle-up"></span></div>',
			'head_end'             => '</div>',
			'wrap_content'         => '<div class="section-collapse">',
			'section-content'      => '<div class="section-content">',
			'section-description'  => sprintf(
				'<div class="section-description">%s%s%s</div>',
				$this->html_edit_section_description( $section_items->section_description ?? '' ),
				sprintf(
					'<button type="button" class="lp-btn-update-section-description button">%s</button>',
					__( 'Update' )
				),
				sprintf(
					'<button type="button" class="lp-btn-cancel-update-section-description button">%s</button>',
					__( 'Cancel' )
				)
			),
			'section-list-items'   => Template::combine_components( $section_list_items ),
			'section-content-end'  => '</div>',
			'section-item-actions' => $this->html_section_item_actions(),
			'wrap_content_end'     => '</div>',
			'wrap_end'             => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML input section title.
	 *
	 * @param string $section_name
	 *
	 * @return string
	 */
	public function html_input_section_title( string $section_name = '' ): string {
		return sprintf(
			'<input class="lp-section-title-input"
				name="lp-section-title-input"
				type="text"
				value="%1$s"
				data-old="%1$s"
				placeholder="%2$s"
				data-mess-empty-title="%3$s"
				data-send="%4$s">',
			esc_attr( $section_name ),
			esc_attr__( 'Update section title', 'learnpress' ),
			esc_attr__( 'Section title is required', 'learnpress' ),
			Template::convert_data_to_json(
				[
					'action'    => 'course_update_section',
					'course_id' => $this->courseModel->get_id(),
					'id_url'    => 'course-update-section-title',
				]
			)
		);
	}

	/**
	 * HTML for section description input.
	 *
	 * @param string|null $section_description
	 *
	 * @return string
	 */
	public function html_edit_section_description( string $section_description = '' ): string {
		return sprintf(
			'<textarea class="lp-section-description-input"
				name="lp-section-description-input"
				type="text"
				data-old="%1$s"
				placeholder="%2$s"
				data-send="%3$s">%1$s</textarea>',
			esc_attr( $section_description ),
			esc_attr__( '+ Add Description', 'learnpress' ),
			Template::convert_data_to_json(
				[
					'action'    => 'course_update_section',
					'course_id' => $this->courseModel->get_id(),
					'id_url'    => 'course-update-section-description',
				]
			)
		);
	}

	/**
	 * HTML add new section.
	 *
	 * @return string
	 */
	public function html_add_new_section(): string {
		$section = [
			'wrap'     => '<div class="add-new-section">',
			'icon'     => '<span class="lp-icon-plus"></span>',
			'input'    => sprintf(
				'<input class="lp-section-title-new-input"
					name="lp-section-title-new-input"
					type="text"
					title="%1$s"
					placeholder="%1$s"
					data-mess-empty-title="%2$s"
					data-send="%3$s">',
				esc_attr__( 'Create a new section', 'learnpress' ),
				esc_attr__( 'Section title is required', 'learnpress' ),
				Template::convert_data_to_json(
					[
						'action'    => 'course_add_section',
						'course_id' => $this->courseModel->get_id(),
						'id_url'    => 'course-add-section',
					]
				),
			),
			'button'   => sprintf(
				'<button type="button"
					class="lp-btn-add-section button lp-btn-edit-primary">%s
				</button>',
				__( 'Add Section', 'learnpress' )
			),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for section item.
	 * $item is null when clone item new
	 *
	 * @param CourseModel $courseModel
	 * @param null|object $item
	 *
	 * @return string
	 */
	public function html_section_item( CourseModel $courseModel, $item = null ): string {
		$is_clone   = is_null( $item );
		$item_id    = $item->item_id ?? 0;
		$item_title = $item->title ?? '';
		$item_type  = $item->item_type ?? '';

		/**
		 * @var $itemModel PostModel
		 */
		$itemModel = $courseModel->get_item_model( $item_id, $item_type );

		$section_action = [
			'wrap'     => '<ul class="item-actions">',
			'preview'  => sprintf(
				'<li title="%s" class="lp-btn-set-preview-item"><a class="%s"></a></li>',
				__( 'Enable/Disable Preview', 'learnpress' ),
				$itemModel && $itemModel->post_type === LP_LESSON_CPT
				&& $itemModel->has_preview() ? 'lp-icon-eye' : 'lp-icon-eye-slash'
			),
			'edit'     => sprintf(
				'<li title="%s"><a href="%s" target="_blank" class="lp-icon-edit-square edit-link"></a></li>',
				__( 'Edit item detail', 'learnpress' ),
				$itemModel ? $itemModel->get_edit_link() : ''
			),
			'delete'   => sprintf(
				'<li class="action lp-btn-delete-item"
					data-title="%s" data-content="%s">%s</li>',
				__( 'Are you sure?', 'learnpress' ),
				__( 'This item will be removed from this section. This item will no longer be assigned to this course. It will not be permanently deleted from the system.', 'learnpress' ),
				sprintf( '<a class="lp-icon-trash-o" title="%s"></a>', __( 'Remove item', 'learnpress' ) )
			),
			'wrap_end' => '</ul>',
		];

		$section = [
			'li'           => sprintf(
				'<li data-item-id="%s" data-item-type="%s" class="section-item %s %s">',
				$item_id,
				$item_type,
				$item_type,
				$is_clone ? 'clone lp-hidden' : ''
			),
			'drag'         => sprintf(
				'<span class="drag lp-icon-drag" title="%s"></span>',
				__( 'Drag to reorder item', 'learnpress' )
			),
			'icon'         => '<div class="item-ico-type"></div>',
			'loading'      => '<span class="lp-icon-spinner"></span>',
			'input-title'  => sprintf(
				'<input name="%1$s" class="%1$s" type="text" value="%2$s" data-old="%2$s" data-mess-empty-title="%3$s">',
				'lp-item-title-input',
				wp_kses_post( $item_title ),
				esc_attr__( 'Item title is required', 'learnpress' )
			),
			'btn-update'   => sprintf(
				'<button type="button" class="lp-btn-update-item-title button button-primary">%s</button>',
				__( 'Update' )
			),
			'btn-cancel'   => sprintf(
				'<button type="button" class="lp-btn-cancel-update-item-title button">%s</button>',
				__( 'Cancel' )
			),
			'item_actions' => Template::combine_components( $section_action ),
			'li_end'       => '</li>',
		];

		return Template::combine_components( $section );
	}

	public function html_section_item_actions(): string {
		$course_item_types = CourseModel::item_types_support();

		$html_buttons = '';
		foreach ( $course_item_types as $type ) {
			$item_label = CourseModel::item_types_label( $type );

			$html_buttons .= sprintf(
				'<button class="lp-btn-select-item-type button"
					data-item-type="%s"
					data-placeholder="%s"
					data-button-add-text="%s"
					type="button">%s</button>',
				$type,
				sprintf( __( 'Create a new %s', 'learnpress' ), $item_label ),
				sprintf( __( 'Add %s', 'learnpress' ), $item_label ),
				sprintf( __( 'New %s', 'learnpress' ), $item_label )
			);
		}

		$section = [
			'wrap'             => '<div class="section-actions">',
			'buttons'          => $html_buttons,
			'btn-select-items' => sprintf(
				'<button type="button"
					data-template="#lp-tmpl-select-course-items-bank"
					class="button lp-btn-show-popup-items-to-select">
					%s</button>',
				__( 'Content Bank', 'learnpress' )
			),
			'add-item-type'    => $this->html_add_item_type(),
			'wrap_end'         => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for section item new.
	 *
	 * @return string
	 */
	public function html_add_item_type(): string {
		$section = [
			'wrap'          => '<div class="lp-add-item-type clone lp-hidden">',
			'icon-plus'     => '<div class="lp-icon-plus"></div>',
			'item-ico-type' => '<div class="item-ico-type"></div>',
			'actions'       => '<div class="new-item-actions">',
			'input'         => sprintf(
				'<input class="%1$s" name="%1$s" data-mess-empty-title="%2$s" type="text"/>',
				'lp-add-item-type-title-input',
				esc_attr__( 'Item title is required', 'learnpress' )
			),
			'button_add'    => '<button class="lp-btn-add-item button button-primary" type="button"></button>',
			'button_cancel' => sprintf(
				'<button class="lp-btn-add-item-cancel button" type="button">%s</button>',
				__( 'Cancel' )
			),
			'actions_end'   => '</div>',
			'wrap_end'      => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML for popup items to select.
	 *
	 * @param CourseModel $course_model
	 *
	 * @return string
	 */
	public function html_popup_items_to_select_clone( CourseModel $course_model ): string {
		/**
		 * @uses self::render_list_items_not_assign
		 */
		$html_items = TemplateAJAX::load_content_via_ajax(
			[
				'id_url'             => 'list-items-not-assign',
				'enableScrollToView' => false,
				'course_id'          => $course_model->ID,
				'item_type'          => LP_LESSON_CPT,
				'paged'              => 1,
			],
			[
				'class'  => self::class,
				'method' => 'render_list_items_not_assign',
			]
		);

		$tabs              = [];
		$course_item_types = CourseModel::item_types_support();
		foreach ( $course_item_types as $type ) {
			$item_label    = CourseModel::item_types_label( $type );
			$tabs[ $type ] = $item_label;
		}

		$section = [
			'wrap-script-template'     => '<script type="text/template" id="lp-tmpl-select-course-items-bank">',
			'popup'                    => AdminTemplate::html_popup_items_to_select_clone( $tabs, $html_items ),
			'wrap-script-template-end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Render list items not assign to course.
	 * Get all items not assigned to any course, use old logic.
	 * If user current is Admin, get all items.
	 * Else get all items created by user current.
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.4
	 */
	public static function render_list_items_not_assign( $data ): stdClass {
		$user                   = wp_get_current_user();
		$content                = new stdClass();
		$course_id              = $data['course_id'] ?? 0;
		$item_type              = $data['item_type'] ?? LP_LESSON_CPT;
		$item_selecting         = $data['item_selecting'] ?? [];
		$search_title           = $data['search_title'] ?? '';
		$paged                  = intval( $data['paged'] ?? 1 );
		$item_selecting_compare = new stdClass();

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		// Check permission
		$coursePostModel = new CoursePostModel( $courseModel );
		if ( ! $coursePostModel->check_capabilities_create() ) {
			throw new Exception( __( 'You do not have permission view list', 'learnpress' ) );
		}

		$lp_posts_db         = PostDB::getInstance();
		$filter              = new PostFilter();
		$filter->only_fields = [
			'DISTINCT(p.ID) AS ID',
			'p.post_title',
			'p.post_type',
		];
		$filter->post_type   = $item_type;
		$filter->post_status = [ 'publish' ];
		$filter->order_by    = 'p.ID';
		$filter->page        = $paged;
		if ( ! user_can( $user, UserModel::ROLE_ADMINISTRATOR ) ) {
			$filter->post_author = $user->ID;
		}

		if ( ! empty( $search_title ) ) {
			$filter->post_title = $search_title;
		}

		// Old logic: Get all items not assigned to any course.
		$filter->where[] = "AND p.ID NOT IN ( SELECT item_id FROM {$lp_posts_db->tb_lp_section_items} )";

		// New logic: Get all items not assigned to the course.
		// Code here

		$total_rows  = 0;
		$filter      = apply_filters(
			'learn-press/filter-list-items-not-assign-course',
			$filter,
			$data,
			$courseModel
		);
		$posts       = $lp_posts_db->get_posts( $filter, $total_rows );
		$total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );

		$html_lis = '';
		if ( empty( $posts ) ) {
			$html_lis = sprintf( '<li>%s</li>', __( 'No items found', 'learnpress' ) );
		} else {
			if ( ! empty( $item_selecting ) ) {
				foreach ( $item_selecting as $item ) {
					if ( ! isset( $item['item_id'] ) || ! isset( $item['item_type'] ) ) {
						continue;
					}

					$item_selecting_compare->{$item['item_id']}            = new stdClass();
					$item_selecting_compare->{$item['item_id']}->item_type = $item['item_type'];
				}
			}

			foreach ( $posts as $post ) {
				/**
				 * @var $itemModel PostModel
				 */
				$itemModel = $courseModel->get_item_model( $post->ID, $post->post_type );
				if ( ! $itemModel ) {
					continue;
				}

				$checked = '';

				if ( isset( $item_selecting_compare->{$post->ID} ) ) {
					$checked = ' checked="checked"';
				}

				$title_display = sprintf(
					'<span class="title">%s<strong>(#%d)</strong></span>',
					$post->post_title,
					$post->ID,
				);

				$html_lis .= sprintf(
					'<li class="lp-select-item">%s%s</li>',
					sprintf(
						'<input name="lp-select-item"
							data-id="%d"
							data-type="%s"
							data-title="%s" %s data-edit-link="%s"
							data-title-selected="%s"
							type="checkbox" />',
						esc_attr( $post->ID ?? 0 ),
						esc_attr( $post->post_type ?? '' ),
						esc_attr( $title_display ), // For JS display on list selected.
						esc_attr( $checked ),
						get_edit_post_link( $post->ID ),
						esc_attr( get_the_title( $post->ID ) )
					),
					$title_display
				);
			}
		}

		$section = [
			'ul'         => '<ul class="list-items">',
			'items'      => $html_lis,
			'ul_end'     => '</ul>',
			'pagination' => Template::instance()->html_pagination(
				[
					'total_pages' => $total_pages,
					'paged'       => $paged,
				]
			),
		];

		$content->content = Template::combine_components( $section );

		return $content;
	}
}
