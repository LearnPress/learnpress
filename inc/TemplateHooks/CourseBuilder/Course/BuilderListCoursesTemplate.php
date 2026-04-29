<?php
/**
 * Template hooks Tab Course in Course Builder.
 *
 * @since 4.3.6
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Course;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Databases\DataBase;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\PostModel;
use LearnPress\Models\UserModel;
use LearnPress\Services\OpenAiService;
use LearnPress\TemplateHooks\Admin\AI\AdminCreateCourseAITemplate;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\CourseBuilder\CourseBuilderTemplate;
use LP_Course_Filter;
use Throwable;

class BuilderListCoursesTemplate {
	use Singleton;

	public function init() {}

	public function layout( array $data = [] ) {
		$section = [
			'header'       => $this->html_header( $data ),
			'filter_bar'   => $this->html_filter_bar(),
			'courses'      => $this->tab_list_courses(),
			'ai_templates' => AdminCreateCourseAITemplate::instance()->render_for_frontend(),
		];

		echo Template::combine_components( $section );
	}

	/**
	 * HTML header
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_header( array $data = [] ): string {
		$btn_add_new = sprintf(
			'<a href="%s" class="cb-btn-add-new lp-button">%s</a>',
			esc_url( CourseBuilder::get_link_course_builder( 'courses/create' ) ),
			__( 'Add New Course', 'learnpress' )
		);

		$enable_open_ai  = OpenAiService::instance()->is_enable()
							&& ! empty( OpenAiService::instance()->get_secret_key() );
		$ai_btn_class    = $enable_open_ai ? 'lp-btn-generate-course-with-ai' : 'lp-btn-warning-enable-ai';
		$btn_generate_ai = sprintf(
			'<button type="button" class="cb-btn-add-new %s">
				<i class="lp-ico-ai"></i> %s
			</button>',
			esc_attr( $ai_btn_class ),
			esc_html__( 'Generate with AI', 'learnpress' )
		);

		$header = [
			'wrapper'     => '<div class="cb-tab-header">',
			'title'       => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', __( 'Courses', 'learnpress' ) ),
			'actions'     => sprintf(
				'<div class="cb-tab-header-actions" style="display:flex;align-items:center;gap:8px;">%s%s</div>',
				$btn_add_new,
				$btn_generate_ai
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $header );
	}

	/**
	 * Render filter bar with search, status, items per page dropdowns
	 *
	 * @return string
	 * @since 4.3.0
	 */
	public function html_filter_bar(): string {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_tab_link( 'courses' );

		// Current filter values
		$current_search = $args['c_search'] ?? '';
		$current_status = $args['c_status'] ?? '';
		$current_limit  = $args['per_page'] ?? 20;

		// Status options
		$statuses = [
			''        => __( 'All Status', 'learnpress' ),
			'publish' => __( 'Published', 'learnpress' ),
			'future'  => __( 'Scheduled', 'learnpress' ),
			'draft'   => __( 'Draft', 'learnpress' ),
			'pending' => __( 'Pending Review', 'learnpress' ),
			'private' => __( 'Private', 'learnpress' ),
			'trash'   => __( 'Trash', 'learnpress' ),
		];

		// Items per page options
		$per_page_options = [ 10, 20, 50 ];

		// Build status dropdown HTML
		$status_options = '';
		foreach ( $statuses as $value => $label ) {
			$selected        = ( $current_status === $value ) ? 'selected' : '';
			$status_options .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), $selected, esc_html( $label ) );
		}

		// Build per page dropdown HTML
		$per_page_html = '';
		foreach ( $per_page_options as $option ) {
			$selected       = ( (int) $current_limit === $option ) ? 'selected' : '';
			$per_page_html .= sprintf( '<option value="%d" %s>%d</option>', $option, $selected, $option );
		}

		// Row 2: Filter toolbar
		$filter = [
			'wrapper'     => sprintf( '<form class="cb-tab-filter-bar" method="get" action="%s">', esc_url( $link_tab ) ),
			'fields'      => '<div class="cb-filter-fields">',
			'search'      => sprintf(
				'<div class="cb-filter-group">
					<label>%s</label>
					<div class="cb-filter-search">
						<input type="search" name="c_search" placeholder="%s" value="%s">
					</div>
				</div>',
				esc_html__( 'Search', 'learnpress' ),
				esc_attr__( 'Search by title', 'learnpress' ),
				esc_attr( $current_search )
			),
			'status'      => sprintf(
				'<div class="cb-filter-group">
					<label>%s</label>
					<select name="c_status" class="cb-filter-select">%s</select>
				</div>',
				esc_html__( 'Status', 'learnpress' ),
				$status_options
			),
			'per_page'    => sprintf(
				'<div class="cb-filter-group">
					<label>%s</label>
					<select name="per_page" class="cb-filter-select">%s</select>
				</div>',
				esc_html__( 'Items per page', 'learnpress' ),
				$per_page_html
			),
			'actions'     => '<div class="cb-filter-actions">',
			'filter_btn'  => sprintf( '<button type="submit" class="cb-filter-btn">%s</button>', __( 'Filter', 'learnpress' ) ),
			'reset_btn'   => sprintf( '<a href="%s" class="cb-filter-reset">%s</a>', esc_url( $link_tab ), __( 'Reset', 'learnpress' ) ),
			'actions_end' => '</div>',
			'fields_end'  => '</div>',
			'wrapper_end' => '</form>',
		];

		return Template::combine_components( $filter );
	}

	public function tab_list_courses(): string {
		$content = '';

		try {
			$user = UserModel::find( get_current_user_id(), true );
			// Query courses of user
			$filter          = new LP_Course_Filter();
			$param           = lp_archive_skeleton_get_args();
			$param['id_url'] = 'tab-list-courses';

			// Handle status filter - if empty, show all; otherwise filter by selected status
			$status_param = $param['c_status'] ?? '';
			if ( empty( $status_param ) ) {
				$param['c_status'] = 'publish,future,private,draft,pending,trash';
			}

			Courses::handle_params_for_query_courses( $filter, $param );
			// Admin can view all courses in Course Builder.
			if ( ! current_user_can( ADMIN_ROLE ) ) {
				$filter->post_author = $user->get_id();
			}

			// Handle per_page parameter
			$per_page      = isset( $param['per_page'] ) ? absint( $param['per_page'] ) : 20;
			$filter->limit = $per_page > 0 ? $per_page : 20;

			$filter->page = $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1;
			$filter       = apply_filters( 'lp/course-builder/courses/query/filter', $filter, [] );

			$total_courses = 0;
			$courses       = Courses::get_courses( $filter, $total_courses );
			if ( ! empty( $courses ) ) {
				$html_courses = $this->list_courses( $courses );
			} else {
				$html_courses = Template::print_message(
					sprintf( __( 'No courses found', 'learnpress' ) ),
					'info',
					false
				);
			}

			$data_pagination = [
				'paged'       => $filter->page,
				'total_pages' => DataBase::get_total_pages( $filter->limit, $total_courses ),
			];

			$sections = apply_filters(
				'learn-press/course-builder/courses/sections',
				[
					'wrapper'     => '<div class="courses-builder__course-tab learn-press-courses">',
					'courses'     => $html_courses,
					'pagination'  => Template::instance()->html_pagination( $data_pagination ),
					'wrapper_end' => '</div>',
				],
				$courses,
				$user
			);

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Display list courses.
	 *
	 * @param $instructor
	 * @param $courses
	 *
	 * @return string
	 */
	public function list_courses( $courses ): string {
		$content = '';

		try {
			$html_list_course = '';
			foreach ( $courses as $course_obj ) {
				// Read fresh model to avoid stale post_status in long-lived cache,
				// especially around future -> publish transitions.
				$course            = CourseModel::find( $course_obj->ID, false );
				$html_list_course .= self::render_course( $course );
			}

			$sections = [
				'wrapper'     => '<ul class="cb-list-course">',
				'list_course' => $html_list_course,
				'wrapper_end' => '</ul>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Render course in course builder
	 *
	 * @param CourseModel $courseModel
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public static function render_course( CourseModel $courseModel, array $settings = [] ): string {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$course_id     = $courseModel->get_id();
			$course_status = $courseModel->get_status();
			$edit_link     = CourseBuilder::get_link_course_builder( CourseBuilderTemplate::MENU_COURSES . "/{$course_id}" );

			// Offline badge overlay
			$offline_badge = '';
			if ( $courseModel->is_offline() ) {
				$offline_badge = '<span class="cb-item-status-badge offline">Offline</span>';
			}

			$html_img = apply_filters(
				'learn-press/course-builder/list-courses/item/section-top',
				[
					'wrapper'     => '<div class="course-thumbnail">',
					'link'        => sprintf( '<a href="%s">', $edit_link ),
					'img'         => $singleCourseTemplate->html_image( $courseModel ),
					'badge'       => $offline_badge,
					'link_end'    => '</a>',
					'wrapper_end' => '</div>',
				],
				$courseModel,
				$settings
			);

			// Icon mapping for meta items (using LearnPress frontend icons)
			$meta_icons = [
				'lesson'   => '<i class="lp-icon-file-o"></i>',
				'student'  => '<i class="lp-icon-user-graduate"></i>',
				'duration' => '<i class="lp-icon-clock-o"></i>',
				'level'    => '<i class="lp-icon-signal"></i>',
				'quiz'     => '<i class="lp-icon-puzzle-piece"></i>',
				'address'  => '<i class="lp-icon-map-marker"></i>',
			];

			$meta_data = apply_filters(
				'learn-press/course-builder/list-courses/item/meta-data',
				[
					'duration' => $singleCourseTemplate->html_duration( $courseModel ),
					'level'    => $singleCourseTemplate->html_level( $courseModel ),
					'lesson'   => $singleCourseTemplate->html_count_item( $courseModel, LP_LESSON_CPT ),
					'quiz'     => $singleCourseTemplate->html_count_item( $courseModel, LP_QUIZ_CPT ),
					'student'  => $singleCourseTemplate->html_count_student( $courseModel ),
				],
				$courseModel,
				$settings
			);

			if ( $courseModel->is_offline() ) {
				$singleCourseOfflineTemplate = SingleCourseOfflineTemplate::instance();
				unset( $meta_data['quiz'] );
				unset( $meta_data['student'] );
				if ( ! empty( $meta_data['lesson'] ) ) {
					$meta_data['lesson'] = $singleCourseOfflineTemplate->html_lesson_info( $courseModel, true );
				}

				$html_address = $singleCourseOfflineTemplate->html_address( $courseModel );
				if ( ! empty( $html_address ) ) {
					$meta_data['address'] = $singleCourseOfflineTemplate->html_address( $courseModel );
				}
			}

			$html_meta_data = '';
			if ( ! empty( $meta_data ) ) {
				foreach ( $meta_data as $k => $v ) {
					$icon            = $meta_icons[ $k ] ?? '';
					$html_meta_data .= sprintf( '<div class="meta-item meta-item-%s">%s%s</div>', $k, $icon, $v );
				}

				$html_meta_data = sprintf( '<div class="course-wrap-meta">%s</div>', $html_meta_data );
			}

			$status_label = 'future' === $course_status ? __( 'Scheduled', 'learnpress' ) : $course_status;
			$html_status  = sprintf(
				'<div class="course-status %1$s">
					<span>%2$s</span>
				</div>',
				esc_attr( $course_status ),
				esc_html( $status_label )
			);

			// Price
			$html_price = sprintf( '<div class="course-item-price-wrap">%s</div>', $singleCourseTemplate->html_price( $courseModel ) );

			// Categories
			$html_categories = '';
			$categories      = $courseModel->get_categories();
			if ( ! empty( $categories ) ) {
				$cat_names = array();
				foreach ( $categories as $cat ) {
					$cat_name = is_object( $cat ) ? $cat->name : ( is_array( $cat ) ? ( $cat['name'] ?? '' ) : '' );
					if ( ! empty( $cat_name ) ) {
						$cat_names[] = sprintf( '<span class="course-category-name">%s</span>', esc_html( $cat_name ) );
					}
				}
				if ( ! empty( $cat_names ) ) {
					$html_categories = sprintf(
						' <span class="course-categories-label">%s</span> %s',
						__( 'in', 'learnpress' ),
						implode( ', ', $cat_names )
					);
				}
			}

			// Last Updated
			$post_obj          = get_post( $courseModel->get_id() );
			$html_last_updated = '';
			if ( $post_obj && ! empty( $post_obj->post_modified ) ) {
				$modified_time     = strtotime( $post_obj->post_modified );
				$html_last_updated = sprintf(
					'<div class="course-last-updated">%s %s</div>',
					__( 'Last Updated on', 'learnpress' ),
					date_i18n( 'Y/m/d \a\t g:i a', $modified_time )
				);
			}

			$section_bottom_end = apply_filters(
				'learn-press/course-builder/list-courses/item/section/bottom/end',
				[
					'wrapper'      => '<div class="course-bottom">',
					'price'        => $html_price,
					'status'       => $html_status,
					'last_updated' => $html_last_updated,
					'wrapper_end'  => '</div>',
				],
				$courseModel,
				$settings
			);

			// Instructor with categories
			$instructor               = $courseModel->get_author_model();
			$instructor_name          = $instructor ? $instructor->get_display_name() : '';
			$html_instructor_category = '';
			if ( ! empty( $instructor_name ) ) {
				$html_instructor_category = sprintf(
					'<div class="course-instructor-category"><div><span class="course-by-label">%s</span> <span class="instructor-display-name">%s</span>%s</div></div>',
					__( 'by', 'learnpress' ),
					esc_html( $instructor_name ),
					$html_categories
				);
			}

			$html_content = apply_filters(
				'learn-press/course-builder/list-courses/item/section/bottom',
				[
					'wrapper'     => '<div class="course-content">',
					'title'       => sprintf(
						'<h3 class="wap-course-title"><a href="%s">%s</a></h3>',
						$edit_link,
						$singleCourseTemplate->html_title( $courseModel )
					),
					'instructor'  => $html_instructor_category,
					'meta'        => $html_meta_data,
					'info'        => Template::combine_components( $section_bottom_end ),
					'wrapper_end' => '</div>',
				],
				$courseModel,
				$settings
			);

			$more_actions_icon = wp_remote_fopen( LP_PLUGIN_URL . 'assets/images/icons/ico-cb-more.svg' );

			// Set action by status
			$action_by_status = [];
			if ( $course_status === PostModel::STATUS_TRASH ) {
				$action_by_status['restore'] = __( 'Restore', 'learnpress' );
				$action_by_status['delete']  = __( 'Delete permanently', 'learnpress' );
			} else {
				$action_by_status['duplicate']                 = __( 'Duplicate', 'learnpress' );
				$action_by_status[ PostModel::STATUS_PUBLISH ] = __( 'Publish', 'learnpress' );
				$action_by_status[ PostModel::STATUS_PENDING ] = __( 'Pending Review', 'learnpress' );
				$action_by_status[ PostModel::STATUS_DRAFT ]   = __( 'Draft', 'learnpress' );
				$action_by_status[ PostModel::STATUS_TRASH ]   = __( 'Trash', 'learnpress' );
			}
			// Unset current status on action
			unset( $action_by_status[ $course_status ] );

			$html_action_by_status = '';

			$data_send_action = [
				'id_url'    => 'cb-quick-edit-action',
				'action'    => 'cb_quick_edit_save_course',
				'course_id' => $courseModel->get_id(),
			];

			foreach ( $action_by_status as $action_status => $action_label ) {
				$html_action_by_status .= sprintf(
					'<span class="lp-cb-item-action %s" data-send="%s">%s</span>',
					$action_status,
					Template::convert_data_to_json( $data_send_action + [ 'action_type' => $action_status ] ),
					$action_label
				);
			}

			$html_action = apply_filters(
				'learn-press/course-builder/list-courses/item/action',
				[
					'wrapper'                => '<div class="lp-cb-item-action-wrap">',
					'edit'                   => $course_status !== PostModel::STATUS_TRASH ? sprintf(
						'<div class="course-action-editor"><a class="btn-edit-course course-edit-permalink" href="%s">%s</a></div>',
						$edit_link,
						__( 'Edit', 'learnpress' )
					) : '',
					'action_expanded_button' => sprintf(
						'<div class="lp-cb-item-action-expand-toggle lp-button">%s</div>',
						$more_actions_icon
					),
					'action_wrap'            => '<div class="lp-cb-item-action-expand lp-hidden">',
					'action'                 => $html_action_by_status,
					'action_wrap_end'        => '</div>',
					'wrapper_end'            => '</div>',
				],
				$courseModel,
				$settings
			);

			$section = apply_filters(
				'learn-press/course-builder/list-courses/item-li',
				[
					'wrapper_li'      => '<li class="course">',
					'wrapper_div'     => sprintf( '<div class="course-item" data-course-id="%s">', $courseModel->get_id() ),
					'media'           => Template::combine_components( $html_img ),
					'course_info'     => Template::combine_components( $html_content ),
					'course_action'   => Template::combine_components( $html_action ),
					'wrapper_div_end' => '</div>',
					'wrapper_li_end'  => '</li>',
				],
				$courseModel,
				$settings
			);

			$html_item = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}
}
