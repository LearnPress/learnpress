/**
 * Register block item curriculum.
 */

import edit from './edit.js';
import { save } from './save.js';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { checkTemplatesCanLoadBlock } from '../../utilBlock.js';

const templatesName = [ 'learnpress/learnpress//single-lp_course_item' ];

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
