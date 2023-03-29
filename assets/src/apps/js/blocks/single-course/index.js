/**
 * Register block single course.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/single-course', {
	...metadata,
	edit,
	save,
} );
