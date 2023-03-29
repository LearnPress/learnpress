import { page } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				icon={ page }
				label={ __( 'Item Curriculum Course', 'learnpress' ) }
			>
				<div>
					{
						__(
							'This is an editor placeholder for the Item Curriculum Course page. Content will render content of single item curriculum course. Should be not remove it',
							'learnpress'
						)
					}
				</div>
			</Placeholder>
		</div>
	);
};
