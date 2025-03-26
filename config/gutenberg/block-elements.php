<?php
/**
 * Declare block type elements
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */

use LearnPress\Gutenberg\Blocks\BreadCrumb\BreadCrumb;
use LearnPress\Gutenberg\Blocks\Courses\ListCoursesBlockType;
use LearnPress\Gutenberg\Blocks\Legacy\SingleCourseBlockLegacy;
use LearnPress\Gutenberg\Blocks\Legacy\ArchiveCourseBlockLegacy;
use LearnPress\Gutenberg\Blocks\SingleCourse\SingleCourseBlock;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseCategoriesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDateBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDescriptionBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFaqsBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFeaturesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseInstructorBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseRequirementsBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseTargetAudiencesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseTitleBlockType;

return apply_filters(
	'learn-press/config/block-elements',
	array(
		new SingleCourseBlockLegacy(),
		new ArchiveCourseBlockLegacy(),
		new CourseTitleBlockType(),
		new CourseInstructorBlockType(),
		new CourseCategoriesBlockType(),
		new CourseDateBlockType(),
		new CourseDescriptionBlockType(),
		new CourseFeaturesBlockType(),
		new CourseTargetAudiencesBlockType(),
		new CourseRequirementsBlockType(),
		new CourseFaqsBlockType(),
		new BreadCrumb(),
		new SingleCourseBlock(),
		new ListCoursesBlockType(),
	)
);
