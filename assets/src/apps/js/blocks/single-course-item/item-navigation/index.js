/**
 * Register block item navigation.
 */

import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { checkTemplatesCanLoadBlock } from '../../utilBlock.js';
import { queryPaginationNumbers } from '@wordpress/icons';

const templatesName = [ 'learnpress/learnpress//single-lp_course_item' ];

checkTemplatesCanLoadBlock( templatesName, metadata, ( metadataNew ) => {
	registerBlockType( metadataNew.name, {
		...metadataNew,
		icon: queryPaginationNumbers,
		edit,
		save,
	} );
} );

registerBlockType( metadata.name, {
	...metadata,
	icon: queryPaginationNumbers,
	edit,
	save,
} );
