/**
 * Register block course title.
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/list-courses', {
	...metadata,
	edit,
	save,
} );
