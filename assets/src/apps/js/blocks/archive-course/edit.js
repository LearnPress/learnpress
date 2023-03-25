import {page} from '@wordpress/icons';
import {__} from '@wordpress/i18n';
import {useBlockProps} from '@wordpress/block-editor';
import {Placeholder} from '@wordpress/components';

export const edit = (props) => {
	const blockProps = useBlockProps();

	return (
		<div {...blockProps}>
			<Placeholder
				icon={page}
				label={__('Thim Real Estate Content Archive', 'realpress')}
			>
				<div>
					{
						__(
							'This is an editor placeholder for the Archive Property page. Content will render content of list properties. Should be not remove it',
							'realpress'
						)
					}
				</div>
			</Placeholder>
		</div>
	);
};
