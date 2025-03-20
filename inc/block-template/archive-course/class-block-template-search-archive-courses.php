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
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$data             = [];
		$settings         = [];
		$settings         = array_merge(
			$settings,
			lp_archive_skeleton_get_args()
		);
		$data['settings'] = $settings;
		return parent::render_content_block_template( $data );
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.lp-courses-bar .search-courses button[name="lp-btn-search-courses"],
		.lp-courses-bar .search-courses input[name="c_search"] {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}
