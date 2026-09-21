<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuizPostFilter
 *
 * Filter post type LP Quiz
 *
 * @version 1.0.0
 * @since 4.4.8
 */
class QuizPostFilter extends PostFilter {
	public $post_type = LP_QUIZ_CPT;
}
