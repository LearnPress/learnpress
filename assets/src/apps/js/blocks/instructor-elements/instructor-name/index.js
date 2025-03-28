/**
 * Register block instructor name.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/instructor-name', {
	...metadata,
	icon: {
		src: (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				width="24"
				height="24"
				aria-hidden="true"
				focusable="false"
			>
				<path d="M6 5V18.5911L12 13.8473L18 18.5911V5H6Z"></path>
			</svg>
		),
	},
	edit,
	save,
} );
