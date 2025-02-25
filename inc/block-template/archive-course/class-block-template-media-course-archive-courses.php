<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Media_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Media_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'media-course-archive-course';
	public $name                          = 'learnpress/media-course-archive-course';
	public $title                         = 'Media Course (LearnPress)';
	public $description                   = 'Media Course Block Template';
	public $path_html_block_template_file = 'html/list-course/media-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/media-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{media-course}}';
		return $output;
	}
}
