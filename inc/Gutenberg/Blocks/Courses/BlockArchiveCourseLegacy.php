<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\BlockAbstract;

/**
 * Class BlockArchiveCourseLegacy
 *
 * Block Archive Course Legacy
 */
class BlockArchiveCourseLegacy extends BlockAbstract {
	public $slug                          = 'archive-lp_course';
	public $name                          = 'learnpress/archive-course-legacy';
	public $title                         = 'Course Archive (Legacy)';
	public $description                   = 'Course Archive Legacy Block Template';
	public $path_html_block_template_file = 'html/courses/archive-course-legacy.html';
	public $path_template_render_default  = 'archive-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/archive-course-legacy.js';

	public function __construct() {
		parent::__construct();
	}
}
