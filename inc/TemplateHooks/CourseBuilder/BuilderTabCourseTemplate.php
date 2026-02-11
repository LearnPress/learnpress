<?php
/**
 * Template hooks Tab Course in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder;

use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course_Filter;
use Throwable;

class BuilderTabCourseTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/course-builder/courses/layout', [ $this, 'html_tab_courses' ] );
	}

	public function html_tab_courses() {
		$list_course   = $this->tab_list_courses();
		$html_filter   = $this->html_filter_bar();

		$tab = [
			'wrapper'            => '',
			'filter_bar'         => $html_filter,
			'courses'            => $list_course,
			'wrapper_end'        => '</div>',
		];

		echo Template::combine_components( $tab );
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
			'draft'   => __( 'Draft', 'learnpress' ),
			'pending' => __( 'Pending', 'learnpress' ),
			'private' => __( 'Private', 'learnpress' ),
			'trash'   => __( 'Trash', 'learnpress' ),
		];

		// Items per page options
		$per_page_options = [ 10, 20, 50 ];

		// Build status dropdown HTML
		$status_options = '';
		foreach ( $statuses as $value => $label ) {
			$selected = ( $current_status === $value ) ? 'selected' : '';
			$status_options .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), $selected, esc_html( $label ) );
		}

		// Build per page dropdown HTML
		$per_page_html = '';
		foreach ( $per_page_options as $option ) {
			$selected = ( (int) $current_limit === $option ) ? 'selected' : '';
			$per_page_html .= sprintf( '<option value="%d" %s>%d</option>', $option, $selected, $option );
		}

		// Add New Course button
		$btn_add_new = sprintf(
			'<a href="%s" class="cb-btn-add-new">+ %s</a>',
			esc_url( CourseBuilder::get_link_add_new_course( CourseBuilder::POST_NEW ) ),
			__( 'Add New Course', 'learnpress' )
		);

		$filter = [
			'wrapper'        => sprintf( '<form class="cb-tab-filter-bar" method="get" action="%s">', esc_url( $link_tab ) ),
			'left_wrapper'   => '<div class="cb-filter-left">',
			'search'         => sprintf(
				'<div class="cb-filter-search">
					<i class="lp-icon-search"></i>
					<input type="search" name="c_search" placeholder="%s" value="%s">
				</div>',
				esc_attr__( 'Search by title', 'learnpress' ),
				esc_attr( $current_search )
			),
			'status'         => sprintf(
				'<select name="c_status" class="cb-filter-select">%s</select>',
				$status_options
			),
			'per_page_label' => sprintf( '<span class="cb-filter-label">%s</span>', __( 'Items per page', 'learnpress' ) ),
			'per_page'       => sprintf(
				'<select name="per_page" class="cb-filter-select">%s</select>',
				$per_page_html
			),
			'filter_btn'     => sprintf( '<button type="submit" class="cb-filter-btn">%s</button>', __( 'Filter', 'learnpress' ) ),
			'reset_btn'      => sprintf( '<a href="%s" class="cb-filter-reset">%s</a>', esc_url( $link_tab ), __( 'Reset', 'learnpress' ) ),
			'left_end'       => '</div>',
			'right_wrapper'  => '<div class="cb-filter-right">',
			'add_new'        => $btn_add_new,
			'right_end'      => '</div>',
			'wrapper_end'    => '</form>',
		];

		return Template::combine_components( $filter );
	}

	public function tab_list_courses(): string {
		$content = '';

		try {
			$user = UserModel::find( get_current_user_id(), true );
			// Query courses of user
			$filter            = new LP_Course_Filter();
			$param             = lp_archive_skeleton_get_args();
			$param['id_url']   = 'tab-list-courses';
			
			// Handle status filter - if empty, show all; otherwise filter by selected status
			$status_param = $param['c_status'] ?? '';
			if ( empty( $status_param ) ) {
				$param['c_status'] = 'publish,private,draft,pending,trash';
			}

			Courses::handle_params_for_query_courses( $filter, $param );
			$filter->post_author = $user->get_id();
			
			// Handle per_page parameter
			$per_page = isset( $param['per_page'] ) ? absint( $param['per_page'] ) : 20;
			$filter->limit = $per_page > 0 ? $per_page : 20;
			
			$filter->page        = $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1;
			$filter              = apply_filters( 'lp/course-builder/courses/query/filter', $filter, [] );

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

			$sections = apply_filters(
				'learn-press/course-builder/courses/sections',
				[
					'wrapper'     => '<div class="courses-builder__course-tab learn-press-courses">',
					'courses'     => $html_courses,
					'pagination'  => $this->courses_pagination( $filter->page, $filter->limit, $total_courses ),
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
				$course            = CourseModel::find( $course_obj->ID, true );
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
	 * @param CourseModel
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public static function render_course( CourseModel $course, array $settings = [] ): string {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$edit_link = BuilderTabCourseTemplate::instance()->get_link_edit( $course->get_id() );

			$html_img = apply_filters(
				'learn-press/course-builder/list-courses/item/section-top',
				[
					'wrapper'     => '<div class="course-thumbnail">',
					'link'        => sprintf( '<a href="%s">', $edit_link ),
					'img'         => $singleCourseTemplate->html_image( $course ),
					'link_end'    => '</a>',
					'wrapper_end' => '</div>',
				],
				$course,
				$settings
			);

			$meta_data = apply_filters(
				'learn-press/course-builder/list-courses/item/meta-data',
				[
					'duration' => $singleCourseTemplate->html_duration( $course ),
					'level'    => $singleCourseTemplate->html_level( $course ),
					'lesson'   => $singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT ),
					'quiz'     => $singleCourseTemplate->html_count_item( $course, LP_QUIZ_CPT ),
					'student'  => $singleCourseTemplate->html_count_student( $course ),
				],
				$course,
				$settings
			);

			if ( $course->is_offline() ) {
				$singleCourseOfflineTemplate = SingleCourseOfflineTemplate::instance();
				unset( $meta_data['quiz'] );
				unset( $meta_data['student'] );
				if ( ! empty( $meta_data['lesson'] ) ) {
					$meta_data['lesson'] = $singleCourseOfflineTemplate->html_lesson_info( $course, true );
				}

				$html_address = $singleCourseOfflineTemplate->html_address( $course );
				if ( ! empty( $html_address ) ) {
					$meta_data['address'] = $singleCourseOfflineTemplate->html_address( $course );
				}
			}

			$html_meta_data = '';
			if ( ! empty( $meta_data ) ) {
				foreach ( $meta_data as $k => $v ) {
					$html_meta_data .= sprintf( '<div class="meta-item meta-item-%s">%s</div>', $k, $v );
				}

				$html_meta_data = sprintf( '<div class="course-wrap-meta">%s</div>', $html_meta_data );
			}

			$html_status = sprintf( '<div class="course-status %1$s"><span>%1$s</span></div>', $course->get_status() );

			// Price
			$html_price = sprintf( '<div class="course-item-price-wrap">%s</div>', $singleCourseTemplate->html_price( $course ) );

			// Categories
			$html_categories = '';
			$categories = $course->get_categories();
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
			$post_obj = get_post( $course->get_id() );
			$html_last_updated = '';
			if ( $post_obj && ! empty( $post_obj->post_modified ) ) {
				$modified_time = strtotime( $post_obj->post_modified );
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
				$course,
				$settings
			);

			// Instructor with categories
			$instructor = $course->get_author_model();
			$instructor_name = $instructor ? $instructor->get_display_name() : '';
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
						'<h3 class="wap-course-title">%s</h3>',
						$singleCourseTemplate->html_title( $course )
					),
					'instructor'  => $html_instructor_category,
					'meta'        => $html_meta_data,
					'info'        => Template::combine_components( $section_bottom_end ),
					'wrapper_end' => '</div>',
				],
				$course,
				$settings
			);

			$html_action = apply_filters(
				'learn-press/course-builder/list-courses/item/action',
				[
					'wrapper'                     => '<div class="course-action">',
					'edit'                        => sprintf(
						'<div class="course-action-editor"><a class="btn-edit-course course-edit-permalink" href="%s">%s</a></div>',
						$edit_link,
						__( 'Edit', 'learnpress' )
					),
					'action_expanded_button'      => '<div class="course-action-expanded"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg></div>',
					'action_expanded_wrapper'     => '<div style="display:none;" class="course-action-expanded__items">',
					'action_expanded_view'        => sprintf( '<a class="course-action-expanded__view" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url_raw( $course->get_permalink() ), __( 'View', 'learnpress' ) ),
					'action_expanded_duplicate'   => sprintf( '<span class="course-action-expanded__duplicate">%s</span>', __( 'Duplicate', 'learnpress' ) ),
					'action_expanded_restore'     => sprintf( '<span class="course-action-expanded__draft">%s</span>', __( 'Draft', 'learnpress' ) ),
					'action_expanded_trash'       => sprintf( '<span class="course-action-expanded__trash">%s</span>', __( 'Trash', 'learnpress' ) ),
					'action_expanded_delete'      => sprintf( '<span class="course-action-expanded__delete" data-title="%s" data-content="%s">%s</span>', __( 'Are you sure?', 'learnpress' ), __( 'Are you sure you want to delete this course? This action cannot be undone.', 'learnpress' ), __( 'Delete', 'learnpress' ) ),
					'action_expanded_wrapper_end' => '</div>',
					'wrapper_end'                 => '</div>',
				],
				$course,
				$settings
			);

			$section = apply_filters(
				'learn-press/course-builder/list-courses/item-li',
				[
					'wrapper_li'      => '<li class="course">',
					'wrapper_div'     => sprintf( '<div class="course-item" data-course-id="%s">', $course->get_id() ),
					'media'           => Template::combine_components( $html_img ),
					'course_info'     => Template::combine_components( $html_content ),
					'course_action'   => Template::combine_components( $html_action ),
					'wrapper_div_end' => '</div>',
					'wrapper_li_end'  => '</li>',
				],
				$course,
				$settings
			);

			$html_item = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			$html_item = $e->getMessage();
		}

		return $html_item;
	}

	/**
	 * Pagination courses.
	 *
	 * @param int $page
	 * @param int $limit
	 * @param int $total_courses
	 *
	 * @return string
	 */
	public function courses_pagination( int $page, int $limit, int $total_courses ): string {
		$content = '';

		try {
			$total_pages = \LP_Database::get_total_pages( $limit, $total_courses );
			$link_tab    = CourseBuilder::get_tab_link( 'courses' );
			$base_url    = trailingslashit( $link_tab ) . 'page/%#%';

			$data_pagination = array(
				'total'    => $total_pages,
				'current'  => max( 1, $page ),
				'base'     => $base_url,
				'format'   => '',
				'per_page' => $limit,
			);

			ob_start();
			Template::instance()->get_frontend_template( 'shared/pagination.php', $data_pagination );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	public function get_link_edit( $course_id = 0 ) {
		if ( ! $course_id ) {
			return '';
		}

		$section  = CourseBuilder::get_current_section( '', 'courses' );
		$link_tab = CourseBuilder::get_tab_link( 'courses' );
		$link     = $link_tab . $course_id . '/' . $section;

		return $link;
	}
}
