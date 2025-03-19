<?php
/**
 * Declare block type elements
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */
use LearnPress\Gutenberg\Blocks\Courses\BlockArchiveCourseLegacy;
use LearnPress\Gutenberg\Blocks\SingleCourse\BlockSingleCourseLegacy;
use learnpress\inc\Gutenberg\Templates\SingleCourseBlockTemplate;

return apply_filters(
	'learn-press/config/block-elements',
	array(
//		new BlockArchiveCourseLegacy(),
//		new BlockSingleCourseLegacy(),
	)
);
