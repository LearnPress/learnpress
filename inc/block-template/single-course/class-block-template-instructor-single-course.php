<?php
/**
 * Class Block_Template_Instructor_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'instructor-single-lp_course';
	public $name                          = 'learnpress/instructor-single-course';
	public $title                         = 'Instructor Single Course (LearnPress)';
	public $description                   = 'Instructor Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/instructor-single-course.html';
	public $single_course_func            = 'html_instructor';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$order = [ 'courseId', 'avatar' ];

		foreach ( $order as $key ) {
			$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
		}

		return parent::render_content_block_template( $sortedAttributes );
	}
}
