/**
 * Register block archive property.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/categories-single-course', {
	...metadata,
	edit,
	save,
} );
