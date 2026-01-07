<?php

namespace LearnPress\Shortcodes\Courses;

use LearnPress\Databases\DataBase;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Shortcodes\AbstractShortcode;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LP_Debug;
use Throwable;

/**
 * Shortcode display list courses.
 *
 * @since 4.3.2.3
 * @version 1.0.1
 */
class ListCoursesShortcode extends AbstractShortcode {
	use singleton;

	protected $shortcode_name = 'courses';

	/**
	 * Show single instructor
	 *
	 * @param $attrs [ 'limit', 'c_status', 'order_by', 'order', '' ]
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content = '';
		wp_enqueue_style( 'learnpress' );
		wp_enqueue_script( 'lp-courses-v2' );

		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}

			$attrs['c_status'] = $attrs['c_status'] ?? 'publish';
			$filter            = new CourseJsonFilter();
			Courses::handle_params_for_query_list_courses( $filter, $attrs );
			$filter->only_fields = [ CourseJsonFilter::COL_ID ];

			$total_rows                = 0;
			$courses                   = Courses::get_list_courses( $filter, $total_rows );
			$total_pages               = DataBase::get_total_pages( $filter->limit, $total_rows );
			$attrs['total_pages']      = $total_pages;
			$attrs['total_rows']       = $total_rows;
			$attrs['courses_per_page'] = $filter->limit;
			$skin                      = $attrs['skin'] ?? 'list';
			$paged                     = $attrs['paged'] ?? 1;
			$attrs['paged']            = $paged;
			$listCoursesTemplate       = self::instance();

			// HTML section courses.
			$html_courses = '';
			if ( empty( $courses ) ) {
				$html_courses = Template::print_message( __( 'No courses found', 'learnpress' ), 'info', false );
			} else {
				foreach ( $courses as $courseObj ) {
					$course        = CourseModel::find( $courseObj->ID, true );
					$html_courses .= ListCoursesTemplate::render_course( $course, $attrs );
				}
			}

			$section_courses = apply_filters(
				'learn-press/layout/list-courses/shortcode/section',
				[
					'wrap'     => sprintf(
						'<ul class="learn-press-courses lp-list-courses-no-css %1$s learn-press-courses-shortcode" data-layout="%1$s">',
						$skin
					),
					'courses'  => $html_courses,
					'wrap_end' => '</ul>',
				],
				$courses,
				$attrs
			);

			$content = Template::combine_components( $section_courses );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $content;
	}
}
