
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { page } from '@wordpress/icons';

const TEMPLATES = {
	'single-course': {
		title: __( 'LearnPress Single Course Template', 'learnpress' ),
		placeholder: 'single-course',
	},
	'archive-course': {
		title: __( 'LearnPress Course Archive Template', 'learnpress' ),
		placeholder: 'archive-course',
	},
};

export default function Edit( { attributes } ) {
	const blockProps = useBlockProps();
	const templateTitle = TEMPLATES[ attributes.template ]?.title ?? attributes.template;

	return (
		<div { ...blockProps }>
			<Placeholder
				icon={ page }
				label={ templateTitle }
				className="wp-block-learnpress-template__placeholder"
			>
				<div className="wp-block-learnpress-template__placeholder-inner">
					{ sprintf(
						/* translators: %s is the template title */
						__(
							'This is an editor placeholder for the %s. This will be replaced by the template in your store and displayed with your course image(s), title, price, and so on. You can move this placeholder around and add further blocks around it to extend the template.',
							'learnpress'
						),
						templateTitle
					) }
				</div>
			</Placeholder>
		</div>
	);
}
