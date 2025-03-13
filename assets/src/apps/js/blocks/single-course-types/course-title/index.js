/**
 * Register block single course.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, BlockControls, AlignmentToolbar, useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/course-title', {
	$schema: 'https://schemas.wp.org/trunk/block.json',
	apiVersion: 3,
	name: 'learnpress/course-title',
	title: 'Course Title',
	category: 'learnpress-category',
	description: '',
	textdomain: 'learnpress',
	keywords: [ 'single course', 'learnpress' ],
	usesContext: [],
	//ancestor: [ 'learnpress/single-course' ],
	supports: {
		align: [ 'wide', 'full' ],
		color: true,
		html: false,
	},
	attributes: {
		type: 'string',
	},
	edit( props ) {
		// eslint-disable-next-line react-hooks/rules-of-hooks
		const blockProps = useBlockProps();
		const { attributes, setAttributes } = props;
		const { align } = attributes;

		return (
			<div { ...blockProps }>
				<BlockControls>
					<AlignmentToolbar
						value={ align }
						onChange={ ( newAlign ) => {
							setAttributes( { align: newAlign } );
						} }
					/>
				</BlockControls>
				<div style={ { textAlign: align } }>Course Title</div>
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
},
);
