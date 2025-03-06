<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Categories_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Categories_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'categories-single-lp_course';
	public $name                          = 'learnpress/categories-single-course';
	public $title                         = 'List Categories Single Course (LearnPress)';
	public $description                   = 'List Categories Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/categories-single-course.html';
	public $single_course_func            = 'html_categories';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/categories-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content         = '';
		$course          = CourseModel::find( get_the_ID(), true );
		$html_categories = SingleCourseTemplate::instance()->html_categories( $course );
		if ( ! empty( $html_categories ) ) {
			$content = sprintf(
				'<div>%s %s</div>',
				sprintf( '<label>%s</label>', __( 'in', 'learnpress' ) ),
				$html_categories
			);
		}

		return $content;
	}
}
