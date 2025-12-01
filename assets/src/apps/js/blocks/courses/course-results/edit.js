import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div class="course-results">
					{ sprintf( __( 'Showing %d-%d of %d results', 'learnpress' ), 1, 6, 20 ) }
				</div>
			</div>
		</>
	);
};
