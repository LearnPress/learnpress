<?php
namespace LearnPress\Gutenberg\Templates;

/**
 * Class ArchiveCoursesBlockCategoryTemplate
 *
 * @since 4.2.8.2
 */
class ArchiveCoursesBlockCategoryTemplate extends AbstractBlockTemplate {
	public $slug                          = 'taxonomy-course_category';
	public $title                         = 'Courses by category';
	public $description                   = 'Displays courses filtered by a category';
	public $path_html_block_template_file = 'html/archive-courses-template-default.html';

	public function __construct() {
		parent::__construct();
	}
}
