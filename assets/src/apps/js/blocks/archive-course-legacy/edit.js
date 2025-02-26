import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';

export const edit = ( props ) => {
	const { attributes: { content }, setAttributes } = props;

	return (
		<Placeholder
			label={ __( 'Archive Course (Legacy)', 'learnpress' ) }
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
	);
};
