import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div style={ { margin: '15px 0' } }>
					<strong>
						{ 'Share Course' }
					</strong>
				</div>
			</div>
		</>
	);
};
