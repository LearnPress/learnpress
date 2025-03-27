import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[
			'core/group',
			{
				layout: { type: 'constrained' },
				className: 'lp-archive-courses',
			},
			[
				[ 'learnpress/breadcrumb', {} ],
				[
					'core/group',
					{
						metadata: { name: 'Content Area' },
						className: 'lp-content-area has-sidebar',
						layout: {
							type: 'flex',
							orientation: 'horizontal',
							flexWrap: 'nowrap',
							verticalAlignment: 'top',
							justifyContent: 'left',
						},
					},
					[
						[
							'core/group',
							{
								metadata: { name: 'Archive Courses' },
								className: 'lp-archive-courses',
								layout: { type: 'default' },
							},
							[
								[
									'core/group',
									{
										tagName: 'header',
										metadata: { name: 'Course Header' },
										className: 'learn-press-courses-header',
										layout: { type: 'default' },
									},
									[
										[
											'core/heading',
											{
												level: 1,
												className: 'wp-block-heading',
												content: 'All Courses',
											},
										],
									],
								],
								[
									'learnpress/list-course-archive-course',
									{ ajax: true, load: true },
									[
										[
											'core/group',
											{
												metadata: {
													name: 'Course Bar',
												},
												className: 'lp-courses-bar',
												layout: {
													type: 'flex',
													flexWrap: 'nowrap',
												},
											},
											[
												[
													'learnpress/search-archive-course',
													{},
												],
												[
													'learnpress/order-by-archive-course',
													{},
												],
											],
										],
										[
											'learnpress/template-course-archive-course',
											{ ajax: false },
											[
												[
													'learnpress/media-course-archive-course',
													{},
												],
												[
													'learnpress/title-course-archive-course',
													{},
												],
												[
													'core/group',
													{
														metadata: {
															name: 'Category Instructor',
														},
														className:
															'course-instructor-category',
														layout: {
															type: 'constrained',
														},
													},
													[
														[
															'learnpress/instructor-course-archive-course',
															{},
														],
														[
															'learnpress/category-course-archive-course',
															{},
														],
													],
												],
												[
													'core/group',
													{
														metadata: {
															name: 'Meta Course',
														},
														className:
															'course-wrap-meta',
														layout: {
															type: 'constrained',
														},
													},
													[
														[
															'learnpress/level-course-archive-course',
															{},
														],
														[
															'learnpress/duration-course-archive-course',
															{},
														],
														[
															'learnpress/lesson-course-archive-course',
															{},
														],
														[
															'learnpress/quiz-course-archive-course',
															{},
														],
														[
															'learnpress/student-course-archive-course',
															{},
														],
													],
												],
												[
													'learnpress/description-course-archive-course',
													{},
												],
												[
													'core/group',
													{
														metadata: {
															name: 'Course Info',
														},
														className:
															'course-info',
														layout: {
															type: 'constrained',
														},
													},
													[
														[
															'learnpress/price-course-archive-course',
															{},
														],
														[
															'learnpress/button-course-archive-course',
															{},
														],
													],
												],
											],
										],
									],
								],
							],
						],
						[
							'learnpress/course-filter',
							{ numberLevelCategory: 0, showInRest: false },
							[
								[ 'learnpress/search-filter-archive-course', {} ],
								[ 'learnpress/author-filter-archive-course', {} ],
								[ 'learnpress/level-filter-archive-course', {} ],
								[ 'learnpress/price-filter-archive-course', {} ],
								[
									'learnpress/category-filter-archive-course',
									{},
								],
								[ 'learnpress/tag-filter-archive-course', {} ],
							],
						],
					],
				],
			],
		],
	];
	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
		</>
	);
};
