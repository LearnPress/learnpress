<?php

/**
 * Declare list LP widgets for elementor
 */

use LearnPress\ExternalPlugin\Elementor\Widgets\BecomeATeacherElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\SingleInstructorElementor;

return apply_filters(
	'lp/elementor/widgets',
	[
		'become-a-teacher'  => BecomeATeacherElementor::class,
		'login-form'        => \Elementor\LP_Elementor_Widget_Login_Form::class,
		'register-form'     => \Elementor\LP_Elementor_Widget_Register_Form::class,
		'list-courses'      => \Elementor\LP_Elementor_Widget_List_Courses::class,
		'single-instructor' => SingleInstructorElementor::class,
	]
);
