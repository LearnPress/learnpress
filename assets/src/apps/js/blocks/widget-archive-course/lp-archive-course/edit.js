import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[
			'core/group',
			{
				className: 'lp-archive-courses',
				layout: {
					type: 'default',
				},
			},
			[
				[ 'learnpress/breadcrumb' ],

				[
					'core/group',
					{
						className: 'lp-content-area',
						layout: {
							type: 'default',
						},
					},
					[
						[
							'core/group',
							{
								tagName: 'header',
								className: 'learn-press-courses-header',
								layout: {
									type: 'default',
								},
							},
							[
								[
									'core/heading',
									{
										level: 1,
										content: 'All Courses',
										className: 'wp-block-heading',
									},
								],
							],
						],

						[
							'learnpress/list-course-archive-course',
							{
								custom: true,
							},
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
										[ 'learnpress/search-archive-course' ],
										[ 'learnpress/order-by-archive-course' ],
										[ 'learnpress/switch-layout-archive-course' ],
									],
								],
								[ 'learnpress/template-course-archive-course' ],
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
				<InnerBlocks template={ TEMPLATE } />
			</div>
		</>
	);
};
