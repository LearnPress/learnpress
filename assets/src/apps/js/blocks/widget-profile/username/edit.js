import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="profile-username">
					<strong>{ 'Username' }</strong>
				</div>
			</div>
		</>
	);
};
