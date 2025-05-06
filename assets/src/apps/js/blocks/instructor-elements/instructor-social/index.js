/**
 * Register block instructor social property.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { customLink } from '@wordpress/icons';

registerBlockType( metadata.name, {
	...metadata,
	icon: customLink,
	edit,
	save,
} );
