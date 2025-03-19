<?php
/**
 * Declare block type elements
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */
use LearnPress\Gutenberg\Blocks\Legacy\SingleCourseBlockLegacy;
use LearnPress\Gutenberg\Blocks\Legacy\ArchiveCourseBlockLegacy;

return apply_filters(
	'learn-press/config/block-elements',
	array(
		new SingleCourseBlockLegacy(),
		new ArchiveCourseBlockLegacy(),
	)
);
