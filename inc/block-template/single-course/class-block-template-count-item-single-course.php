<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Count_Item_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'count-item-single-lp_course';
	public $name                          = 'learnpress/count-item-single-course';
	public $title                         = 'Count Item Course (LearnPress)';
	public $description                   = 'Count Item Course Block Template';
	public $path_html_block_template_file = 'html/single-course/count-item-single-course.html';
	public $single_course_func            = 'html_count_item';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/count-item-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$order = [ 'courseId', 'itemType', 'showOnlyNumber' ];

		foreach ( $order as $key ) {
			$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
		}

		return parent::render_content_block_template( $sortedAttributes );
	}
}
