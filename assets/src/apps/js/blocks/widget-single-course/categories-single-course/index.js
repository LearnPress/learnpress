/**
 * Register block archive property.
 */
import metadata from './block.json';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType( 'learnpress/categories-single-course', {
	...metadata,
	edit: ( props ) => {
		const blockProps = useBlockProps();
		const updateCourseId = ( e ) => {
			if ( ! e.target.value ) {
				props.setAttributes( { courseId: '' } );
			}

			if ( Number( e.target.value ) ) {
				props.setAttributes( { courseId: Number( e.target.value ) } );
			}
		};

		return (
			<div { ...blockProps }>
				<strong>
					{ 'List Categories Single Course' }
				</strong>
				<br />
				<span>
					{ 'Course ID' }
				</span>
				<input value={ props.attributes.courseId } onChange={ updateCourseId } placeholder="Get current post"></input>
			</div>
		);
	},
	save: ( props ) => {
		return null;
	},
} );
