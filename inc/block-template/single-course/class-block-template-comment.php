<?php
/**
 * Class Block_Template_Comment
 *
 * Handle register, render block template
 */
class Block_Template_Comment extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'comment';
	public $name                          = 'learnpress/comment';
	public $title                         = 'Comment (LearnPress)';
	public $description                   = 'Comment Block Template';
	public $path_html_block_template_file = 'html/single-course/comment.html';
	public $single_course_func            = 'html_comment';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/comment.js';
}
