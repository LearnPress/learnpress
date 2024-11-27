<?php
/**
 * Class Block_Template_Order_By_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Order_By_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'order-by-archive-course';
	public $name                          = 'learnpress/order-by-archive-course';
	public $title                         = 'Order By (LearnPress)';
	public $description                   = 'Order By Block Template';
	public $path_html_block_template_file = 'html/list-course/order-by-archive-course.html';
	public $single_course_func            = 'html_order_by';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/order-by-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$settings = [];
		$settings = array_merge(
			$settings,
			lp_archive_skeleton_get_args()
		);

		$attributes['order_by'] = $settings['order_by'] ?? 'post_date';

		return parent::render_content_block_template( $attributes );
	}
}
