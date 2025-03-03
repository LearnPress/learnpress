// In a JavaScript file (course-meta-block.js)
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, PanelBody } from '@wordpress/components';
import { InnerBlocks } from '@wordpress/block-editor';

registerBlockType( 'learnpress/single-course', {
	title: 'Single Course',
	icon: 'book-alt',
	category: 'widgets',
	attributes: {
		duration: {
			type: 'string',
			default: '',
		},
		level: {
			type: 'string',
			default: '',
		},
		instructor: {
			type: 'string',
			default: '',
		},
	},
	edit( props ) {
		const { attributes, setAttributes } = props;

		return (
			<>
				<InnerBlocks
					template={ [
						[ 'learnpress/course-title' ],
						[ 'core/paragraph', { placeholder: 'Enter content...' } ],
						[ 'core/image' ],
					] }
					templateLock={ false }
				/>
			</>
		);
	},
	save( props ) {
		const { attributes } = props;

		return (
			<div className="course-meta-block">
				<p><strong>Duration:</strong> { attributes.duration }</p>
				<p><strong>Level:</strong> { attributes.level }</p>
				<p><strong>Instructor:</strong> { attributes.instructor }</p>
			</div>
		);
	},
} );
