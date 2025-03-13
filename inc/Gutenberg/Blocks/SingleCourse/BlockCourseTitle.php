<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourse;

use LearnPress\Gutenberg\Blocks\BlockAbstract;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class BlockCourseTitle extends BlockAbstract {
	public $name        = 'learnpress/course-title';
	public $title       = 'Course Title';
	public $description = '';
	public $content     = '<!-- wp:learnpress/course-title /-->';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-title.js';
	public $templates_display = [
		'learnpress/learnpress//single-lp_course'
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
	public function render_content_block_template( array $attributes ) {
		$align   = $attributes['align'] ?? '';

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

		$class_string = esc_attr( implode( ' ', $classes ) );
		return '<div class="' . $class_string . '">55555</div>';
	}
}
