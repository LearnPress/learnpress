import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<span>{ '0 Quiz' }</span>
			</div>
		</>
	);
};
