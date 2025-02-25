import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	const TEMPLATE = [
		[ 'learnpress/tabs-single-course', {} ],
		[ 'learnpress/requirements-single-course', {} ],
		[ 'learnpress/features-single-course', {} ],
		[ 'learnpress/target-audiences-single-course', {} ],
		[ 'learnpress/comment', {} ],
	];
	return (
		<>
			<div { ...blockProps } >
				<div className="course-content-left">
					<InnerBlocks template={ TEMPLATE } />
				</div>
			</div>
		</>
	);
};
