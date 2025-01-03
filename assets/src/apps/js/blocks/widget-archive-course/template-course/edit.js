import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const template = [
		[ 'learnpress/media-course-archive-course', {} ],
		[ 'learnpress/title-course-archive-course', {} ],
		[ 'learnpress/instructor-category-course-archive-course', {} ],
		[ 'learnpress/meta-course-archive-course', {} ],
		[ 'learnpress/info-course-archive-course', {} ],
	];

	const renderInnerBlocks = () => {
		const blocks = [];
		for ( let i = 0; i < 3; i++ ) {
			blocks.push(
				<div className="course-item" key={ i }>
					<InnerBlocks
						template={ template }
						templateLock={ false }
					/>
				</div>
			);
		}
		return blocks;
	};

	return (
		<>
			<div { ...blockProps }>
				<div className="template-course">
					{ renderInnerBlocks() }
				</div>
			</div>
		</>
	);
};
