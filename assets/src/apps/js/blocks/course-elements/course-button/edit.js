import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseButton = lpCourseData?.button || '';
	return (
		<>
			{ lpCourseData?.button ? (
				<div className="course-readmore">
					<a
						{ ...blockProps }
						dangerouslySetInnerHTML={ {
							__html: courseButton,
						} }
					></a>
				</div>
			) : (
				<button { ...blockProps }>{ 'Buy Now' }</button>
			) }
		</>
	);
};

export default Edit;
