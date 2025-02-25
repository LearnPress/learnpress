import { InnerBlocks, useInnerBlocksProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useInnerBlocksProps();

	const TEMPLATE = [
		[ 'learnpress/duration-single-course', {} ],
		[ 'learnpress/level-single-course', {} ],
		[ 'learnpress/lesson-single-course', {} ],
		[ 'learnpress/quiz-single-course', {} ],
		[ 'learnpress/student-single-course', {} ],
	];
	return (
		<>
			<div { ...blockProps }>
				<div className="wp-block-learnpress-course-meta-secondary">
					<InnerBlocks template={ TEMPLATE } />
				</div>
			</div>
		</>
	);
};
