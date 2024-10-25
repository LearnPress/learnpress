/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/title-single-course', {
	...metadata,
	edit: ( props ) => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				{ 'Title Single Course' }
			</div>
		);
	},
	save: ( props ) => {
		return null;
	},
} );
