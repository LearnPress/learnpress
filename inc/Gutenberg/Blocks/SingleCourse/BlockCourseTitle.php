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
		return '55555';
	}
}
