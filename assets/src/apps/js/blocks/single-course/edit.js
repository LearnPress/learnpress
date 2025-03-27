import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[
			'core/group',
			{
				metadata: { name: 'Header' },
				className: 'lp-single-course__header',
				layout: { type: 'default' },
			},
			[
				[
					'core/group',
					{
						metadata: { name: 'Inner' },
						className: 'lp-single-course__header__inner',
						layout: { type: 'default' },
					},
					[
						[ 'learnpress/breadcrumb', {} ],
						[ 'learnpress/course-title', { tag: 'h1' } ],
						[
							'core/group',
							{
								metadata: { name: 'Meta Left' },
								className: 'course-instructor-category',
								layout: { type: 'flex', flexWrap: 'nowrap' },
							},
							[
								[ 'learnpress/course-instructor', {} ],
								[ 'learnpress/course-categories', {} ],
							],
						],
						[ 'learnpress/course-date', {} ],
					],
				],
			],
		],
		[
			'core/group',
			{
				metadata: { name: 'Area' },
				className: 'lp-content-area',
				layout: { type: 'default' },
			},
			[
				[
					'core/group',
					{
						metadata: { name: 'Main' },
						className: 'lp-single-course-main',
						layout: { type: 'default' },
					},
					[
						[
							'core/group',
							{
								metadata: { name: 'Left' },
								className: 'lp-single-course-main__left',
								layout: { type: 'default' },
							},
							[
								[ 'learnpress/course-description', {} ],
								[ 'learnpress/course-features', {} ],
								[
									'learnpress/course-target-audiences',
									{},
								],
								[ 'learnpress/course-requirements', {} ],
								[ 'learnpress/course-faqs', {} ],
								[ 'learnpress/course-curriculum', {} ],
								[
									'learnpress/course-instructor-info',
									{},
								],
								[ 'learnpress/course-comment', {} ],
							],
						],
						[
							'core/group',
							{
								metadata: { name: 'Right' },
								className: 'lp-single-course-main__right',
								layout: { type: 'default' },
							},
							[
								[
									'core/group',
									{
										className:
											'lp-single-course-main__right__inner',
										layout: { type: 'default' },
									},
									[
										[ 'learnpress/course-image', {} ],
										[ 'learnpress/course-price', {} ],
										[
											'learnpress/course-progress',
											{},
										],
										[
											'core/group',
											{
												metadata: { name: 'Meta' },
												className: 'info-metas',
												layout: { type: 'default' },
											},
											[
												[
													'learnpress/course-student',
													{},
												],
												[
													'learnpress/course-lesson',
													{},
												],
												[
													'learnpress/course-duration',
													{},
												],
												[
													'learnpress/course-quiz',
													{},
												],
												[
													'learnpress/course-level',
													{},
												],
											],
										],
										[
											'learnpress/course-button',
											{},
										],
										[ 'learnpress/course-share', {} ],
										[
											'learnpress/course-feature-review',
											{},
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
						metadata: { name: 'Related' },
						className: 'lp-list-courses-related',
						layout: { type: 'default' },
					},
					[ [ 'learnpress/course-related', {} ] ],
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
