/**
 * Register block instructor name.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { title } from '@wordpress/icons';

registerBlockType( metadata.name, {
	...metadata,
	icon: title,
	edit,
	save,
} );
