import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

const Save = ( props ) => {
	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
};

export default Save;
