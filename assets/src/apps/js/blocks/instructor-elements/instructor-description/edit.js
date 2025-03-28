import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="instructor-description">
					<p>{ 'Description' }</p>
				</div>
			</div>
		</>
	);
};
