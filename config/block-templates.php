<?php
use LearnPress\Gutenberg\Blocks\Courses\BlockArchiveCourseLegacy;
use LearnPress\Gutenberg\Blocks\SingleCourse\BlockSingleCourseLegacy;

return apply_filters(
	'learn-press/config/block-templates',
	array(
		new BlockArchiveCourseLegacy(),
		new BlockSingleCourseLegacy(),
	)
);
