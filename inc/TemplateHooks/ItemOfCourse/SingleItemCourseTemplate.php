<?php
/**
 * Template hooks Single Item's Course.
 *
 * @since 4.2.6.9.
 * @version 1.0.1
 */

namespace LearnPress\TemplateHooks\ItemOfCourse;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;
use LP_Datetime;
use LP_Page_Controller;
use Throwable;
use WP_Post;

class SingleItemCourseTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/single-item-of-course/layout-no-header-footer', [
			$this,
			'layout_no_header_footer'
		] );
	}

	public function layout_no_header_footer() {
		$item_course_data = LP_Page_Controller::is_page_single_item();
		if ( ! $item_course_data ) {
			return;
		}

		$html_wrapper = [
			'<div id="popup-course" class="course-summary">' => '</div>',
		];
		$section      = apply_filters(
			'learn-press/single-item-of-course/layout-no-header-footer/section',
			[
				'header'  => [ 'text_html' => $this->header_section( $item_course_data ) ],
				'sidebar' => [ 'text_html' => $this->sidebar_section( $item_course_data ) ],
				'content' => [ 'text_html' => $this->content_section( $item_course_data ) ],
				'footer'  => [ 'text_html' => $this->footer_section( $item_course_data ) ],
			],
			$item_course_data
		);

		ob_start();
		Template::instance()->print_sections( $section );
		$content = ob_get_clean();

		echo Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get header html single item's course
	 *
	 * @param $item_course_data [ CourseModel, WP_POST ]
	 *
	 * @return string
	 */
	public function header_section( $item_course_data ): string {
		/**
		 * @var CourseModel $course
		 */
		$course = $item_course_data['course'] ?? false;
		/**
		 * @var WP_Post $item
		 */
		$item = $item_course_data['item'] ?? false;
		if ( empty( $course ) && empty( $item ) ) {
			return '';
		}

		$html_wrapper = [
			'<div id="popup-header">' => '</div>',
		];

		$btn_back = sprintf(
			'<a href="%s" class="back-course"><i class="lp-icon-times"></i></a>',
			$course->get_permalink()
		);
		$section  = [
			'btn-toggle' => [ 'text_html' => '<input type="checkbox" id="sidebar-toggle" title="Show/Hide curriculum" />' ],
			'btn-back'   => [ 'text_html' => $btn_back ],
		];

		ob_start();
		Template::instance()->print_sections( $section );
		$content = ob_get_clean();

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	public function sidebar_section( $item_course_data ) {
		return '';
	}

	public function content_section( $item_course_data ) {
		return '';
	}

	public function footer_section( $item_course_data ) {
		return '';
	}
}
