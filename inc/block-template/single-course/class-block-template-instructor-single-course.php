<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Instructor_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'instructor-single-lp_course';
	public $name                          = 'learnpress/instructor-single-course';
	public $title                         = 'Instructor Single Course (LearnPress)';
	public $description                   = 'Instructor Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/instructor-single-course.html';
	public $single_course_func            = 'html_instructor';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$course                      = CourseModel::find( get_the_ID(), true );
		$singleCourseClassicTemplate = SingleCourseClassicTemplate::instance();

		$layout_single_course = LP_Settings::get_option( 'layout_single_course', 'classic' );
		if ( $layout_single_course === 'modern' ) {
			$content = sprintf(
				'<div>%s %s</div>',
				sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) ),
				SingleCourseTemplate::instance()->html_instructor( $course )
			);
		} else {
			ob_start();
			echo $singleCourseClassicTemplate->html_instructor( $course );
			$content = ob_get_clean();
		}

		return $content;
	}
}
