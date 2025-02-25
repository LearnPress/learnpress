import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps } style={ { display: 'flex', flexWrap: 'wrap' } }>
				<div style={ { width: '50%' } }>
					<strong>{ 'Instructor' }</strong>
				</div>
				<div>
					<strong>{ 'Category' }</strong>
				</div>
			</div>
		</>
	);
};
