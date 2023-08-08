<?php
/**
 * Template hooks List Courses.
 *
 * @since 4.2.3.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Assets;
use LP_Page_Controller;
use LP_User;
use LP_WP_Filesystem;
use Throwable;
use WP_Query;

class ListCoursesTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/rest-api/courses/suggest/layout', [ $this, 'sections_course_suggest' ] );
		add_action( 'learn-press/archive-course/sidebar', [ $this, 'sidebar' ] );
	}

	/**
	 * Button Load more
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_pagination_load_more(): string {
		$html_wrapper = [
			'<button class="courses-btn-load-more learn-press-pagination lp-button">' => '</button>',
		];
		$content      = sprintf(
			'%s<span class="lp-loading-circle hide"></span>',
			__( 'Load more', 'learnpress' )
		);

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Button infinite
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_pagination_infinite(): string {
		$html_wrapper = [
			'<div class="courses-load-infinite learn-press-pagination">' => '</div>',
		];
		$content      = '<span class="lp-loading-circle hide"></span>';

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Pagination number
	 *
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.3.3
	 * @version 1.0.0
	 */
	public function html_pagination_number( array $data = [] ): string {
		if ( empty( $data['total_pages'] ) || $data['total_pages'] <= 1 ) {
			return '';
		}

		$html_wrapper = [
			'<nav class="learn-press-pagination navigation pagination">' => '</nav>',
		];

		$pagination = paginate_links(
			apply_filters(
				'learn_press_pagination_args',
				array(
					'base'      => $data['base'] ?? '',
					'format'    => '',
					'add_args'  => '',
					'current'   => max( 1, $data['paged'] ?? 1 ),
					'total'     => $data[ 'total_pages' ?? 1 ],
					'prev_text' => '<i class="fas fa-angle-left"></i>',
					'next_text' => '<i class="fas fa-angle-right"></i>',
					'type'      => 'list',
					'end_size'  => 3,
					'mid_size'  => 3,
				)
			)
		);

		return Template::instance()->nest_elements( $html_wrapper, $pagination );
	}

	/**
	 * Layout course search suggest result.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function sections_course_suggest( array $data = [] ) {
		$content              = '';
		$singleCourseTemplate = SingleCourseTemplate::instance();

		ob_start();
		try {
			$courses       = $data['courses'] ?? [];
			$key_search    = $data['keyword'] ?? '';
			$total_courses = $data['total_course'] ?? 0;

			// Section list courses.
			$html_item_wrapper = [
				'<ul class="lp-courses-suggest-list">' => '</ul>',
			];
			$list_course       = '';
			foreach ( $courses as $courseObj ) {
				if ( ! is_object( $courseObj ) ) {
					continue;
				}
				$course_id = $courseObj->ID;
				$course    = learn_press_get_course( $course_id );
				if ( ! $course ) {
					continue;
				}

				$item_wrapper  = [
					'<li class="item-course-suggest">' => '</li>',
				];
				$course_title  = sprintf(
					'<a href="%s">%s</a>',
					$course->get_permalink(),
					$singleCourseTemplate->html_title( $course )
				);
				$item_sections = apply_filters(
					'learn-press/course-suggest/item/sections',
					[
						'course_image' => [ 'text_html' => $singleCourseTemplate->html_image( $course ) ],
						'course_title' => [ 'text_html' => $course_title ],
					]
				);
				ob_start();
				Template::instance()->print_sections( $item_sections );
				$item_content = ob_get_clean();
				$list_course .= Template::instance()->nest_elements( $item_wrapper, $item_content );
			}
			$list_course = Template::instance()->nest_elements( $html_item_wrapper, $list_course );
			// End section list courses.

			// Section info search.
			$html_info_wrapper = [
				'<div class="lp-courses-suggest-info">' => '</div>',
			];
			$count_courses     = sprintf(
				'%s %s',
				$total_courses,
				_n( 'Course Found', 'Courses Found', $total_courses, 'learnpress' )
			);
			$view_all          = sprintf(
				'<a href="%s">%s</a>',
				add_query_arg( 'c_search', $key_search, learn_press_get_page_link( 'courses' ) ),
				__( 'View All', 'learnpress' )
			);
			$info_sections     = apply_filters(
				'learn-press/course-suggest/info/sections',
				[
					'count'    => [ 'text_html' => $count_courses ],
					'view_all' => [ 'text_html' => $view_all ],
				]
			);

			ob_start();
			Template::instance()->print_sections( $info_sections );
			$info_content = ob_get_clean();
			$info_content = Template::instance()->nest_elements( $html_info_wrapper, $info_content );
			// End section info search.

			$content = $list_course . $info_content;
			echo $content;
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Sidebar
	 *
	 * @since 4.2.3.2
	 * @version 1.0.0
	 * @return void
	 */
	public function sidebar() {
		try {
			if ( is_active_sidebar( 'archive-courses-sidebar' ) ) {
				$html_wrapper = [
					'<div class="lp-archive-courses-sidebar">' => '</div>',
				];

				ob_start();
				dynamic_sidebar( 'archive-courses-sidebar' );
				echo Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
