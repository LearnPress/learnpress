<?php
/**
 * Class Block_Template_Search_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Search_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'search-archive-course';
	public $name                          = 'learnpress/search-archive-course';
	public $title                         = 'Search (LearnPress)';
	public $description                   = 'Search Block Template';
	public $path_html_block_template_file = 'html/list-course/search-archive-course.html';
	public $single_course_func            = 'html_search_form';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/search-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$settings               = [];
		$settings               = array_merge(
			$settings,
			lp_archive_skeleton_get_args()
		);
		$attributes['settings'] = $settings;

		return parent::render_content_block_template( $attributes );
	}
}
