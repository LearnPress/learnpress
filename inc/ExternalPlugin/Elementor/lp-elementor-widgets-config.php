<?php

/**
 * Declare list LP widgets for elementor
 */

use LearnPress\ExternalPlugin\Elementor\Widgets\BecomeATeacherElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\ListCoursesByPageElementor;
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
use LearnPress\ExternalPlugin\Elementor\Widgets\Instructor\SingleInstructorElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\CourseMaterialElementor;
return apply_filters(
	'lp/elementor/widgets',
	[
		//'single-instructor'      => SingleInstructorElementor::class,
		'list-instructors'       	=> ListInstructorsElementor::class,
		'instructor-title'       	=> InstructorTitleElementor::class,
		'instructor-description' 	=> InstructorDescriptionElementor::class,
		'instructor-button-view' 	=> InstructorButtonViewElementor::class,
		'instructor-avatar'		 	=> InstructorAvatarElementor::class,
		'instructor-count-students'	=> InstructorCountStudentsElementor::class,
		'instructor-count-courses'	=> InstructorCountCoursesElementor::class,
		'become-a-teacher'       	=> BecomeATeacherElementor::class,
		'login-form'             	=> LoginUserFormElementor::class,
		'register-form'          	=> RegisterUserFormElementor::class,
		'list-courses'           	=> CourseListElementor::class,
		'list-courses-by-page'   	=> ListCoursesByPageElementor::class,
		// 'course-material'        => CourseMaterialElementor::class,
	]
);
