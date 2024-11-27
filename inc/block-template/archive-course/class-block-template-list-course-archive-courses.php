<?php
/**
 * Class Block_Template_List_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_List_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'list-course-archive-course';
	public $name                          = 'learnpress/list-course-archive-course';
	public $title                         = 'List Course (LearnPress)';
	public $description                   = 'List Course Block Template';
	public $path_html_block_template_file = 'html/list-course/list-course-archive-course.html';
	public $single_course_func            = 'html_list_courses';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/list-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$settings = [];
		$settings = array_merge(
			$settings,
			lp_archive_skeleton_get_args()
		);

		$attributes['settings'] = $settings;

		return parent::render_content_block_template( $attributes );
	}
}
