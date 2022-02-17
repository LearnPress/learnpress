<?php

/**
 * Declare list LP widgets for elementor
 */
return apply_filters(
	'lp/elementor/widgets',
	[
		'become-a-teacher' => \Elementor\LP_Elementor_Widget_Become_A_Teacher::class,
		'login-form'       => \Elementor\LP_Elementor_Widget_Login_Form::class,
		'register-form'    => \Elementor\LP_Elementor_Widget_Register_Form::class,
		'list-courses'     => \Elementor\LP_Elementor_Widget_List_Courses::class,
	]
);
