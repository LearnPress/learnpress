import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="category-course">
					<div className="category">
						{ 'in Category' }
					</div>
				</div>
			</div>
		</>
	);
};
