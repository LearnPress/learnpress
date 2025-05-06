/**
 * Register block course order by property.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/course-order-by', {
	...metadata,
	edit,
	save,
} );
