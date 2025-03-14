<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

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

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content = '';
		$course  = CourseModel::find( get_the_ID(), true );

		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'margin', 'padding' ] );
		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			SingleCourseTemplate::instance()->html_comment( $course )
		);
		$content = ob_get_clean();

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'padding', 'margin' ] );
		return '.lp-single-course .lp-course-comment {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}
