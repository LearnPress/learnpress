/**
 * Register block course item curriculum.
 */
import { edit } from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/course-item-curriculum', {
	...metadata,
icon: {
	src: (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
		>
			<path d="M3 6h7a2 2 0 012 2v11a2 2 0 01-2-2H3z" />
			<path d="M21 6h-7a2 2 0 00-2 2v11a2 2 0 002-2h7z" />
		</svg>
	),
},
	edit,
	save,
} );
