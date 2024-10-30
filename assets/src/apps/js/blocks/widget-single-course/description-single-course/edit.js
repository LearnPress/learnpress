import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
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
				{ 'Description Single Course' }

			</strong>
			<br />
			<span>
				{ 'Course ID' }
			</span>
			<input value={ props.attributes.courseId } onChange={ updateCourseId } placeholder="Get current post"></input>
		</div>
	);
};
