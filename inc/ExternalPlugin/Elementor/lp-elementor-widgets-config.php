<?php

/**
 * Declare list LP widgets for elementor
 */

use LearnPress\ExternalPlugin\Elementor\Widgets\BecomeATeacherElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseAddressDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseAuthorNameElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseAuthorAvatarElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseAuthorUrlElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseCapacityDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseCountLessonDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseCountQuizDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseCountStudentDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseDeliverTypeDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseDurationDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseLevelDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\ListCoursesByPageElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Sections\CoursePriceElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\FilterCourseElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesGrid;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesList;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesLoopItem;
use LearnPress\ExternalPlugin\Elementor\Widgets\CourseListElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections\InstructorButtonViewElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections\InstructorDescriptionElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections\InstructorTitleElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections\InstructorAvatarElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections\InstructorCountStudentsElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\Sections\InstructorCountCoursesElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\ListInstructorsElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\LoginUserFormElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\RegisterUserFormElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CountCoursesFreeDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CountStudentDynamicElementor;

return [
	'widgets'  => apply_filters(
		'lp/elementor/widgets',
		[
			//'single-instructor'      => SingleInstructorElementor::class,
			'list-instructors'          => ListInstructorsElementor::class,
			'instructor-title'          => InstructorTitleElementor::class,
			'instructor-description'    => InstructorDescriptionElementor::class,
			'instructor-button-view'    => InstructorButtonViewElementor::class,
			'instructor-avatar'         => InstructorAvatarElementor::class,
			'instructor-count-students' => InstructorCountStudentsElementor::class,
			'instructor-count-courses'  => InstructorCountCoursesElementor::class,
			'become-a-teacher'          => BecomeATeacherElementor::class,
			'login-form'                => LoginUserFormElementor::class,
			'register-form'             => RegisterUserFormElementor::class,
			'list-courses'              => CourseListElementor::class,
			//'list-courses-by-page'      => ListCoursesByPageElementor::class,
			// Single Course
			'course-price'              => CoursePriceElementor::class,
			//'filter-course'				=> FilterCourseElementor::class
		]
	),
	'dynamic'  => apply_filters(
		'lp/elementor/dynamic',
		[
			'course-count-student'  => CourseCountStudentDynamicElementor::class,
			'course-count-lesson'   => CourseCountLessonDynamicElementor::class,
			'course-count-quiz'     => CourseCountQuizDynamicElementor::class,
			'course-count-level'    => CourseLevelDynamicElementor::class,
			'course-count-duration' => CourseDurationDynamicElementor::class,
			'course-author-name'    => CourseAuthorNameElementor::class,
			'course-author-avatar'  => CourseAuthorAvatarElementor::class,
			'course-author-url'     => CourseAuthorUrlElementor::class,
			'count-student-courses' => CountStudentDynamicElementor::class,
			'course-courses-free'   => CountCoursesFreeDynamicElementor::class,
			'course-deliver-type'   => CourseDeliverTypeDynamicElementor::class,
			'course-capacity'       => CourseCapacityDynamicElementor::class,
			'course-address'       => CourseAddressDynamicElementor::class,
		]
	),
	'loadAjax' => apply_filters(
		'lp/elementor/loadAjax',
		[
			CoursesGrid::class . ':render_courses',
			CoursesList::class . ':render_courses',
			CoursesLoopItem::class . ':render_courses',
		]
	),
];
