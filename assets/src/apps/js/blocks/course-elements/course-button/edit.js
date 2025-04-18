import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	// classOfDiv to fix align.
	let classOfDiv = blockProps.className;
	classOfDiv = classOfDiv.replaceAll( 'wp-block-learnpress-course-button', '' );

	return (
		<div className={ classOfDiv }>
			<button { ...blockProps }>{ __( 'Buy Now', 'learnpress' ) }</button>
		</div>
	);
};

export default Edit;
