<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourse;

use LearnPress\Gutenberg\Blocks\BlockAbstract;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class BlockSingleCourseItem extends BlockAbstract {
	public $name        = 'learnpress/single-course-item';
	public $title       = 'Course Item';
	public $description = '';
	public $content     = '<!-- wp:learnpress/course-title /-->';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/single-course-item.js';

	public function __construct() {
		parent::__construct( $this->name, [] );
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ) {
		return 'Single Course Item';
	}
}
