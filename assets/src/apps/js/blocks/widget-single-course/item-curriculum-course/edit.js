import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="item-curriculum-course">
					<p>{ 'Item Curriculum' }</p>
				</div>
			</div>
		</>
	);
};
