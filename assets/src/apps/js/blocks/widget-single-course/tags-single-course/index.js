/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/tags-single-course', {
	...metadata,
	edit: ( props ) => {
		const blockProps = useBlockProps();

		return <div { ...blockProps }>
			{ 'Tags Single Course' }
		</div>;
	},
	save: ( props ) => {
		return null;
	},
} );
