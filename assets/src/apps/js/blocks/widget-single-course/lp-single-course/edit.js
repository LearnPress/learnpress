import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

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
						[ 'learnpress/title-single-course', { tag: 'h1' } ],
						[
							'core/group',
							{
								metadata: { name: 'Meta Left' },
								className: 'course-instructor-category',
								layout: { type: 'flex', flexWrap: 'nowrap' },
							},
							[
								[ 'learnpress/instructor-single-course', {} ],
								[ 'learnpress/categories-single-course', {} ],
							],
						],
						[ 'learnpress/info-one-course', {} ],
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
								[ 'learnpress/description-single-course', {} ],
								[ 'learnpress/features-single-course', {} ],
								[
									'learnpress/target-audiences-single-course',
									{},
								],
								[ 'learnpress/requirements-single-course', {} ],
								[ 'learnpress/faqs-single-course', {} ],
								[ 'learnpress/curriculum-single-course', {} ],
								[
									'learnpress/instructor-section-single-course',
									{},
								],
								[ 'learnpress/comment', {} ],
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
										[ 'learnpress/image-single-course', {} ],
										[ 'learnpress/price-single-course', {} ],
										[
											'learnpress/progress-single-course',
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
													'learnpress/student-single-course',
													{},
												],
												[
													'learnpress/lesson-single-course',
													{},
												],
												[
													'learnpress/duration-single-course',
													{},
												],
												[
													'learnpress/quiz-single-course',
													{},
												],
												[
													'learnpress/level-single-course',
													{},
												],
											],
										],
										[
											'learnpress/btn-purchase-single-course',
											{},
										],
										[ 'learnpress/share-course', {} ],
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
				[
					'core/group',
					{
						metadata: { name: 'Related' },
						className: 'lp-list-courses-related',
						layout: { type: 'default' },
					},
					[ [ 'learnpress/related-course', {} ] ],
				],
			],
		],
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<ToggleGroupControl
						label="Style"
						isBlock
						value={ props.attributes.style ?? 'classic' }
						onChange={ ( value ) =>
							props.setAttributes( { style: value } )
						}
					>
						<ToggleGroupControlOption
							value="classic"
							label="Classic"
						/>
						<ToggleGroupControlOption
							value="modern"
							label="Modern"
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<InnerBlocks template={ TEMPLATE } />
			</div>
		</>
	);
};
