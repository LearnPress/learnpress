import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				label={ __( 'Item Comment', 'learnpress' ) }
			>
			</Placeholder>
		</div>
	);
};

export default Edit;
