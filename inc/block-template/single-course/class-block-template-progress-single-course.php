<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Progress_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Progress_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'progress-single-course';
	public $name                          = 'learnpress/progress-single-course';
	public $title                         = 'Progress Single Course (LearnPress)';
	public $description                   = 'Progress Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/progress-single-course.html';
	public $single_course_func            = 'html_progress';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/progress-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		if ( $attributes['layout'] === 'modern' ) {
			$course = CourseModel::find( get_the_ID(), true );
			$user   = UserModel::find( get_current_user_id(), true );
			ob_start();
			echo SingleCourseModernLayout::instance()->html_info_learning( $course, $user );
			$content = ob_get_clean();
		} else {
			$content = parent::render_content_block_template( $attributes );
		}

		return $content;
	}
}
