<?php
/**
 * Declare block type elements
 *
 * @since 4.2.8.2
 * @version 1.0.0
 */

use LearnPress\Gutenberg\Blocks\CourseFilter\CourseFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\ButtonResetFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\ButtonSubmitFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\CourseAuthorFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\CourseCategoriesFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\CourseLevelFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\CoursePriceFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\CourseSearchFilterBlockType;
use LearnPress\Gutenberg\Blocks\CourseFilterElements\CourseTagFilterBlockType;
use LearnPress\Gutenberg\Blocks\Breadcrumb\BreadcrumbBlockType;
use LearnPress\Gutenberg\Blocks\Courses\ListCoursesBlockType;
use LearnPress\Gutenberg\Blocks\Courses\CourseItemTemplateBlock;
use LearnPress\Gutenberg\Blocks\Courses\CourseOrderByBlockType;
use LearnPress\Gutenberg\Blocks\Courses\CourseResultsBlockType;
use LearnPress\Gutenberg\Blocks\Courses\CourseSearchBlockType;
use LearnPress\Gutenberg\Blocks\Legacy\SingleCourseBlockLegacy;
use LearnPress\Gutenberg\Blocks\Legacy\ArchiveCourseBlockLegacy;
use LearnPress\Gutenberg\Blocks\Legacy\CourseItemLegacyBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourse\SingleCourseBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseAddressBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseButtonBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseButtonReadMoreBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseCapacityBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseCategoriesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseCurriculumBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDeliveryBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDescriptionBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseDurationBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFaqsBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFeaturedBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFeatureReviewBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseFeaturesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseImageBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseInstructorBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseInstructorInfoBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseLessonBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseLevelBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseOfflineLessonBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseMaterialBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CoursePriceBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseProgressBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseQuizBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseRequirementsBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseShareBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseStudentBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseTargetAudiencesBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseElements\CourseTitleBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemCloseBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemCommentBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemContentBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemCurriculumBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemHiddenSidebarBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemNavigationBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemProgressBlockType;
use LearnPress\Gutenberg\Blocks\SingleCourseItemElements\ItemSearchBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructor\SingleInstructorBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorAvatarBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorBackgroundBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorCourseBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorDescriptionBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorNameBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorSocialBlockType;
use LearnPress\Gutenberg\Blocks\SingleInstructorElements\InstructorStudentBlockType;

return apply_filters(
	'learn-press/config/block-elements',
	array(
		new SingleCourseBlockLegacy(),
		new ArchiveCourseBlockLegacy(),
		new CourseAddressBlockType(),
		new CourseTitleBlockType(),
		new CourseFeaturedBlockType(),
		new CourseInstructorBlockType(),
		new CourseCapacityBlockType(),
		new CourseCategoriesBlockType(),
		new CourseDeliveryBlockType(),
		new CourseDescriptionBlockType(),
		new CourseFeaturesBlockType(),
		new CourseTargetAudiencesBlockType(),
		new CourseRequirementsBlockType(),
		new CourseFaqsBlockType(),
		new CourseCurriculumBlockType(),
		new CourseItemLegacyBlockType(),
		new CourseInstructorInfoBlockType(),
		new CourseImageBlockType(),
		new CoursePriceBlockType(),
		new CourseMaterialBlockType(),
		new CourseProgressBlockType(),
		new CourseStudentBlockType(),
		new CourseLessonBlockType(),
		new CourseOfflineLessonBlockType(),
		new CourseDurationBlockType(),
		new CourseQuizBlockType(),
		new CourseLevelBlockType(),
		new CourseButtonBlockType(),
		new CourseButtonReadMoreBlockType(),
		new CourseShareBlockType(),
		new CourseFeatureReviewBlockType(),
		new CourseSearchBlockType(),
		new CourseOrderByBlockType(),
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
		new InstructorNameBlockType(),
		new InstructorSocialBlockType(),
		new InstructorCourseBlockType(),
		new InstructorStudentBlockType(),
		new InstructorDescriptionBlockType(),
		new BreadcrumbBlockType(),
		//new SingleCourseBlockType(),
		// new SingleInstructorBlockType(),
		new ListCoursesBlockType(),
		new CourseItemTemplateBlock(),
		new CourseResultsBlockType(),
		new ItemCloseBlockType(),
		new ItemCommentBlockType(),
		new ItemContentBlockType(),
		new ItemCurriculumBlockType(),
		new ItemHiddenSidebarBlockType(),
		new ItemNavigationBlockType(),
		new ItemProgressBlockType(),
		new ItemSearchBlockType(),
	)
);
