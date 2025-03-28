import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<h2>
					<span className="instructor-name">
						{ 'Display Name' }
					</span>
				</h2>
			</div>
		</>
	);
};
