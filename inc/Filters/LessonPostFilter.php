<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class LessonPostFilter
 *
 * Filter post type LP Lesson
 *
 * @version 1.0.0
 * @since 4.4.8
 */
class LessonPostFilter extends PostFilter {
	public $post_type = LP_LESSON_CPT;
}
