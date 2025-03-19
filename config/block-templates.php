<?php
/**
 * Declare block type template
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */
use LearnPress\Gutenberg\Templates\SingleCourseBlockTemplate;

return apply_filters(
	'learn-press/config/block-templates',
	array(
		new SingleCourseBlockTemplate(),
	)
);
