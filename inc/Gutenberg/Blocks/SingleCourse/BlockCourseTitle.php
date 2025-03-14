<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourse;

use LearnPress\Gutenberg\Blocks\BlockAbstract;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use WP_Block_Type;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class BlockCourseTitle extends BlockAbstract {
	public $name              = 'learnpress/course-title';
	public $title             = 'Course Title';
	public $description       = '';
	public $content           = '<!-- wp:learnpress/course-title /-->';
	public $source_js         = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-title.js';
	public $templates_display = [
		'learnpress/learnpress//single-lp_course',
	];

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
		$course_id            = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
		$courseModel          = CourseModel::find( $course_id, true );
		$singleCourseTemplate = SingleCourseTemplate::instance();

		$align = $attributes['align'] ?? '';

		switch ( $align ) {
			case 'wide':
				$align = 'alignwide';
				break;
			case 'full':
				$align = 'algignfull';
				break;
			case 'center':
				$align = 'aligncenter';
				break;
			default:
				$align = '';
				break;
		}

		$classes = [
			$align,
		];

		$m = get_block_wrapper_attributes( $attributes );

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes( $attributes ),
			$singleCourseTemplate->html_title( $courseModel )
		);
	}
}
