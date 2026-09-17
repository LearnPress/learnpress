<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuestionPostFilter
 *
 * Filter post type LP Question
 *
 * @version 1.0.0
 * @since 4.4.8
 */
class QuestionPostFilter extends PostFilter {
	public $post_type = LP_QUESTION_CPT;
}
