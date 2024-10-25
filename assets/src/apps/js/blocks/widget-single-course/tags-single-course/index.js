/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/tags-single-course', {
	...metadata,
	edit: ( props ) => {
		return <div>{ 'Tags Single Course' }</div>;
	},
	save: ( props ) => {
		return null;
	},
} );
