<?php
/**
 * Class Block_Template_Quiz_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Quiz_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'quiz-single-course';
	public $name                          = 'learnpress/quiz-single-course';
	public $title                         = 'Quiz Course (LearnPress)';
	public $description                   = 'Quiz Course Block Template';
	public $path_html_block_template_file = 'html/single-course/quiz-single-course.html';
	public $single_course_func            = 'html_count_item';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/quiz-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$attributes['itemType'] = 'lp_quiz';
		$order                  = [ 'courseId', 'itemType', 'showOnlyNumber' ];
		foreach ( $order as $key ) {
			$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
		}

		return parent::render_content_block_template( $sortedAttributes );
	}
}
