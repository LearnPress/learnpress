<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Instructor_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'instructor-archive-course';
	public $name                          = 'learnpress/instructor-archive-course';
	public $title                         = 'Instructor (LearnPress)';
	public $description                   = 'Instructor Block Template';
	public $path_html_block_template_file = 'html/list-course/instructor-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-archive-course.js';
}
