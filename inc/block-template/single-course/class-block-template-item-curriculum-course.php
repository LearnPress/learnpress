<?php

/**
 * Class Block_Template_Item_Curriculum_Course
 *
 * Handle register, render block template
 */
class Block_Template_Item_Curriculum_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug               = 'item-curriculum-course';
	public $name               = 'learnpress/item-curriculum-course';
	public $title              = 'Item curriculum Course (LearnPress)';
	public $description        = 'Item Curriculum Course Block Template';
	public $single_course_func = 'html_content_item';
	public $source_js          = LP_PLUGIN_URL . 'assets/js/dist/blocks/item-curriculum-course.js';
}
