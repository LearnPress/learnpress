import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				label={ __( 'Single Course', 'learnpress' ) }
			>
				<div>
					{
						__(
							'This is an editor placeholder for the Single Course page. Content will render content of single course. Should be not remove it',
							'learnpress'
						)
					}
				</div>
			</Placeholder>
		</div>
	);
};
