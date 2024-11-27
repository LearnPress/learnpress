import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const save = ( props ) => {
	const blockProps = useBlockProps.save();
	return (
		<>
			<InnerBlocks.Content />
		</>
	);
};
