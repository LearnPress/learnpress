<?php
/**
 * Class Block_Template_Lesson_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Lesson_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'lesson-single-course';
	public $name                          = 'learnpress/lesson-single-course';
	public $title                         = 'Lesson Course (LearnPress)';
	public $description                   = 'Lesson Course Block Template';
	public $path_html_block_template_file = 'html/single-course/lesson-single-course.html';
	public $single_course_func            = 'html_count_item';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lesson-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$attributes['itemType'] = 'lp_lesson';
		$order                  = [ 'courseId', 'itemType', 'showOnlyNumber' ];
		foreach ( $order as $key ) {
			$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
		}

		return parent::render_content_block_template( $sortedAttributes );
	}
}
