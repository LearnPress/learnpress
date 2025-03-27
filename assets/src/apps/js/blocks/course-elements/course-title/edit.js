import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;

	const { lpCourseData } = context;

	return (
		<div { ...blockProps }>
			<div>{ lpCourseData?.post_title ?? __( 'Course Title', 'learnpress' ) }</div>
		</div>
	);
};

export default Edit;
