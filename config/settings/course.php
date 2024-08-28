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
					'title'             => esc_html__( 'Include courses in subcategory', 'learnpress' ),
					'desc'              => esc_html__( 'Show all courses within the subcategory that have not been chosen in the parent category.', 'learnpress' ),
					'id'                => 'get_courses_of_subcategory',
					'default'           => 'no',
					'type'              => 'checkbox',
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
					'title'           => esc_html__( 'Pagination Type', 'learnpress' ),
					'desc'            => esc_html__( 'Display the pagination type on the Course Archive page.', 'learnpress' ),
					'id'              => 'course_pagination_type',
					'default'         => 'standard',
					'type'            => 'select',
					'options'         => array(
						'number'    => esc_html__( 'Number', 'learnpress' ),
						'load-more' => esc_html__( 'Load More', 'learnpress' ),
						'infinite'  => esc_html__( 'Infinite Scroll', 'learnpress' ),
					),
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
					'default' => -1,
					'type'    => 'number',
					'min'     => '-1',
					'desc'    => esc_html__( 'The number of displayed sections per page (Enter -1 to display all sections).', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Course Item Per Page', 'learnpress' ),
					'id'      => 'course_item_per_page',
					'default' => -1,
					'type'    => 'number',
					'min'     => '-1',
					'desc'    => esc_html__( 'The number of displayed course items per page in a section (Enter -1 to display all course items).', 'learnpress' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'lp_metabox_curriculum_setting',
				),
			)
		),
		apply_filters(
			'learn-press/course-settings-fields/material',
			array(
				array(
					'type'  => 'title',
					'title' => esc_html__( 'Material Settings', 'learnpress' ),
					'id'    => 'lp_course_material_setting',
				),
				array(
					'title'             => esc_html__( 'File Type', 'learnpress' ),
					'id'                => 'material_allow_file_type',
					'type'              => 'multiselect',
					'is_optgroup'       => '1',
					'class'             => 'lp-tom-select',
					'default'           => array( 'pdf', 'txt' ),
					'desc'              => esc_html__( 'Which types of file will be allowed uploading?', 'learnpress' ),
					'options'           => array(
						'document'           => array(
							'txt'      => '.txt(text/plain)',
							'doc,docx' => '.doc, .docx (Microsoft Word)',
							'pdf'      => '.pdf (Portable Document Format)',
						),
						'image'              => array(
							'jpg,jpeg' => '.jpg, .jpeg (Joint Photographic Experts Group)',
							'png'      => '.png (Portable Network Graphics)',
							'gif'      => '.gif (Graphics Interchange Format)',
							'bmp'      => '.bmp (Bitmap)',
						),
						'audio'              => array(
							'mp3' => '.mp3 (MPEG Audio Layer 3)',
							'wav' => '.wav (Waveform Audio File Format)',
							'wma' => '.wma (Windows Media Audio)',
						),
						'video'              => array(
							'mp4' => '.mp4 (MPEG-4 Part 14)',
							'avi' => '.avi (Audio Video Interleave)',
							'mov' => '.mov (QuickTime Movie)',
							'wmv' => '.wmv (Windows Media Video)',
						),
						'spreadsheet'        => array(
							'xls,xlsx' => '.xls, .xlsx (Microsoft Excel)',
							'ods'      => '.ods (OpenDocument Spreadsheet)',
							'csv'      => '.csv (Comma-Separated Values)',
							'numbers'  => '.numbers (Apple Numbers)',
							'tsv'      => '.tsv (Tab-Separated Values)',
						),
						'compressed archive' => array(
							'zip' => 'ZIP archive',
						),
					),
					'custom_attributes' => array(
						'required' => '1',
					),
				),
				array(
					'title'             => esc_html__( 'Upload Files', 'learnpress' ),
					'id'                => 'material_upload_files',
					'default'           => 2,
					'type'              => 'number',
					'desc'              => esc_html__( 'Number files the user can upload. Set to 0 to disable', 'learnpress' ),
					'custom_attributes' => array(
						'min' => '0',
					),
				),
				array(
					'title'             => esc_html__( 'File Size Limit', 'learnpress' ),
					'id'                => 'material_max_file_size',
					'default'           => 2,
					'type'              => 'number',
					'desc'              => esc_html__( 'Set Maximum Attachment size for upload (MB)', 'learnpress' ),
					'custom_attributes' => array(
						'min' => '1',
					),
				),
				array(
					'title'             => esc_html__( 'File Per Page', 'learnpress' ),
					'id'                => 'material_file_per_page',
					'default'           => -1,
					'type'              => 'number',
					'desc'              => esc_html__( 'The number of displayed files per page (Enter -1 to display all files, set to 0 to disable).', 'learnpress' ),
					'custom_attributes' => array(
						'min' => '-1',
					),
				),
				array(
					'title'   => esc_html__( 'Url nofollow', 'learnpress' ),
					'desc'    => esc_html__( 'Add rel="nofollow" to material external link.', 'learnpress' ),
					'id'      => 'material_url_nofollow',
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'lp_course_material_setting',
				),
			)
		),
		apply_filters(
			'learn-press/course-settings-fields/instructor',
			array(
				array(
					'type'  => 'title',
					'title' => esc_html__( 'Instructor Settings', 'learnpress' ),
					'id'    => 'lp_metabox_instructor_setting',
				),
				array(
					'title'   => esc_html__( 'Instructor Per Page', 'learnpress' ),
					'id'      => 'instructor_per_page',
					'default' => 12,
					'type'    => 'number',
					'desc'    => esc_html__( 'The number of displayed instructors per page (Enter -1 to display all sections).', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Show Admin on list', 'learnpress' ),
					'id'      => 'show_admin_on_list_instructors',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Show author Admin on list instructors.', 'learnpress' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'lp_metabox_instructor_setting',
				),
			)
		)
	)
);
