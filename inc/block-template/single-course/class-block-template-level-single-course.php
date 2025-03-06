<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Level_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Level_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'level-single-lp_course';
	public $name                          = 'learnpress/level-single-course';
	public $title                         = 'Level Course (LearnPress)';
	public $description                   = 'Level Course Block Template';
	public $path_html_block_template_file = 'html/single-course/level-single-course.html';
	public $single_course_func            = 'html_level';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/level-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';
		$course  = CourseModel::find( get_the_ID(), true );
		$value   = SingleCourseTemplate::instance()->html_level( $course );
		$label   = __( 'Level', 'learnpress' );
		ob_start();
		echo sprintf(
			'<div class="info-meta-item">
					<span class="info-meta-left"><i class="lp-icon-signal"></i>%s:</span>
					<span class="info-meta-right"><div class="course-count-student">%s</div></span>
				</div>',
			$label,
			$value
		);

		$content = ob_get_clean();
		return $content;
	}
}
