<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Info_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Info_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'info-course-archive-course';
	public $name                          = 'learnpress/info-course-archive-course';
	public $title                         = 'Info Course (LearnPress)';
	public $description                   = 'Info Course Block Template';
	public $path_html_block_template_file = 'html/list-course/info-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/info-course-archive-course.js';
}
