import { registerBlockType } from '@wordpress/blocks';

import './category';

import * as template from './template';

const registerBlock = ( block ) => {
	if ( ! block ) {
		return;
	}

	const { metadata, settings, name } = block;

	registerBlockType( name, { ...metadata, ...settings } );
};

/**
 * Function to register blocks.
 */
export const registerLearnPressBlocks = () => {
	[
		template,
	].forEach( registerBlock );
};

registerLearnPressBlocks();
