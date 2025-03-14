<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourse;

use LearnPress\Gutenberg\Blocks\BlockAbstract;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use Throwable;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class BlockSingleCourseTemplate extends BlockAbstract {
	public $slug                          = 'single-lp_course';
	public $name                          = 'learnpress/single-course';
	public $title                         = 'Single Course Template';
	public $description                   = 'Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/single-course.html';
	public $path_template_render_default  = 'single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/single-course.js';
	public $is_show_on_template_list      = true;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ) {
		try {
			$attributes['courseId'] = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
			$courseModel            = CourseModel::find( $attributes['courseId'], true );

			$singleCourseTemplate = SingleCourseTemplate::instance();
			//echo $singleCourseTemplate->html_title( $courseModel );
			return sprintf(
				'<div %s></div>',
				get_block_wrapper_attributes( $attributes )
			);
		} catch ( Throwable $e ) {
			\LP_Debug::error_log( $e );
		}
	}
}
