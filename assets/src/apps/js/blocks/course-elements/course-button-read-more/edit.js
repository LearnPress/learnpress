import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseButton = __( 'Read more', 'learnpress' );

	let classOfDiv = blockProps.className;
	classOfDiv = classOfDiv.replaceAll( 'wp-block-learnpress-course-button-read-more', '' );
	return (
		<>
			<a
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseButton,
				} }
			></a>
		</>
	);
};

export default Edit;
