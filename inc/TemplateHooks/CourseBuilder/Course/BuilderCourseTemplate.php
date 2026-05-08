<?php
/**
 * Template hooks Tab Course in Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\CourseBuilder\Course;

use Exception;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;

class BuilderCourseTemplate {
	use Singleton;

	public function init() {}

	/**
	 * Check query var to switch layout.
	 *
	 * @param array $data
	 *
	 * @since 4.3.6
	 * @version 1.0.1
	 * @return void
	 * @throws Exception
	 */
	public function layout( array $data = [] ): string {
		// Check to switch layout.
		$item_id         = CourseBuilder::get_item_id();
		$data['item_id'] = $item_id;
		$html            = '';

		if ( ! empty( $item_id ) ) {
			// Show edit course
			$html = BuilderEditCourseTemplate::instance()->layout( $data );
		} else {
			// Show list courses
			$html = BuilderListCoursesTemplate::instance()->layout( $data );
		}

		return $html;
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
