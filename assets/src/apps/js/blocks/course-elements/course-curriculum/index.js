/**
 * Register block course curriculum.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'learnpress/course-curriculum', {
	...metadata,
	icon: {
		src: (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				width="24"
				height="24"
				className="wc-block-editor-components-block-icon"
				aria-hidden="true"
				focusable="false"
			>
				<path
					fillRule="evenodd"
					d="M16.83 6.342l.602.3.625-.25.443-.176v12.569l-.443-.178-.625-.25-.603.301-1.444.723-2.41-.804-.475-.158-.474.158-2.41.803-1.445-.722-.603-.3-.625.25-.443.177V6.215l.443.178.625.25.603-.301 1.444-.722 2.41.803.475.158.474-.158 2.41-.803 1.445.722zM20 4l-1.5.6-1 .4-2-1-3 1-3-1-2 1-1-.4L5 4v17l1.5-.6 1-.4 2 1 3-1 3 1 2-1 1 .4 1.5.6V4zm-3.5 6.25v-1.5h-8v1.5h8zm0 3v-1.5h-8v1.5h8zm-8 3v-1.5h8v1.5h-8z"
					clipRule="evenodd"
				></path>
			</svg>
		),
	},
	edit,
	save,
} );
