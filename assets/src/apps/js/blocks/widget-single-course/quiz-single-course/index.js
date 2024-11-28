/**
 * Register block archive property.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/quiz-single-course', {
	...metadata,
	edit,
	save,
} );
