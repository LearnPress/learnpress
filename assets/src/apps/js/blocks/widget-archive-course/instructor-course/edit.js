import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="instructor-course">
					<div className="instructor">
						{ 'by ' }
						<strong>{ 'Instructor' }</strong>
					</div>
				</div>
			</div>
		</>
	);
};
