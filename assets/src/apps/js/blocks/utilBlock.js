/**
 * Check if the block can be loaded in the current template.
 *
 * @since 4.2.8.4
 * @version 1.0.0
 */
import { unregisterBlockType, getBlockType } from '@wordpress/blocks';
import { subscribe, select } from '@wordpress/data';

let currentPostIdOld = null;
const checkTemplateCanLoadBlock = ( template, block_name, metadata, callBack ) => {
	const metaDataNew = { ...metadata };
	subscribe( () => {
		const store = select( 'core/editor' );
		const currentPostId = store.getCurrentPostId();

		if ( currentPostId === null ) {
			return;
		}

		if ( currentPostIdOld === currentPostId ) {
			return;
		}

		currentPostIdOld = currentPostId;
		if ( getBlockType( block_name ) ) {
			unregisterBlockType( block_name );

			if ( currentPostId === template ) {
				metaDataNew.ancestor = null;
				callBack( metaDataNew );
			} else {
				callBack( metadata );
			}
		}
	} );
};

export { checkTemplateCanLoadBlock };

