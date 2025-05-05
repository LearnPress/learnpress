/**
 * Register block course title.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType, unregisterBlockType, getBlockType } from '@wordpress/blocks';
import { subscribe, select } from '@wordpress/data';
import { checkTemplateCanLoadBlock } from '../../utilBlock.js';

// let currentPostIdOld = null;
// const metaDataNew = { ...metadata };
// subscribe( () => {
// 	const store = select( 'core/editor' );
// 	const currentPostId = store.getCurrentPostId();
//
// 	if ( currentPostId !== null ) {
// 		if ( currentPostIdOld !== currentPostId ) {
// 			currentPostIdOld = currentPostId;
//
// 			if ( getBlockType( 'learnpress/course-title' ) ) {
// 				unregisterBlockType( 'learnpress/course-title' );
//
// 				if ( currentPostId === 'learnpress/learnpress//single-lp_course' ) {
// 					metaDataNew.ancestor = null;
//
// 					registerBlockType( 'learnpress/course-title', {
// 						...metaDataNew,
// 						edit,
// 						save,
// 					} );
// 				} else {
// 					registerBlockType( 'learnpress/course-title', {
// 						...metadata,
// 						edit,
// 						save,
// 					} );
// 				}
// 			}
// 		}
// 	}
// } );

const block_name = 'learnpress/course-title';
const templateName = 'learnpress/learnpress//single-lp_course';

/**
 * Check if the block can load in the template editor: single-lp_course.
 * if it is editing on this template, set ancestor to null
 */
checkTemplateCanLoadBlock(
	templateName,
	block_name,
	metadata,
	( metadataNew ) => {
		registerBlockType( block_name, {
			...metadataNew,
			edit,
			save,
		} );
	}
);

// Register the block with the original metadata, ancestor will be set on block.json
registerBlockType( block_name, {
	...metadata,
	edit,
	save,
} );
