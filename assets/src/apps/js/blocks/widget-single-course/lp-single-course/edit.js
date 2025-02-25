import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[
			'core/group',
			{ layout: { type: 'constrained' } },
			[
				[ 'learnpress/breadcrumb', {} ],
				[ 'learnpress/item-curriculum-course', {} ],
			],
		],
		[
			'core/group',
			{
				metadata: { name: 'Course Summary' },
				className: 'course-summary',
				layout: { type: 'constrained' },
			},
			[
				[
					'core/group',
					{
						metadata: { name: 'Content' },
						className: 'course-content course-summary-content',
						layout: { type: 'constrained' },
					},
					[
						[
							'core/group',
							{
								metadata: { name: 'Course Detail Info' },
								className: 'course-detail-info',
								layout: { type: 'constrained' },
							},
							[
								[
									'core/group',
									{
										className: 'lp-content-area',
										layout: { type: 'constrained' },
									},
									[
										[
											'core/group',
											{ layout: { type: 'constrained' } },
											[
												[
													'core/group',
													{
														className:
															'course-info-left',
														layout: {
															type: 'constrained',
														},
													},
													[
														[
															'core/group',
															{
																metadata: {
																	name: 'Meta Primary',
																},
																className:
																	'course-meta course-meta-primary',
																layout: {
																	type: 'constrained',
																},
															},
															[
																[
																	'core/group',
																	{
																		metadata:
																			{
																				name: 'Meta Left',
																			},
																		className:
																			'course-meta__pull-left',
																		layout: {
																			type: 'constrained',
																		},
																	},
																	[
																		[
																			'learnpress/instructor-single-course',
																			{},
																		],
																		[
																			'learnpress/categories-single-course',
																			{},
																		],
																	],
																],
															],
														],
														[
															'learnpress/title-single-course',
															{ tag: 'h1' },
														],
														[
															'core/group',
															{
																metadata: {
																	name: 'Course Meta Secondary',
																},
																className:
																	'course-meta course-meta-secondary',
																layout: {
																	type: 'constrained',
																},
															},
															[
																[
																	'core/group',
																	{
																		className:
																			'course-meta__pull-left',
																		layout: {
																			type: 'constrained',
																		},
																	},
																	[
																		[
																			'learnpress/duration-single-course',
																			{},
																		],
																		[
																			'learnpress/level-single-course',
																			{},
																		],
																		[
																			'learnpress/lesson-single-course',
																			{},
																		],
																		[
																			'learnpress/quiz-single-course',
																			{},
																		],
																		[
																			'learnpress/student-single-course',
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
									],
								],
							],
						],
						[
							'core/group',
							{
								metadata: { name: 'Content Area' },
								className: 'lp-entry-content lp-content-area',
								layout: { type: 'constrained' },
							},
							[
								[
									'core/group',
									{
										metadata: { name: 'Content Left' },
										className: 'entry-content-left',
										layout: { type: 'constrained' },
									},
									[
										[ 'learnpress/tabs-single-course', {} ],
										[
											'learnpress/requirements-single-course',
											{},
										],
										[
											'learnpress/features-single-course',
											{},
										],
										[
											'learnpress/target-audiences-single-course',
											{},
										],
										[ 'learnpress/comment', {} ],
									],
								],
								[
									'core/group',
									{
										tagName: 'aside',
										metadata: {
											name: 'Course Summary SideBar',
										},
										className:
											'course-summary-sidebar slide-top',
										layout: { type: 'constrained' },
									},
									[
										[
											'core/group',
											{
												className:
													'course-summary-sidebar__inner',
												layout: { type: 'constrained' },
											},
											[
												[
													'core/group',
													{
														className:
															'course-sidebar-top',
														layout: {
															type: 'constrained',
														},
													},
													[
														[
															'core/group',
															{
																className:
																	'course-sidebar-preview',
																layout: {
																	type: 'constrained',
																},
															},
															[
																[
																	'learnpress/image-single-course',
																	{},
																],
																[
																	'learnpress/price-single-course',
																	{},
																],
																[
																	'learnpress/btn-purchase-single-course',
																	{},
																],
																[
																	'learnpress/time-single-course',
																	{},
																],
																[
																	'learnpress/progress-single-course',
																	{},
																],
																[
																	'learnpress/feature-review-single-course',
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
