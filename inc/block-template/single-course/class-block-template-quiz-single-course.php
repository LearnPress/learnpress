<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Quiz_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Quiz_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'quiz-single-course';
	public $name                          = 'learnpress/quiz-single-course';
	public $title                         = 'Quiz Course (LearnPress)';
	public $description                   = 'Quiz Course Block Template';
	public $path_html_block_template_file = 'html/single-course/quiz-single-course.html';
	public $single_course_func            = 'html_count_item';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/quiz-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';
		$course  = CourseModel::find( get_the_ID(), true );
		$value   = SingleCourseTemplate::instance()->html_count_item( $course, LP_QUIZ_CPT );
		$label   = __( 'Quiz', 'learnpress' );
		ob_start();
		echo sprintf(
			'<div class="info-meta-item">
					<span class="info-meta-left"><i class="lp-icon-puzzle-piece"></i>%s:</span>
					<span class="info-meta-right"><div class="course-count-student">%s</div></span>
				</div>',
			$label,
			$value
		);

		$content = ob_get_clean();
		return $content;
	}
}
