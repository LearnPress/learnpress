<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Duration_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Duration_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'duration-single-lp_course';
	public $name                          = 'learnpress/duration-single-course';
	public $title                         = 'Duration Course (LearnPress)';
	public $description                   = 'Duration Course Block Template';
	public $path_html_block_template_file = 'html/single-course/duration-single-course.html';
	public $single_course_func            = 'html_duration';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/duration-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content              = '';
		$layout_single_course = LP_Settings::get_option( 'layout_single_course', 'classic' );
		if ( $layout_single_course === 'modern' ) {
			$course = CourseModel::find( get_the_ID(), true );
			$value  = SingleCourseTemplate::instance()->html_duration( $course );
			$label  = __( 'Duration', 'learnpress' );
			ob_start();
			echo sprintf(
				'<div class="info-meta-item">
						<span class="info-meta-left"><i class="lp-icon-clock-o"></i>%s:</span>
						<span class="info-meta-right"><div class="course-count-student">%s</div></span>
					</div>',
				$label,
				$value
			);

			$content = ob_get_clean();
			return $content;
		} else {
			return parent::render_content_block_template( $attributes );
		}
	}
}
