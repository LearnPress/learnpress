/**
 * Register block single course.
 */
import { edit } from './edit';
import { save } from './save';
import block from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/item-example', {
	...block,
	edit,
	save,
} );
