// In a JavaScript file (course-meta-block.js)
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, PanelBody } from '@wordpress/components';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/single-course', {
	title: 'Single Course',
	icon: 'book-alt',
	category: 'widgets',
	attributes: {

	},
	supports: {
		html: false,
	},
	edit( props ) {
		const { attributes, setAttributes } = props;
		// eslint-disable-next-line react-hooks/rules-of-hooks
		const blockProps = useBlockProps();
		const ALLOWED_BLOCKS = [ 'learnpress/course-title' ];

		return (
			<div { ...blockProps }>
				<InnerBlocks allowedBlocks={ ALLOWED_BLOCKS }
					template={ [
						[ 'learnpress/course-title' ],
					] }
					templateLock={ false }
				/>
			</div>
		);
	},
	save( props ) {
		const blockProps = useBlockProps.save();

		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
			</div>
		);
	},
} );
