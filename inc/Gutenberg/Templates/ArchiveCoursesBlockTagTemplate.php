<?php
namespace LearnPress\Gutenberg\Templates;

/**
 * Class ArchiveCoursesBlockTagTemplate
 *
 * @since 4.2.8.2
 */
class ArchiveCoursesBlockTagTemplate extends AbstractBlockTemplate {
	public $slug                          = 'taxonomy-course_tag';
	public $title                         = 'Courses by Tag';
	public $description                   = 'Displays courses filtered by a tag';
	public $path_html_block_template_file = 'html/archive-courses-template-default.html';

	public function __construct() {
		parent::__construct();
	}
}
