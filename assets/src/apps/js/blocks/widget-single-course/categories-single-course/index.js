/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/categories-single-course', {
	...metadata,
	edit: ( props ) => {
		return <div>{ 'List Categories Single Course' }</div>;
	},
	save: ( props ) => {
		return null;
	},
} );
