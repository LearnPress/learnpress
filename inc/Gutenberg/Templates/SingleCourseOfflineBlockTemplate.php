<?php
namespace LearnPress\Gutenberg\Templates;

/**
 * Class SingleCourseOfflineBlockTemplate
 *
 * Handle register, render block template
 */
class SingleCourseOfflineBlockTemplate extends AbstractBlockTemplate {
	public $slug                          = 'single-lp_course-offline';
	public $title                         = 'Single Course Offline Template';
	public $description                   = 'Single Course Offline Block Template';
	public $path_html_block_template_file = 'html/single-course-offline-template-default.html';

	public function __construct() {
		parent::__construct();
	}
}
