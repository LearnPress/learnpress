<?php
namespace LearnPress\Gutenberg\Templates;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class SingleCourseItemBlockTemplate extends AbstractBlockTemplate {
	public $slug                          = 'single-lp_course_item';
	public $title                         = 'Single Course Item Template';
	public $description                   = 'Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course-item-template-default.html';

	public function __construct() {
		parent::__construct();
	}
}
