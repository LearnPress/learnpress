/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/image-single-course', {
	...metadata,
	edit: ( props ) => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				{ 'Image Single Course' }
			</div>
		);
	},
	save: ( props ) => {
		return null;
	},
} );
