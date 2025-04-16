import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseButton = lpCourseData?.button || '';
	return (
		<>
			{ lpCourseData?.button ? (
				<div
					{ ...blockProps }
					dangerouslySetInnerHTML={ {
						__html: courseButton,
					} }
				></div>
			) : (
				<div className="course-buttons">
					<button { ...blockProps }>{ 'Buy Now' }</button>
 				</div>
			) }
		</>
	);
};
