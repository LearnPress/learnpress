import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[
			'core/group',
			{ className: 'lp-archive-courses', layout: { type: 'default' } },
			[
				[ 'learnpress/breadcrumb', {} ],
				[
					'core/group',
					{
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
								className: 'lp-content-area',
								layout: { type: 'default' },
							},
							[
								[
									'core/group',
									{
										tagName: 'header',
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
									{},
									[
										[
											'core/group',
											{
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
												[
													'learnpress/switch-layout-archive-course',
													{},
												],
											],
										],
										[
											'learnpress/template-course-archive-course',
											{},
											[
												[
													'learnpress/media-course-archive-course',
													{
														content:
															'{{media-course}}',
													},
												],
												[
													'learnpress/title-course-archive-course',
													{
														content:
															'{{title-course}}',
													},
												],
												[
													'learnpress/instructor-category-course-archive-course',
													{
														content:
															'{{instructor-category-course}}',
													},
												],
												[
													'learnpress/meta-course-archive-course',
													{
														content:
															'{{meta-course}}',
													},
												],
												[
													'learnpress/info-course-archive-course',
													{
														content:
															'{{info-course}}',
													},
												],
											],
										],
									],
								],
							],
						],
						[ 'learnpress/sidebar-archive-course', {} ],
					],
				],
			],
		],
	];
	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks template={ TEMPLATE } />
			</div>
		</>
	);
};
