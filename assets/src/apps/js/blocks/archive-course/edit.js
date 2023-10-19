import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				label={ __( 'Archive Course', 'learnpress' ) }
			>
				<div>
					{
						__(
							'This is an editor placeholder for the Archive Course page. Content will render content of list courses. Should be not remove it',
							'realpress'
						)
					}
				</div>
			</Placeholder>
		</div>
	);
};
