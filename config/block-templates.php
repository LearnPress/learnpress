<?php
use LearnPress\Gutenberg\Blocks\Courses\BlockArchiveCourseLegacy;
use LearnPress\Gutenberg\Blocks\SingleCourse\BlockSingleCourseItem;
use LearnPress\Gutenberg\Blocks\SingleCourse\BlockSingleCourseLegacy;
use LearnPress\Gutenberg\Blocks\SingleCourse\BlockCourseTitle;
use LearnPress\Gutenberg\Blocks\SingleCourse\BlockSingleCourseTemplate;

return apply_filters(
	'learn-press/config/block-templates',
	array(
		//new BlockArchiveCourseLegacy(),
		new BlockCourseTitle(),
		//new BlockSingleCourseLegacy(),
		new BlockSingleCourseTemplate(),
		new BlockSingleCourseItem(),
	)
);
