<?php
/**
 * Class Block_Template_Btn_Purchase_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Btn_Purchase_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'btn-purchase-single-lp_course';
	public $name                          = 'learnpress/btn-purchase-single-course';
	public $title                         = 'Button Purchase Course (LearnPress)';
	public $description                   = 'Button Purchase Course Block Template';
	public $path_html_block_template_file = 'html/single-course/btn-purchase-single-course.html';
	public $single_course_func            = 'html_btn_purchase_course';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/btn-purchase-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$order = [ 'courseId', 'user' ];

		foreach ( $order as $key ) {
			$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
		}

		return parent::render_content_block_template( $sortedAttributes );
	}
}
