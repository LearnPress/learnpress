<?php

namespace LearnPress\Shortcodes\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LP_Course_Filter;

/**
 * Class ListCourseRecentShortcode
 *
 * @package LearnPress\Shortcodes
 * @since 4.2.6.4
 * @version 1.0.0
 */
class ListCourseRecentShortcode extends AbstractListCourseShortcode {
	use singleton;
	protected $shortcode_name = 'recent_courses';

	/**
	 * @param array $attrs
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		if ( ! is_array( $attrs ) ) {
			$attrs = [];
		}

		$filter = new LP_Course_Filter();
		$filter->only_fields = ['ID'];
		$filter->limit = 5;
		$courses = Courses::get_courses( $filter );
		$attrs['courses'] = $courses;

		return parent::render( $attrs );
	}
}

