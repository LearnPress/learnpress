<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class CoursePostFilter
 *
 * Filter post type LP Course
 *
 * @version 1.0.0
 * @since 4.4.8
 */
class CoursePostFilter extends PostFilter {
	public $post_type = LP_COURSE_CPT;
}
