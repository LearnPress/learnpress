import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	const TEMPLATE = [];

	return (
		<>
			<div { ...blockProps }>{ <InnerBlocks /> }</div>
		</>
	);
};
