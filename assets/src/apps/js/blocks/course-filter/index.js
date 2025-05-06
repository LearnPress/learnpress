/**
 * Register block course filter.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/course-filter', {
	...metadata,
	edit,
	save,
} );
