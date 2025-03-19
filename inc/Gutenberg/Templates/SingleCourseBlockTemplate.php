<?php
namespace LearnPress\Gutenberg\Templates;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class SingleCourseBlockTemplate extends AbstractBlockTemplate {
	public $slug                          = 'single-lp_course';
	public $title                         = 'Single Course Template';
	public $description                   = 'Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course-template-default.html';

	public function __construct() {
		parent::__construct();
	}
}
