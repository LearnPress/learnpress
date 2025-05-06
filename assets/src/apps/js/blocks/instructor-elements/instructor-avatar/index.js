/**
 * Register block instructor avatar.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { commentAuthorAvatar } from '@wordpress/icons';

registerBlockType( metadata.name, {
	...metadata,
	icon: commentAuthorAvatar,
	edit,
	save,
} );
