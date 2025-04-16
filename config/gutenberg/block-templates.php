<?php
/**
 * Declare block type template
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */

use LearnPress\Gutenberg\Templates\ArchiveCoursesBlockTemplate;
use LearnPress\Gutenberg\Templates\ArchiveCoursesBlockCategoryTemplate;
use LearnPress\Gutenberg\Templates\ArchiveCoursesBlockTagTemplate;
use LearnPress\Gutenberg\Templates\SingleCourseBlockTemplate;

return apply_filters(
	'learn-press/config/block-templates',
	[
		new SingleCourseBlockTemplate(),
		new ArchiveCoursesBlockTemplate(),
		new ArchiveCoursesBlockCategoryTemplate(),
		new ArchiveCoursesBlockTagTemplate(),
	]
);
