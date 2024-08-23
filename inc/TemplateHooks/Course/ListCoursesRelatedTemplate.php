<?php
/**
 * Template hooks List Courses.
 *
 * @since 4.2.7
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Course;
use LP_Course_Filter;
use LP_Database;
use LP_Request;
use LP_Settings;
use LP_Settings_Courses;
use LP_User_Items_DB;
use LP_User_Items_Filter;
use stdClass;
use Throwable;
use WP_Term;
use function Symfony\Component\String\s;

class ListCoursesRelatedTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/single-course/courses-related/layout', [ $this, 'layout_courses' ], 10, 2 );
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	public function allow_callback( $callbacks ) {
		$callbacks[] = get_class( $this ) . ':render_courses';

		return $callbacks;
	}

	/**
	 * Layout default list courses.
	 *
	 * @return void
	 * @since 4.2.7
	 * @version 1.0.0
	 */
	public function layout_courses( CourseModel $course, $limit ) {
		$html_wrapper = [
			'<div class="lp-list-courses-related">' => '</div>',
		];

		$callback = [
			'class'  => get_class( $this ),
			'method' => 'render_courses',
		];

		$args = [
			'course_id' => $course->get_id(),
			'limit'     => $limit
		];

		$content = TemplateAJAX::load_content_via_ajax( $args, $callback );

		echo Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Render template list courses with settings param.
	 *
	 * @param array $settings
	 *
	 * @return stdClass { content: string_html }
	 * @since 4.2.7
	 * @version 1.0.0
	 */
	public static function render_courses( array $settings = [] ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		$filter    = new LP_Course_Filter();
		$course_id = $settings['course_id'] ?? 0;
		if ( empty( $course_id ) ) {
			return $content;
		}

		$course   = CourseModel::find( $course_id, true );
		$terms    = $course->get_categories();
		$term_ids = [];

		foreach ( $terms as $term ) {
			$term_ids[] = $term->term_id ?? 0;
			$term_ids[] = $term->parent ?? 0;
		}

		$total_rows       = 0;
		$filter->limit    = $settings['limit'];
		$filter->term_ids = $term_ids;

		$courses = Courses::get_courses( $filter, $total_rows );
		if ( empty( $courses ) ) {
			return $content;
		}

		// Handle layout
		$html_ul_courses = [
			'<ul class="lp-courses-related">' => '</ul>',
		];

		ob_start();
		foreach ( $courses as $courseObj ) {
			$course = CourseModel::find( $courseObj->ID, true );
			if ( ! $course instanceof CourseModel ) {
				continue;
			}
			echo static::render_course( $course, $settings );
		}
		$html_courses = Template::instance()->nest_elements( $html_ul_courses, ob_get_clean() );

		$sections         = apply_filters(
			'learn-press/list-courses/related/sections',
			[
				'header'  => sprintf( '<h3>%s</h3>', __( 'You might be interested in' ) ),
				'courses' => $html_courses,
			],
			$course, $courses, $settings
		);
		$content->content = Template::combine_components( $sections );

		return $content;
	}

	/**
	 * Render single item course
	 *
	 * @param LP_Course $course
	 * @param array $settings
	 *
	 * @return string
	 * @since 4.2.5.8
	 * @version 1.0.1
	 */
	public static function render_course( CourseModel $course, array $settings = [] ): string {
		$singleCourseTemplate = SingleCourseTemplate::instance();

		try {
			$top_wrapper = [
				'<div class="course-wrap-thumbnail">' => '</div>',
				'<div class="course-thumbnail">'      => '</div>',
			];
			$img         = sprintf( '<a href="%s">%s</a>', $course->get_permalink(), $singleCourseTemplate->html_image( $course ) );
			$html_top    = Template::instance()->nest_elements( $top_wrapper, $img );


			// Section main top
			$section_main_top = [
				'wrapper_start' => '<div class="course-content-top">',
				'level'         => $singleCourseTemplate->html_level( $course ),
				'price'         => $singleCourseTemplate->html_price( $course ),
				'wrapper_end'   => '</div>',
			];
			$html_main_top    = Template::combine_components( $section_main_top );
			// End section main top

			// Section main bottom
			$html_count_student  = $singleCourseTemplate->html_count_student( $course );
			$section_main_bottom = [
				'wrapper_start' => '<div class="course-content-bottom">',
				'students'      => $html_count_student,
				'lessons'       => $singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT ),
				'wrapper_end'   => '</div>',
			];
			$html_main_bottom    = Template::combine_components( $section_main_bottom );
			// End section main bottom

			$section_main      = apply_filters(
				'learn-press/list-courses/related/layout/item/section/bottom',
				[
					'wrapper_start' => '<div class="course-content">',
					'top'           => $html_main_top,
					'category'      => $singleCourseTemplate->html_categories( $course ),
					'title'         => sprintf( '<a href="%s">%s</a>', $course->get_permalink(), $singleCourseTemplate->html_title( $course, 'h4' ) ),
					'bottom'        => $html_main_bottom,
					'wrapper_end'   => '</div>',
				],
				$course,
				$settings
			);
			$html_section_main = Template::combine_components( $section_main );

			$section   = apply_filters(
				'learn-press/list-courses/related/layout/item/section',
				[
					'wrapper_start' => sprintf( '<li class="course-item" data-id="%s">', esc_attr( $course->get_id() ) ),
					'top'           => $html_top,
					'bottom'        => $html_section_main,
					'wrapper_end'   => '</li>'
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
}
