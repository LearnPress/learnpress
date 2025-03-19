import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				label={ __( 'Single Course (Legacy)', 'learnpress' ) }
			>
				<div>
					{
						__(
							'Display full content of Single Course, can not edit.',
							'learnpress'
						)
					}
				</div>
			</Placeholder>
		</div>
	);
};

export default Edit;
