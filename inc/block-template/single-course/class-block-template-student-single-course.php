<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */

class Block_Template_Student_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'student-single-lp_course';
	public $name                          = 'learnpress/student-single-course';
	public $title                         = 'Student Course (LearnPress)';
	public $description                   = 'Student Course Block Template';
	public $path_html_block_template_file = 'html/single-course/student-single-course.html';
	public $single_course_func            = 'html_count_student';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/student-single-course.js';
}
