<?php
$currencies = learn_press_currencies();

foreach ( $currencies as $code => $name ) {
	$currency_symbol     = learn_press_get_currency_symbol( $code );
	$currencies[ $code ] = sprintf( '%s (%s)', $name, $currency_symbol );
}

return apply_filters(
	'learn-press/course-settings-fields/single',
	[
		[
			'title' => esc_html__( 'Permalinks', 'learnpress' ),
			'type'  => 'title',
		],
		[
			'title'   => esc_html__( 'Course', 'learnpress' ),
			'type'    => 'course-permalink',
			'default' => '',
			'id'      => 'course_base',
		],
		[
			'title'       => esc_html__( 'Lesson', 'learnpress' ),
			'type'        => 'text',
			'id'          => 'lesson_slug',
			'desc'        => sprintf( 'e.g. %s/course/sample-course/<code>lessons</code>/sample-lesson/', home_url() ),
			'default'     => 'lessons',
			'placeholder' => 'lesson',
		],
		[
			'title'       => esc_html__( 'Quiz', 'learnpress' ),
			'type'        => 'text',
			'id'          => 'quiz_slug',
			'desc'        => sprintf( 'e.g. %s/course/sample-course/<code>quizzes</code>/sample-quiz/', home_url() ),
			'default'     => 'quizzes',
			'placeholder' => 'quizzes',
		],
		[
			'title'       => esc_html__( 'Category base', 'learnpress' ),
			'id'          => 'course_category_base',
			'default'     => 'course-category',
			'type'        => 'text',
			'placeholder' => 'course-category',
			'desc'        => sprintf( 'e.g. %s/course/%s/sample-category/', home_url(), '<code>course-category</code>' ),
		],
		[
			'title'       => esc_html__( 'Tag base', 'learnpress' ),
			'id'          => 'course_tag_base',
			'default'     => 'course-tag',
			'type'        => 'text',
			'placeholder' => 'course-tag',
			'desc'        => sprintf( 'e.g. %s/course/%s/sample-tag/', home_url(), '<code>course-tag</code>' ),
		],
		[
			'type' => 'sectionend',
		],
	]
);
