/**
 * Register block archive property.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { checkTemplatesCanLoadBlock } from '../utilBlock.js';
import { registerBlockType } from '@wordpress/blocks';
const templatesName = [
	'learnpress/learnpress//archive-lp_course',
	'learnpress/learnpress//taxonomy-course_category',
	'learnpress/learnpress//archive-course_tag',
];

checkTemplatesCanLoadBlock( templatesName, metadata, ( metadataNew ) => {
	registerBlockType( metadataNew.name, {
		...metadataNew,
		edit,
		save,
	} );
} );

registerBlockType( metadata.name, {
	...metadata,
	edit,
	save,
} );
