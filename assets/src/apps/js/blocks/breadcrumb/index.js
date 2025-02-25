/**
 * Register block archive property.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/breadcrumb', {
	...metadata,
	icon: {
		src: (
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
				<rect x="4" y="10.5" width="6" height="3" rx="1.5" fill="currentColor"></rect>
				<rect x="12" y="10.5" width="3" height="3" rx="1.5" fill="currentColor"></rect>
				<rect x="17" y="10.5" width="3" height="3" rx="1.5" fill="currentColor"></rect>
			</svg>
		),
	},
	edit,
	save,
} );
