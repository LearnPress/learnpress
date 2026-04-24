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

	public function init() {
		add_action( 'learn-press/course-builder/courses/layout', [ $this, 'layout' ] );
	}

	/**
	 * Check query var to switch layout.
	 *
	 * @param array $data
	 *
	 * @return void
	 * @throws Exception
	 */
	public function layout( array $data = [] ) {
		// Check to switch layout.
		$item_id         = CourseBuilder::get_item_id();
		$data['item_id'] = $item_id;

		if ( ! empty( $item_id ) ) {
			// Show edit course
			BuilderEditCourseTemplate::instance()->layout( $data );
		} else {
			// Show list courses
			BuilderListCoursesTemplate::instance()->layout( $data );
		}
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
