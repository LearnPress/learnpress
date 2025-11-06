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
		$list_course = $this->tab_list_courses();
		$hmtl_search = $this->html_search();
		$btn         = CourseBuilderTemplate::instance()->html_btn_add_new();

		$tab = [
			'wrapper'            => '',
			'wrapper_action'     => '<div class="cb-tab-course__action">',
			'search_course'      => $hmtl_search,
			'btn'                => $btn,
			'wrapper_action_end' => '</div>',
			'courses'            => $list_course,
			'wrapper_end'        => '</div>',
		];

		echo Template::combine_components( $tab );
	}

	public function html_search() {
		$args = lp_archive_skeleton_get_args();

		$search = [
			'wrapper'       => '<form class="cb-search-form" method="get" action="">',
			'search_course' => '<button class="cb-search-btn" type="submit"> <i class="lp-icon-search"> </i></button>',
			'input'         => sprintf( '<input class="cb-input-search-course" type="search" placeholder="%s" name="c_search" value="%s">', __( 'Search', 'learnpress' ), $args['c_search'] ?? '' ),
			'wrapper_end'   => '</form>',
		];

		return Template::combine_components( $search );
	}

	public function tab_list_courses(): string {
		$content = '';

		try {
			$user = UserModel::find( get_current_user_id(), true );
			// Query courses of user
			$filter            = new LP_Course_Filter();
			$param             = lp_archive_skeleton_get_args();
			$param['id_url']   = 'tab-list-courses';
			$param['c_status'] = 'publish,private,draft,pending,trash';

			Courses::handle_params_for_query_courses( $filter, $param );
			$filter->post_author = $user->get_id();
			$filter->limit       = 10;
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

			$section_bottom_end = apply_filters(
				'learn-press/course-builder/list-courses/item/section/bottom/end',
				[
					'wrapper'     => '<div class="course-bottom">',
					'status'      => $html_status,
					'wrapper_end' => '</div>',
				],
				$course,
				$settings
			);

			$html_content = apply_filters(
				'learn-press/course-builder/list-courses/item/section/bottom',
				[
					'wrapper'     => '<div class="course-content">',
					'title'       => sprintf(
						'<h3 class="wap-course-title"><a href="%s">%s</a></h3>',
						$edit_link,
						$singleCourseTemplate->html_title( $course )
					),
					'instructor'  => sprintf(
						'<div class="course-instructor__wrapper">%s %s</div>',
						sprintf( '<label>%s</label>', __( 'icon', 'learnpress' ) ),
						$singleCourseTemplate->html_instructor( $course, false, [ 'is_link' => false ] )
					),
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
						'<div class="course-action-editor"><a class="btn-edit-course course-edit-permalink" href="%s">%s %s</a></div>',
						$edit_link,
						'<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
						__( 'Edit', 'learnpress' )
					),
					'action_expanded_button'      => '<div class="course-action-expanded"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg></div>',
					'action_expanded_wrapper'     => '<div style="display:none;" class="course-action-expanded__items">',
					'action_expanded_view'        => sprintf( '<a class="course-action-expanded__view" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url_raw( $course->get_permalink() ), __( 'View', 'learnpress' ) ),
					'action_expanded_duplicate'   => '<span class="course-action-expanded__duplicate">Duplicate</span>',
					'action_expanded_delete'      => '<span class="course-action-expanded__delete">Delete</span>',
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
