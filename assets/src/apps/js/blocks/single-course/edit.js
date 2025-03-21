import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
		</>
	);
};
