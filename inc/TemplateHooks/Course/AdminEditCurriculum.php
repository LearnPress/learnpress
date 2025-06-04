<?php

namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\CourseModel;

use LearnPress\Models\LessonPostModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Background_Single_Course;
use LP_Database;
use LP_Post_DB;
use LP_Post_Type_Filter;
use LP_Section_DB;
use LP_Section_Items_DB;
use LP_Section_Items_Filter;
use LP_WP_Filesystem;
use stdClass;

/**
 * Template hooks Admin Edit Course Curriculum.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */
class AdminEditCurriculum {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
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
		$callbacks[] = get_class( $this ) . ':handle_edit_course_curriculum';
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

		$content          = new stdClass();
		$content->content = AdminEditCurriculum::instance()->html_edit_curriculum( $courseModel );

		return $content;
	}

	/**
	 * Render string to data content
	 *
	 * @param array $data
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public static function handle_edit_course_curriculum( array $data ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		$can_handle = false;

		if ( current_user_can( 'manage_options' ) ) {
			$can_handle = true;
		}

		if ( ! $can_handle ) {
			throw new Exception( __( 'You do not have permission to access this page.', 'learnpress' ) );
		}

		$action = $data['action'] ?? '';
		if ( is_callable( self::class, $action ) ) {
			$content = call_user_func( [ self::class, $action ], $data );
		} else {
			throw new Exception( __( 'Action not found', 'learnpress' ) );
		}

		if ( ! isset( $content->content ) ) {
			$content->content = '';
		}

		return $content;
	}

	/**
	 * Add section
	 *
	 * @throws Exception
	 */
	public static function add_section( $data ): stdClass {
		$response = new stdClass();

		$course_id   = $data['course_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$section_title = trim( $data['title'] ?? '' );
		if ( empty( $section_title ) ) {
			throw new Exception( __( 'Section title is required', 'learnpress' ) );
		}

		// Get max section order
		$max_order = LP_Section_DB::getInstance()->get_last_number_order( $course_id );

		$sectionNew                    = new CourseSectionModel();
		$sectionNew->section_name      = $section_title;
		$sectionNew->section_course_id = $course_id;
		$sectionNew->section_order     = $max_order + 1;
		$sectionNew->save();

		$response->section = $sectionNew;
		$response->message = __( 'Section added successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update section
	 *
	 * @throws Exception
	 */
	public static function update_section( $data ): stdClass {
		$response   = new stdClass();
		$course_id  = $data['course_id'] ?? 0;
		$section_id = $data['section_id'] ?? 0;

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
		if ( ! $courseSectionModel ) {
			throw new Exception( __( 'Section not found', 'learnpress' ) );
		}

		foreach ( $data as $key => $value ) {
			if ( $key !== 'section_id' && property_exists( $courseSectionModel, $key ) ) {
				$courseSectionModel->{$key} = $value;
			}
		}

		$courseSectionModel->save();

		$response->message = __( 'Section updated successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update section position
	 * new_position => list of section id by order
	 *
	 * @throws Exception
	 */
	public static function update_section_position( $data ): stdClass {
		$response     = new stdClass();
		$course_id    = $data['course_id'] ?? 0;
		$new_position = $data['new_position'] ?? [];
		if ( ! is_array( $new_position ) ) {
			throw new Exception( __( 'Invalid section position', 'learnpress' ) );
		}

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		LP_Section_DB::getInstance()->update_sections_position( $new_position, $course_id );

		$courseModel->sections_items = null;
		$courseModel->save();

		$response->message = __( 'Section updated successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update section
	 *
	 * @throws Exception
	 */
	public static function delete_section( $data ): stdClass {
		$response    = new stdClass();
		$course_id   = $data['course_id'] ?? 0;
		$section_id  = $data['section_id'] ?? 0;
		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
		if ( ! $courseSectionModel ) {
			throw new Exception( __( 'Section not found', 'learnpress' ) );
		}

		$courseSectionModel->delete();

		$response->message = __( 'Section updated successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Add item data to section
	 *
	 * @throws Exception
	 */
	public static function add_item_to_section( $data ): stdClass {
		$response   = new stdClass();
		$course_id  = $data['course_id'] ?? 0;
		$section_id = $data['section_id'] ?? 0;

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
		if ( ! $courseSectionModel ) {
			throw new Exception( __( 'Section not found', 'learnpress' ) );
		}

		$courseSectionItemModel = $courseSectionModel->create_item_and_add( $data );

		$response->section_item = $courseSectionItemModel;

		$response->message = __( 'Item added to section successfully', 'learnpress' );

		return $response;
	}

	/**
	 * @throws Exception
	 */
	public static function add_items_to_section( $data ): stdClass {
		$response   = new stdClass();
		$course_id  = $data['course_id'] ?? 0;
		$section_id = $data['section_id'] ?? 0;

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
		if ( ! $courseSectionModel ) {
			throw new Exception( __( 'Section not found', 'learnpress' ) );
		}

		$courseSectionModel->add_items( $data );

		$response->message = __( 'Items added to section successfully', 'learnpress' );

		return $response;
	}

	/**
	 * @throws Exception
	 */
	public static function delete_item_from_section( $data ): stdClass {
		$response   = new stdClass();
		$course_id  = $data['course_id'] ?? 0;
		$section_id = $data['section_id'] ?? 0;
		$item_id    = $data['item_id'] ?? 0;

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$courseSectionModel = CourseSectionModel::find( $section_id, $course_id );
		if ( ! $courseSectionModel ) {
			throw new Exception( __( 'Section not found', 'learnpress' ) );
		}

		// Find item of section id
		$courseSectionItemModel = CourseSectionItemModel::find( $section_id, $item_id );
		if ( ! $courseSectionItemModel ) {
			throw new Exception( __( 'Item not found in section', 'learnpress' ) );
		}

		// Delete item from section
		$courseSectionItemModel->section_course_id = $course_id;
		$courseSectionItemModel->delete();

		$response->message = __( 'Item deleted from section successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update item position in section
	 *
	 * @throws Exception
	 */
	public static function update_item_section_and_position( $data ): stdClass {
		$response               = new stdClass();
		$course_id              = $data['course_id'] ?? 0;
		$items_position         = $data['items_position'] ?? [];
		$item_id_change         = $data['item_id_change'] ?? 0;
		$section_id_new_of_item = $data['section_id_new_of_item'] ?? 0;
		$section_id_old_of_item = $data['section_id_old_of_item'] ?? 0;
		if ( ! is_array( $items_position ) ) {
			throw new Exception( __( 'Invalid item position', 'learnpress' ) );
		}

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		// Find item of section id old
		$filter                  = new LP_Section_items_Filter();
		$filter->section_id      = $section_id_old_of_item;
		$filter->item_id         = $item_id_change;
		$filter->run_query_count = false;

		$courseSectionItemModel = CourseSectionItemModel::get_item_model_from_db( $filter );
		if ( ! $courseSectionItemModel ) {
			throw new Exception( __( 'Item not found in section', 'learnpress' ) );
		}

		// Update section id of item
		$courseSectionItemModel->section_id = $section_id_new_of_item;
		$courseSectionItemModel->save();

		// For each section to find item then update section id of item and position of item in the new section
		$sections_items = $courseModel->get_section_items();
		foreach ( $sections_items as $section_items ) {
			$section_id = $section_items->section_id ?? 0;

			if ( $section_id != $section_id_new_of_item ) {
				continue;
			}

			// Update position of item in section
			LP_Section_Items_DB::getInstance()->update_items_position( $items_position, $section_id_new_of_item );
			break;
		}

		$courseModel->sections_items = null;
		$courseModel->save();

		$response->message = __( 'Item position updated successfully', 'learnpress' );

		return $response;
	}

	/**
	 * @throws Exception
	 */
	public static function update_items_position( $data ): stdClass {
		$response       = new stdClass();
		$course_id      = $data['course_id'] ?? 0;
		$section_id     = $data['section_id'] ?? 0;
		$items_position = $data['items_position'] ?? [];

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		if ( ! is_array( $items_position ) || empty( $items_position ) ) {
			throw new Exception( __( 'Invalid item position', 'learnpress' ) );
		}

		// Update position of item in section
		LP_Section_Items_DB::getInstance()->update_items_position( $items_position, $section_id );

		$courseModel->sections_items = null;
		$courseModel->save();

		$response->message = __( 'Item position updated successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update data of item in section
	 *
	 * @param array $data
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public static function update_item( array $data ): stdClass {
		$response   = new stdClass();
		$course_id  = $data['course_id'] ?? 0;
		$section_id = $data['section_id'] ?? 0;
		$item_id    = $data['item_id'] ?? 0;
		$item_type  = $data['item_type'] ?? '';
		$item_title = $data['item_title'] ?? '';

		if ( empty( $item_title ) ) {
			throw new Exception( __( 'Item title is required', 'learnpress' ) );
		}

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$itemModel = $courseModel->get_item_model( $item_id, $item_type );
		if ( ! $itemModel ) {
			throw new Exception( __( 'Item not found', 'learnpress' ) );
		}

		$rs = wp_update_post(
			[
				'ID'         => $itemModel->get_id(),
				'post_title' => $item_title,
			]
		);

		if ( is_wp_error( $rs ) ) {
			throw new Exception( $rs->get_error_message() );
		}

		$courseModel->sections_items = null;
		$courseModel->save();

		$response->message = __( 'Item updated successfully', 'learnpress' );

		return $response;
	}

	/**
	 * Update item preview.
	 *
	 * @param array $data
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public static function update_item_preview( array $data ): stdClass {
		$response       = new stdClass();
		$course_id      = $data['course_id'] ?? 0;
		$item_id        = $data['item_id'] ?? 0;
		$item_type      = $data['item_type'] ?? '';
		$enable_preview = $data['enable_preview'] ?? 0;

		$courseModel = CourseModel::find( $course_id, true );
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		if ( $item_type !== LP_LESSON_CPT ) {
			throw new Exception( __( 'Only lesson can be set preview', 'learnpress' ) );
		}

		/**
		 * @var $itemModel LessonPostModel
		 */
		$itemModel = $courseModel->get_item_model( $item_id, $item_type );
		if ( ! $itemModel ) {
			throw new Exception( __( 'Item not found', 'learnpress' ) );
		}

		$itemModel->set_preview( $enable_preview == 1 );

		$response->message = __( 'Item updated successfully', 'learnpress' );

		return $response;
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
			'select_items'    => $this->html_popup_items_to_select_clone(),
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
			'drag'                 => '<span class="drag lp-icon-drag"></span>',
			'loading'              => '<span class="lp-icon-spinner"></span>',
			'title'                => $this->html_input_section_title( $section_name ),
			'btn-delete'           => sprintf(
				'<button type="button" class="lp-btn-delete-section button" data-title="%s" data-content="%s">%s</button>',
				__( 'Are you sure?', 'learnpress' ),
				__( 'This section will be deleted. All items in this section will be kept.', 'learnpress' ),
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
				data-mess-empty-title="%3$s">',
			esc_attr( $section_name ),
			esc_attr__( 'Update section title', 'learnpress' ),
			esc_attr__( 'Section title is required', 'learnpress' )
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
			'<input class="lp-section-description-input"
				name="lp-section-description-input"
				type="text"
				value="%1$s"
				data-old="%1$s"
				placeholder="%2$s">',
			esc_attr( $section_description ),
			esc_attr__( '+ Add Description', 'learnpress' )
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
					data-mess-empty-title="%2$s">',
				esc_attr__( 'Create a new section', 'learnpress' ),
				esc_attr__( 'Section title is required', 'learnpress' )
			),
			'button'   => sprintf(
				'<button type="button" class="lp-btn-add-section button">%s</button>',
				__( 'Add Sections', 'learnpress' )
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
		$itemModel  = $courseModel->get_item_model( $item_id, $item_type );

		$section_action = [
			'wrap'     => '<ul class="item-actions">',
			'preview'  => sprintf(
				'<li title="%s" class="lp-btn-set-preview-item"><a class="%s"></a></li>',
				__( 'Enable/Disable Preview', 'learnpress' ),
				$itemModel && $itemModel->post_type === LP_LESSON_CPT && $itemModel->has_preview() ? 'lp-icon-eye' : 'lp-icon-eye-slash'
			),
			'edit'     => sprintf(
				'<li title="%s"><a href="%s" target="_blank" class="lp-icon-edit-square"></a></li>',
				__( 'Edit item detail', 'learnpress' ),
				$itemModel ? get_edit_post_link( $itemModel->ID ) : ''
			),
			'delete'   => sprintf(
				'<li class="action lp-btn-delete-item"
					data-title="%s" data-content="%s">%s</li>',
				__( 'Are you sure?', 'learnpress' ),
				__( 'This item will be deleted from this section. It not delete penalty.', 'learnpress' ),
				'<a class="lp-icon-trash-o"></a>'
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
			'drag'         => '<span class="drag lp-icon-drag"></span>',
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
				sprintf( __( 'Add %s', 'leanpress' ), $item_label ),
				sprintf( __( 'New %s', 'learnpress' ), $item_label )
			);
		}

		$section = [
			'wrap'             => '<div class="section-actions">',
			'buttons'          => $html_buttons,
			'btn-select-items' => sprintf(
				'<button type="button" class="button lp-btn-show-popup-items-to-select">%s</button>',
				__( 'Select items', 'learnpress' )
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

	public function html_popup_items_to_select_clone(): string {
		$html_tabs         = '';
		$course_item_types = CourseModel::item_types_support();
		foreach ( $course_item_types as $type ) {
			$item_label = CourseModel::item_types_label( $type );
			$tab_active = $type === LP_LESSON_CPT ? ' active' : '';
			$html_tabs .= sprintf(
				'<li data-type="%s" class="tab %s"><a href="#">%s</a></li>',
				$type,
				$tab_active,
				$item_label
			);
		}

		$section_header = [
			'wrap'     => '<div class="header">',
			'count'    => '<div class="header-count-items-selected lp-hidden"></div>',
			'tabs'     => sprintf(
				'<ul class="tabs">%s</ul>',
				$html_tabs
			),
			'wrap_end' => '</div>',
		];

		/**
		 * @uses self::render_list_items_not_assign
		 */
		ob_start();
		lp_skeleton_animation_html( 10 );
		$html_loading = ob_get_clean();
		$html_items   = TemplateAJAX::load_content_via_ajax(
			[
				'id_url'                  => 'list-items-not-assign',
				'html_no_load_ajax_first' => $html_loading,
				'course_id'               => 123,
				'item_type'               => LP_LESSON_CPT,
				'paged'                   => 1,
			],
			[
				'class'  => self::class,
				'method' => 'render_list_items_not_assign',
			]
		);

		$section_main = [
			'wrap'                => '<div class="main">',
			'wrap_items'          => '<div class="list-items-wrap">',
			'search'              => sprintf(
				'<input type="text" placeholder="%s" title="search" class="lp-search-title-item">',
				__( 'Type here to search for an item', 'learnpress' )
			),
			'list-items'          => $html_items,
			'wrap_items_end'      => '</div>',
			'list-items-selected' => '
				<ul class="list-items-selected lp-hidden">
					<li class="li-item-selected clone lp-hidden" data-id="" data-type="">
						<i class="dashicons dashicons-remove"></i>
						<div>
							<span class="item-title">item_title</span>
							(#<span class="item-id">item_id</span> - <span class="item-type">item_type</span>)
						</div>
					</li>
				</ul>',
			'wrap_end'            => '</div>',
		];

		$section_footer = [
			'wrap'                 => '<div class="footer">',
			'btn-add'              => sprintf(
				'<button type="button" disabled="disabled" class="button lp-btn-add-items-selected">%s</button>',
				__( 'Add', 'learnpress' )
			),
			'count-items-selected' => sprintf(
				'<button type="button" disabled="disabled" class="button lp-btn-count-items-selected">%s %s</button>',
				sprintf( __( 'Selected items', 'learnpress' ), 0 ),
				'<span class="count"></span>'
			),
			'btn-back'             => sprintf(
				'<button type="button" class="button lp-btn-back-to-select-items lp-hidden">%s</button>',
				__( 'Back', 'learnpress' )
			),
			'wrap_end'             => '</div>',
		];

		$section = [
			'wrap'     => '<div class="lp-popup-items-to-select clone lp-hidden">',
			'header'   => Template::combine_components( $section_header ),
			'main'     => Template::combine_components( $section_main ),
			'footer'   => Template::combine_components( $section_footer ),
			'wrap_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * @throws Exception
	 */
	public static function render_list_items_not_assign( $data ): stdClass {
		$content                = new stdClass();
		$course_id              = $data['course_id'] ?? 0;
		$item_type              = $data['item_type'] ?? LP_LESSON_CPT;
		$item_selecting         = $data['item_selecting'] ?? [];
		$search_title           = $data['search_title'] ?? '';
		$paged                  = intval( $data['paged'] ?? 1 );
		$item_selecting_compare = new stdClass();

		$lp_db               = LP_Database::getInstance();
		$filter              = new LP_Post_Type_Filter();
		$filter->only_fields = [
			'DISTINCT(p.ID)',
			'p.post_title',
			'p.post_type',
		];
		$filter->post_type   = $item_type;
		$filter->post_status = 'publish';
		$filter->order_by    = 'p.ID';
		$filter->page        = $paged;

		if ( ! empty( $search_title ) ) {
			$filter->post_title = $search_title;
		}

		// Old logic: Get all items not assigned to any course.
		$filter->where[] = "AND p.ID NOT IN ( SELECT item_id FROM {$lp_db->tb_lp_section_items} )";

		// New logic: Get all items not assigned to the course.
		// Code here

		$lp_posts_db = LP_Post_DB::getInstance();
		$total_rows  = 0;
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
				$checked = '';

				if ( isset( $item_selecting_compare->{$post->ID} ) ) {
					$checked = ' checked="checked"';
				}

				$html_lis .= sprintf(
					'<li class="lp-select-item">%s%s</li>',
					sprintf(
						'<input type="checkbox" value="%d" data-type="%s" data-title="%s" %s />',
						esc_attr( $post->ID ?? 0 ),
						esc_attr( $post->post_type ?? '' ),
						esc_attr( $post->post_title ?? '' ),
						esc_attr( $checked )
					),
					sprintf(
						'<span class="title">%s<strong>(#%d)</strong></span>',
						$post->post_title,
						$post->ID
					)
				);
			}
		}

		$page_numbers = paginate_links(
			apply_filters(
				'learn_press_pagination_args',
				array(
					'base'      => add_query_arg( 'paged', '%#%', \LP_Helper::getUrlCurrent() ),
					'format'    => '',
					'add_args'  => '',
					'current'   => max( 1, $paged ),
					'total'     => $total_pages,
					'prev_text' => '<i class="dashicons dashicons-arrow-left-alt"></i>',
					'next_text' => '<i class="dashicons dashicons-arrow-right-alt"></i>',
					'type'      => 'array',
					'end_size'  => 3,
					'mid_size'  => 3,
				)
			)
		);

		$html_li_number = '';
		if ( ! empty( $page_numbers ) ) {
			foreach ( $page_numbers as $page_number ) {
				$html_li_number .= sprintf(
					'<li>%s</li>',
					$page_number
				);
			}
		}
		$section_pagination = [
			'wrap'     => '<ul class="pagination">',
			'numbers'  => $html_li_number,
			'wrap_end' => '</ul>',
		];

		$section = [
			'ul'         => '<ul class="list-items">',
			'items'      => $html_lis,
			'ul_end'     => '</ul>',
			'pagination' => Template::combine_components( $section_pagination ),
		];

		$content->content = Template::combine_components( $section );

		return $content;
	}
}
