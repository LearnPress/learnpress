/**
 * Register block instructor social property.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { customLink } from '@wordpress/icons';
import { checkTemplatesCanLoadBlock } from '../../utilBlock.js';
const templatesName = [ Number( lpDataAdmin?.single_instructor_id ) ];

checkTemplatesCanLoadBlock( templatesName, metadata, ( metadataNew ) => {
	registerBlockType( metadataNew.name, {
		...metadataNew,
		edit,
		save,
	} );
} );
registerBlockType( metadata.name, {
	...metadata,
	icon: customLink,
	edit,
	save,
} );
