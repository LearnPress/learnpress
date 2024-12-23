/**
 * Register block archive property.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/tabs-single-course', {
	...metadata,
	icon: {
		src: (
			<svg
				viewBox="0 0 24 24"
				xmlns="http://www.w3.org/2000/svg"
				width="24"
				height="24"
				className="wc-block-editor-components-block-icon"
				aria-hidden="true"
				focusable="false"
			>
				<path d="M3 6h11v1.5H3V6Zm3.5 5.5h11V13h-11v-1.5ZM21 17H10v1.5h11V17Z"></path>
			</svg>
		),
	},
	edit,
	save,
} );
