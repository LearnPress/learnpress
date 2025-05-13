/**
 * Check if the block can be loaded in the current template.
 *
 * @since 4.2.8.4
 * @version 1.0.0
 */
import { unregisterBlockType, getBlockType } from '@wordpress/blocks';
import { subscribe, select } from '@wordpress/data';

let currentPostIdOld = null;
const checkTemplatesCanLoadBlock = ( templates, metadata, callBack ) => {
	subscribe( () => {
		const metaDataNew = { ...metadata };
		const store = select( 'core/editor' ) || null;

		if ( ! store || typeof store.getCurrentPostId !== 'function' || ! store.getCurrentPostId() ) {
			return;
		}

		const currentPostId = store.getCurrentPostId();

		if ( currentPostId === null ) {
			return;
		}

		if ( currentPostIdOld === currentPostId ) {
			return;
		}

		currentPostIdOld = currentPostId;
		if ( getBlockType( metaDataNew.name ) ) {
			unregisterBlockType( metaDataNew.name );

			if ( templates.includes( currentPostId ) ) {
				metaDataNew.ancestor = null;
				callBack( metaDataNew );
			} else {
				if ( ! metaDataNew.ancestor ) {
					metaDataNew.ancestor = [];
				}
				callBack( metaDataNew );
			}
		}
	} );
};

export { checkTemplatesCanLoadBlock };
