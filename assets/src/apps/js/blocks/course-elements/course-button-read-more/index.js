/**
 * Register block course button.
 */

import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { checkTemplatesCanLoadBlock } from '../../utilBlock.js';

registerBlockType( metadata.name, {
	...metadata,
	edit,
	save,
} );
