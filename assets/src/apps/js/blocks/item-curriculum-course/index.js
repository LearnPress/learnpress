/**
 * Register block single course.
 */
import { edit } from './edit';
import { save } from './save';
import block from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( block.name, {
	...block,
	edit,
	save,
} );
