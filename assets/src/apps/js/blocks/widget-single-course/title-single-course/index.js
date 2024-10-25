/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/title-single-course', {
	...metadata,
	edit: ( props ) => {
		return <div>{ 'Title Single Course' }</div>;
	},
	save: ( props ) => {
		return null;
	},
} );
