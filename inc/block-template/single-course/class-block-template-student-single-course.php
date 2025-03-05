<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Student_Single_Course
 *
 * Handle register, render block template
 */

class Block_Template_Student_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'student-single-lp_course';
	public $name                          = 'learnpress/student-single-course';
	public $title                         = 'Student Course (LearnPress)';
	public $description                   = 'Student Course Block Template';
	public $path_html_block_template_file = 'html/single-course/student-single-course.html';
	public $single_course_func            = 'html_count_student';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/student-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		if ( $attributes['layout'] === 'modern' ) {
			$course = CourseModel::find( get_the_ID(), true );
			$value  = SingleCourseTemplate::instance()->html_count_student( $course );
			$label  = __( 'Student', 'learnpress' );
			ob_start();
			echo sprintf(
				'<div class="info-meta-item">
						<span class="info-meta-left"><i class="lp-icon-user-graduate"></i>%s:</span>
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
