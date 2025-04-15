import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<h2>
					<span className="instructor-display-name">
						{ "Instructor's name" }
					</span>
				</h2>
			</div>
		</>
	);
};
