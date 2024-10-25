/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/categories-single-course', {
	...metadata,
	edit: ( props ) => {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				{ 'List Categories Single Course' }
			</div>
		);
	},
	save: ( props ) => {
		return null;
	},
} );
