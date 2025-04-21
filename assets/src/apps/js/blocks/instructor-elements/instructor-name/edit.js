import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<h2>
					<span className="instructor-display-name">
						{ __( "Instructor's name", 'learnpress' ) }
					</span>
				</h2>
			</div>
		</>
	);
};
