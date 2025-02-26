import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';

export const edit = ( props ) => {
	return (
		<Placeholder
			label={ __( 'Single Course (Legacy)', 'learnpress' ) }
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
	);
};
