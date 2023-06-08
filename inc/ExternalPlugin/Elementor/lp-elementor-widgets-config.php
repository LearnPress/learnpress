<?php

/**
 * Declare list LP widgets for elementor
 */

use LearnPress\ExternalPlugin\Elementor\Widgets\BecomeATeacherElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\CourseListElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\LoginUserFormElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\RegisterUserFormElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\SingleInstructorElementor;

return apply_filters(
	'lp/elementor/widgets',
	[
		'become-a-teacher'  => BecomeATeacherElementor::class,
		'login-form'        => LoginUserFormElementor::class,
		'register-form'     => RegisterUserFormElementor::class,
		'list-courses'      => CourseListElementor::class,
		'single-instructor' => SingleInstructorElementor::class,
	]
);
