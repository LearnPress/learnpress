/**
 * Register block archive property.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { checkTemplatesCanLoadBlock } from '../../utilBlock.js';

const block_name = 'learnpress/course-quiz';
const templatesName = [ 'learnpress/learnpress//single-lp_course' ];

/**
 * Check if the block can load in the template editor: single-lp_course.
 * if it is editing on this template, set ancestor to null
 */
checkTemplatesCanLoadBlock( templatesName, block_name, metadata, ( metadataNew ) => {
	registerBlockType( block_name, {
		...metadataNew,
		edit,
		save,
	} );
} );

registerBlockType( block_name, {
	...metadata,
	edit,
	save,
} );
