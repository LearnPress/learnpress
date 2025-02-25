<?php
/**
 * Class Block_Template_Feature_Review_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Feature_Review_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'feature-review-single-lp_course';
	public $name                          = 'learnpress/feature-review-single-course';
	public $title                         = 'Feature Review Course (LearnPress)';
	public $description                   = 'Feature Review Course Block Template';
	public $path_html_block_template_file = 'html/single-course/feature-review-single-course.html';
	public $single_course_func            = 'html_feature_review';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/feature-review-single-course.js';
}
