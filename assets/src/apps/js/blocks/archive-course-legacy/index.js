/**
 * Register block archive property.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
const { registerBlockType } = wp.blocks;

registerBlockType( 'learnpress/archive-course-legacy', {
	...metadata,
	edit,
	save,
} );
