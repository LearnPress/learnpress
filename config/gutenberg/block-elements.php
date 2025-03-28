<?php
/**
 * Declare block type elements
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */

use LearnPress\Gutenberg\Blocks\ArchiveCourse\ArchiveCourseBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\ButtonResetFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\ButtonSubmitFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\CourseAuthorFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\CourseCategoriesFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\CourseFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\CourseLevelFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\CoursePriceFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\CourseSearchFilterBlockType;
use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\CourseTagFilterBlockType;
use LearnPress\Gutenberg\Blocks\BreadCrumb\BreadCrumb;
use LearnPress\Gutenberg\Blocks\Courses\ListCoursesBlockType;
use LearnPress\Gutenberg\Blocks\Courses\CourseItemTemplateBlock;
use LearnPress\Gutenberg\Blocks\Legacy\SingleCourseBlockLegacy;
use LearnPress\Gutenberg\Blocks\Legacy\ArchiveCourseBlockLegacy;
use LearnPress\Gutenberg\Blocks\SingleCourse\SingleCourseBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseButtonBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseCategoriesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseCommentBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseCurriculumBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDateBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDescriptionBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDurationBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFaqsBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFeatureReviewBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFeaturesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseImageBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseInstructorBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseInstructorInfoBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseLessonBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseLevelBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CoursePriceBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseProgressBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseQuizBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseRequirementsBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseShareBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseStudentBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseTargetAudiencesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseTitleBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructor\SingleInstructorBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorAvatarBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorBackgroundBlockType;

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
		new CourseCurriculumBlockType(),
		new CourseInstructorInfoBlockType(),
		new CourseCommentBlockType(),
		new CourseImageBlockType(),
		new CoursePriceBlockType(),
		new CourseProgressBlockType(),
		new CourseStudentBlockType(),
		new CourseLessonBlockType(),
		new CourseDurationBlockType(),
		new CourseQuizBlockType(),
		new CourseLevelBlockType(),
		new CourseButtonBlockType(),
		new CourseShareBlockType(),
		new CourseFeatureReviewBlockType(),
		new CourseFilterBlockType(),
		new CourseSearchFilterBlockType(),
		new CourseAuthorFilterBlockType(),
		new CourseLevelFilterBlockType(),
		new CoursePriceFilterBlockType(),
		new CourseCategoriesFilterBlockType(),
		new CourseTagFilterBlockType(),
		new ButtonSubmitFilterBlockType(),
		new ButtonResetFilterBlockType(),
		new InstructorBackgroundBlockType(),
		new InstructorAvatarBlockType(),
		new BreadCrumb(),
		new SingleCourseBlockType(),
		new ArchiveCourseBlockType(),
		new SingleInstructorBlockType(),
		new ListCoursesBlockType(),
		new CourseItemTemplateBlock(),
	)
);
