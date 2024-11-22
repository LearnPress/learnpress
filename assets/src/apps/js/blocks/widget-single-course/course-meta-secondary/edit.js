import { InnerBlocks, useInnerBlocksProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useInnerBlocksProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="wp-block-learnpress-course-meta-secondary">
					<InnerBlocks />
				</div>
			</div>
		</>
	);
};
