import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="instructor-description">
					<p>
						{
							'It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.'
						}
					</p>
				</div>
			</div>
		</>
	);
};
