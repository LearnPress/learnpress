/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/description-single-course', {
	...metadata,
	edit: ( props ) => {
		return <div>{ 'Description Single Course' }</div>;
	},
	save: ( props ) => {
		return null;
	},
} );
