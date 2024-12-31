import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	const TEMPLATE = [
		[ 'learnpress/profile-background-image', {} ],
		[
			'core/group',
			{
				className: 'wrapper-profile-header wrap-fullwidth',
				layout: { type: 'flex', flexWrap: 'nowrap' },
			},
			[
				[
					'core/group',
					{
						className: 'lp-profile-left',
						layout: { type: 'constrained' },
					},
					[ [ 'learnpress/profile-avatar', {} ] ],
				],
				[
					'core/group',
					{
						className: 'lp-profile-right',
						layout: { type: 'default' },
					},
					[ [ 'learnpress/profile-username', {} ] ],
				],
			],
		],
		[ 'learnpress/profile-content', {} ],
		[ 'learnpress/profile-sidebar', {} ],
	];

	return (
		<>
			<div { ...blockProps }>
				{ <InnerBlocks /*  template={ TEMPLATE } */ /> }
			</div>
		</>
	);
};
