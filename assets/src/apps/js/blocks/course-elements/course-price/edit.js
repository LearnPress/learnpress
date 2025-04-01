import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { RawHTML } from '@wordpress/element';

const Edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseQuery } = context;
	const coursePrice = lpCourseQuery?.price || __( 'Course Price', 'learnpress' );

	return (
		<>
			<div { ...blockProps }>
				<RawHTML>{ coursePrice }</RawHTML>
			</div>
		</>
	);
};

export default Edit;
