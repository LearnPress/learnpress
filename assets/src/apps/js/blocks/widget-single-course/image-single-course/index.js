/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/image-single-course', {
	...metadata,
	edit: ( props ) => {
		return <div>{ 'Image Single Course' }</div>;
	},
	save: ( props ) => {
		return null;
	},
} );
