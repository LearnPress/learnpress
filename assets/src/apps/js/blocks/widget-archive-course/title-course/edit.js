import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<span style={ { lineHeight: '1.3', fontSize: '1.5em' } }>
					{ 'Title' }
				</span>
			</div>
		</>
	);
};
