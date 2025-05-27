<?php

namespace LearnPress\TemplateHooks\Course;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\CourseModel;

use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Background_Single_Course;
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
	 * @use self::render_html
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_html';
		$callbacks[] = get_class( $this ) . ':render_list_items_not_assign';

		return $callbacks;
	}

	/**
	 * Render string to data content
	 *
	 * @param array $data
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public static function render_html( array $data ): stdClass {
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

		$courseSectionModel->add_item( $data );

		$response->message = __( 'Item added to section successfully', 'learnpress' );

		return $response;
	}

	/**
	 * @throws Exception
	 */
	public static function update_item_of_section( $data ): stdClass {
		$response = new stdClass();

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

	public function html_edit_sections( $sections_items ): string {

		$html_sections = '';
		foreach ( $sections_items as $section_items ) {
			$html_sections .= $this->html_edit_section( $section_items );
		}

		$section = [
			'wrap'           => '<div class="curriculum-sections">',
			'sections'       => $html_sections,
			'section_action' => $this->html_section_actions(),
			'wrap_end'       => '</div>',
			'select_items'   => $this->html_select_items(),
		];

		return Template::combine_components( $section );
	}

	public function html_edit_section( $section_items ): string {
		$total_items = count( $section_items->items ?? [] );

		$html_items = '';
		foreach ( $section_items->items as $item ) {
			$html_items .= $this->html_section_item( $item );
		}

		$section_list_items = [
			'wrap'               => '<ul class="section-list-items">',
			'items'              => $html_items,
			'section-item-clone' => $this->html_section_item(),
			'section-item-new'   => $this->html_section_item_new(),
			'wrap_end'           => '</ul>',
		];

		$section = [
			'wrap'                 => sprintf(
				'<div data-section-id="%s" class="section open">',
				$section_items->section_id ?? 0
			),
			'head'                 => '<div class="section-head">',
			'drag'                 => '<span class="movable"></span>',
			'title'                => $this->html_edit_section_title( $section_items->section_name ?? '' ),
			'btn-delete'           => sprintf(
				'<button type="button" class="lp-btn-delete-section button" data-title="%s" data-content="%s">%s</button>',
				__( 'Are you sure?', 'learnpress' ),
				__( 'This section will be deleted. All items in this section will be kept.', 'learnpress' ),
				__( 'Delete Section', 'learnpress' )
			),
			'total-items'          => sprintf(
				'<div class="section-item-counts"><span>%s</span></div>',
				sprintf( _n( '%s Item', '%s Items', $total_items, 'learnpress' ), $total_items )
			),
			'toggle'               => '<div class="actions"><span class="collapse close"></span></div>',
			'head_end'             => '</div>',
			'wrap_content'         => '<div class="section-collapse">',
			'section-content'      => '<div class="section-content">',
			'details'              => sprintf(
				'<div class="details">%s</div>',
				$this->html_edit_section_description( $section_items->section_description ?? '' )
			),
			'section-list-items'   => Template::combine_components( $section_list_items ),
			'section-content-end'  => '</div>',
			'section-item-actions' => $this->html_section_item_actions( $section_items ),
			'wrap_content_end'     => '</div>',
			'wrap_end'             => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_edit_section_title( $section_name ): string {
		return sprintf(
			'<input name="section-title-input"
					class="title-input"
					type="text"
					title="Update section title"
					placeholder="Update section title"
					value="%s">',
			esc_attr( $section_name ?? '' )
		);
	}

	public function html_edit_section_description( $section_description ): string {
		return sprintf(
			'<input type="text"
				title="description"
				placeholder="Section description..."
				class="description-input section-description-input"
				value="%s">',
			esc_attr( $section_description ?? '' )
		);
	}

	public function html_section_actions() {
		$html = '
		<div class="add-new-section">
				<div class="section new-section">
					<div class="section-head">
						<span class="creatable"></span>
						<input name="new_section"
								type="text"
								title="Enter title section"
								placeholder="Create a new section"
								class="title-input new-section">
						<button type="button" class="lp-btn-add-section">Add Sections</button>
					</div>
				</div>
			</div>';

		return $html;
	}

	public function html_section_item( $item = null ): string {
		$is_clone     = is_null( $item );
		$item_id      = $item->item_id ?? 0;
		$item_title   = $item->title ?? '';
		$item_type    = $item->item_type ?? '';
		$item_preview = $item->preview ?? '';

		$section_action = [
			'wrap'            => '<div class="item-actions">',
			'div_actions'     => '<div class="actions">',
			'preview'         => '<div data-content-tip="Enable/Disable Preview" class="action preview-item lp-title-attr-tip ready" data-id="%s"><a class="lp-btn-icon dashicons dashicons-hidden"></a></div>',
			'edit'            => '<div data-content-tip="Edit an item" class="action edit-item lp-title-attr-tip ready" data-id="%s"><a href="%s" target="_blank" class="lp-btn-icon dashicons dashicons-edit"></a></div>',
			'delete'          => '<div class="action delete-item"><a class="dashicons dashicons-trash"></a></div>',
			'div_actions_end' => '</div>',
			'wrap_end'        => '</div>',
		];

		$section = [
			'li'           => sprintf(
				'<li data-item-id="%s" class="section-item %s %s">',
				$item_id,
				$item_type,
				$is_clone ? 'empty-item section-item-clone lp-hidden' : ''
			),
			'drag'         => sprintf(
				'<div class="drag lp-sortable-handle">%s</div>',
				LP_WP_Filesystem::instance()->file_get_contents( LP_PLUGIN_PATH . 'assets/images/icons/ico-drag.svg' )
			),
			'icon'         => '<div class="icon"></div>',
			'input-title'  => sprintf(
				'<div class="title"><input name="item-title-input" type="text" value="%s"></div>',
				wp_kses_post( $item_title )
			),
			'item_actions' => Template::combine_components( $section_action ),
			'li_end'       => '</li>',
		];

		return Template::combine_components( $section );
	}

	public function html_section_item_actions( $item ): string {
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
				'<button type="button" class="button button-primary lp-btn-select-items">%s</button>',
				__( 'Select items', 'learnpress' )
			),
			'wrap_end'         => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_section_item_new() {
		$m = '
		<div class="new-section-item section-item lp-hidden">
			<div class="drag"></div>
			<div class="types">
				<label class="type current"></label>
			</div>
			<div class="title">
				<input name="new_item" type="text"/>
				<button class="lp-btn-add-item button" type="button">Add Lesson</button>
				<button class="lp-btn-add-item-cancel button" type="button">Cancel</button>
			</div>
		</div>
		';

		return $m;
	}

	public function html_select_items(): string {
		$html_tabs         = '';
		$course_item_types = CourseModel::item_types_support();
		foreach ( $course_item_types as $type ) {
			$item_label = CourseModel::item_types_label( $type );
			$html_tabs .= sprintf(
				'<li data-type="%s" class="tab"><a href="#">%s</a></li>',
				$type,
				$item_label
			);
		}

		$section_header = [
			'wrap'     => '<div class="header">',
			'title'    => sprintf(
				'<div class="preview-title"><span>%s</span></div>',
				__( 'Selected items (0)', 'learnpress' )
			),
			'tabs'     => sprintf(
				'<ul class="tabs">%s</ul>',
				$html_tabs
			),
			'wrap_end' => '</div>',
		];

		/**
		 * @uses self::render_list_items_not_assign
		 */
		$html_items = TemplateAJAX::load_content_via_ajax(
			[
				'id_url'    => 'list-items-not-assign',
				'course_id' => 123,
				'item_type' => LP_LESSON_CPT,
				'paged'     => 1,
			],
			[
				'class'  => self::class,
				'method' => 'render_list_items_not_assign',
			]
		);

		$section_main = [
			'wrap'       => '<div class="main">',
			'search'     => sprintf(
				'<input type="text" placeholder="%s" title="search" class="modal-search-input">',
				__( 'Type here to search for an item', 'learnpress' )
			),
			'list-items' => $html_items,
			'wrap_end'   => '</div>',
		];

		$section_footer = [
			'wrap'          => '<div class="footer">',
			'cart'          => '<div class="cart">',
			'checkout'      => sprintf(
				'<button type="button" disabled="disabled" class="button button-primary checkout"><span>%s</span></button>',
				__( 'Add', 'learnpress' )
			),
			'edit_selected' => sprintf(
				'<button type="button" disabled="disabled" class="button button-secondary edit-selected">%s</button>',
				sprintf( __( 'Selected items (%s)', 'learnpress' ), 0 )
			),
			'wrap_end'      => '</div></div>',
		];

		$section = [
			'wrap'     => '<div class="lp-select-items-to-add lp-hidden">',
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
		$content   = new stdClass();
		$course_id = $data['course_id'] ?? 0;
		$item_type = $data['item_type'] ?? LP_LESSON_CPT;
		$paged     = $data['paged'] ?? 1;

		$filter              = new LP_Post_Type_Filter();
		$filter->post_type   = $item_type;
		$filter->post_status = 'publish';

		$lp_posts_db = LP_Post_DB::getInstance();
		$total_pages = 0;
		$posts       = $lp_posts_db->get_posts( $filter, $total_pages );

		$html_lis = '';
		foreach ( $posts as $post ) {
			$html_lis .= sprintf(
				'<li class="item" data-id="%s">%s%s</li>',
				$post->ID,
				'<input type="checkbox" />',
				sprintf(
					'<span class="title">%s<strong>(#%d)</strong></span>',
					$post->post_title,
					$post->ID
				)
			);
		}

		$content->content = Template::instance()->nest_elements( [ '<ul class="list-items">' => '</ul>' ], $html_lis );

		return $content;
	}
}
