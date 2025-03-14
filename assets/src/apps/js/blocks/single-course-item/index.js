// In a JavaScript file (course-meta-block.js)
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, PanelBody } from '@wordpress/components';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

registerBlockType( 'learnpress/single-course-item', {
	title: 'Single Course Item',
	icon: 'book-alt',
	category: 'learnpress-category',
	attributes: {
		courseData: { // Store data from the server
			type: 'object',
		},
	},
	providesContext: { // Share data with child blocks
		'learnpress/courseData': 'courseData',
	},
	supports: {
		html: false,
	},
	edit( props ) {
		const { attributes, setAttributes } = props;
		// eslint-disable-next-line react-hooks/rules-of-hooks
		const blockProps = useBlockProps();
		const ALLOWED_BLOCKS = [ 'learnpress/course-title', 'core/image', 'core/group' ];
		const TEMPLATE_BLOCKS = [
			'core/columns',
			{},
			[
				[ 'learnpress/course-title' ],
				[ 'core/image' ],
			],
		];
		// eslint-disable-next-line react-hooks/rules-of-hooks
		const posts = useSelect( ( select ) => {
			return select( 'core' ).getEntityRecords( 'postType', 'lp_course', { per_page: 1 } );
		}, [] );

		if ( posts && posts.length ) {
			setAttributes( { courseData: posts[ 0 ] } );
		}

		console.log( attributes );

		return (
			<div { ...blockProps }>
				<InnerBlocks allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE_BLOCKS }
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
