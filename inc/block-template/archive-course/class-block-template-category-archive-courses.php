<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Category_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Category_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'category-archive-course';
	public $name                          = 'learnpress/category-archive-course';
	public $title                         = 'Category (LearnPress)';
	public $description                   = 'Category Block Template';
	public $path_html_block_template_file = 'html/list-course/category-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/category-archive-course.js';
}
