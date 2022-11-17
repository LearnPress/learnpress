<?php
/**
 * Settings Courses
 */
$generate_course_thumbnail         = get_option( 'learn_press_generate_course_thumbnail', 'no' );
$des_option_redirect_finish_course = wp_sprintf(
	'%s<br>%s',
	esc_html__( 'The site will be redirected to the URL added after clicking the finish course button.', 'learnpress' ),
	esc_html__( 'Set blank, the site will be redirected to the single course page', 'learnpress' )
);

return apply_filters(
	'learn-press/courses-settings-fields',
	array_merge(
		apply_filters(
			'learn-press/course-settings-fields/general',
			array(
				array(
					'title' => esc_html__( 'General', 'learnpress' ),
					'type'  => 'title',
				),
				array(
					'title'   => esc_html__( 'Review courses', 'learnpress' ),
					'desc'    => esc_html__( 'Courses created by instructors will be pending review first.', 'learnpress' ),
					'id'      => 'required_review',
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'title'   => esc_html__( 'Auto start', 'learnpress' ),
					'id'      => 'auto_enroll',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Students will get started on courses immediately after successfully purchasing them.', 'learnpress' ),
				),
				array(
					'title'   => __( 'Confirmation popup', 'learnpress' ),
					'desc'    => __( 'Show a confirmation popup before finishing the course or completing the item.', 'learnpress' ),
					'id'      => 'enable_popup_confirm_finish',
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'title'   => esc_html__( 'Archive page layout', 'learnpress' ),
					'desc'    => esc_html__( 'Display the List course type on the Course Archive page.', 'learnpress' ),
					'id'      => 'archive_courses_layout',
					'default' => 'list',
					'type'    => 'select',
					'options' => learn_press_courses_layouts(),
				),
				array(
					'title'             => esc_html__( 'Courses per page', 'learnpress' ),
					'desc'              => esc_html__( 'The number of displayed courses per page.', 'learnpress' ),
					'id'                => 'archive_course_limit',
					'default'           => '8',
					'type'              => 'number',
					'custom_attributes' => array(
						'min' => '1',
					),
					'css'               => 'min-width: 50px; width: 50px;',
				),
				array(
					'title'   => esc_html__( 'Loading ajax Courses', 'learnpress' ),
					'desc'    => __( 'On/Off <i>loading ajax courses on the Course Archive page </i>.', 'learnpress' ),
					'id'      => 'courses_load_ajax',
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'title'           => esc_html__( 'Do not run Ajax when reloading the Course Archive page', 'learnpress' ),
					'desc'            => esc_html__( 'Ajax is only applied when selecting pagination, filtering, searching, and sorting. Not applicable when reloading the Course Archive page.', 'learnpress' ),
					'id'              => 'courses_first_no_ajax',
					'default'         => 'no',
					'type'            => 'checkbox',
					'show_if_checked' => 'courses_load_ajax',
				),
				array(
					'title'   => esc_html__( 'Thumbnail dimensions', 'learnpress' ),
					'id'      => 'course_thumbnail_dimensions',
					'default' => array( 500, 300, 'yes' ),
					'type'    => 'image-dimensions',
				),
				array(
					'title'       => esc_html__( 'Redirect when finishing the course', 'learnpress' ),
					'id'          => 'course_finish_redirect',
					'default'     => '',
					'type'        => 'url',
					'placeholder' => 'https://learnpress.com/',
					'desc'        => $des_option_redirect_finish_course,
				),
				array(
					'type' => 'sectionend',
				),
			)
		),
		apply_filters(
			'learn-press/course-settings-fields/curriculum',
			array(
				array(
					'type'  => 'title',
					'title' => esc_html__( 'Curriculum Settings', 'learnpress' ),
					'id'    => 'lp_metabox_curriculum_setting',
				),
				array(
					'title'   => esc_html__( 'Section Per Page', 'learnpress' ),
					'id'      => 'section_per_page',
					'default' => 1,
					'type'    => 'number',
					'desc'    => esc_html__( 'The number of displayed sections per page (Enter -1 to display all sections).', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Course Item Per Page', 'learnpress' ),
					'id'      => 'course_item_per_page',
					'default' => 10,
					'type'    => 'number',
					'desc'    => esc_html__( 'The number of displayed course items per page in a section (Enter -1 to display all course items).', 'learnpress' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'lp_metabox_curriculum_setting',
				),
			)
		)
	)
);
