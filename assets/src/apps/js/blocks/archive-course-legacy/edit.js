import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				label={ __( 'Archive Course (Legacy)', 'learnpress' ) }
			>
				<div>
					{
						__(
							'The block will display the full content of the course archive page. Elements on it cannot be modified!',
							'learnpress'
						)
					}
				</div>
			</Placeholder>
		</div>
	);
};

export default Edit;
