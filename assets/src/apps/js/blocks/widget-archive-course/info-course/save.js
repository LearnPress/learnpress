import { useBlockProps } from '@wordpress/block-editor';

export const save = ( props ) => {
	const blockProps = useBlockProps.save();
	const content = '{{info-course}}';
	return (
		<>
			{ content }
		</>
	);
};
