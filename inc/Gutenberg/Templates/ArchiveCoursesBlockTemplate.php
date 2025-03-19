<?php
namespace LearnPress\Gutenberg\Templates;

/**
 * Class ArchiveCoursesBlockTemplate
 *
 * @since 4.2.8.2
 */
class ArchiveCoursesBlockTemplate extends AbstractBlockTemplate {
	public $slug                          = 'archive-lp_course';
	public $title                         = 'Archive Courses Template';
	public $description                   = 'Archive Course Block Template';
	public $path_html_block_template_file = 'html/archive-courses-template-default.html';

	public function __construct() {
		parent::__construct();
	}
}
