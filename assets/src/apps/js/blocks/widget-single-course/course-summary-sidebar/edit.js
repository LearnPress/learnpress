import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const TEMPLATE = [
		[ 'learnpress/image-single-course', {} ],
		[ 'learnpress/price-single-course', {} ],
		[ 'learnpress/btn-purchase-single-course', {} ],
		[ 'learnpress/time-single-course', {} ],
		[ 'learnpress/progress-single-course', {} ],
		[ 'learnpress/feature-review-single-course', {} ],
	];
	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks template={ TEMPLATE } />
			</div>
		</>
	);
};
