/**
 * Register block course title.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { checkTemplateCanLoadBlock } from '../../utilBlock.js';

const block_name = 'learnpress/course-title';
const templateName = [ 'learnpress/learnpress//single-lp_course' ];

/**
 * Check if the block can load in the template editor: single-lp_course.
 * if it is editing on this template, set ancestor to null
 */
checkTemplateCanLoadBlock( templateName, block_name, metadata, ( metadataNew ) => {
	registerBlockType( block_name, {
		...metadataNew,
		edit,
		save,
	} );
} );

// Register the block with the original metadata, ancestor will be set on block.json
registerBlockType( block_name, {
	...metadata,
	edit,
	save,
} );
