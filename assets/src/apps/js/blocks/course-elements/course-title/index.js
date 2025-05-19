/**
 * Register block course title.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { checkTemplatesCanLoadBlock } from '../../utilBlock.js';
import { title } from '@wordpress/icons';

const templatesName = [
	'learnpress/learnpress//single-lp_course',
	'learnpress/learnpress//single-lp_course-offline',
];

/**
 * Check if the block can load in the template editor: single-lp_course.
 * if it is editing on this template, set ancestor to null
 */
checkTemplatesCanLoadBlock( templatesName, metadata, ( metadataNew ) => {
	registerBlockType( metadataNew.name, {
		...metadataNew,
		icon: title,
		edit,
		save,
	} );
} );

// Register the block with the original metadata, ancestor will be set on block.json
registerBlockType( metadata.name, {
	...metadata,
	icon: title,
	edit,
	save,
} );
