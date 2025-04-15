import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="instructor-avatar">
					<img src="https://placehold.co/300x300?text=Avatar+Instructor" />
				</div>
			</div>
		</>
	);
};
