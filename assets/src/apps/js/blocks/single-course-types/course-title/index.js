/**
 * Register block single course.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';

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
	ancestor: [ 'learnpress/single-course' ],
	supports: {

	},
	edit( props ) {
		const { attributes, setAttributes } = props;

		return (
			<>
				<InnerBlocks>
					<div>Course Title</div>
				</InnerBlocks>
			</>
		);
	},
	save( props ) {
		const { attributes } = props;

		return (
			<>
				<InnerBlocks.Content />
			</>
		);
	},
},
);
