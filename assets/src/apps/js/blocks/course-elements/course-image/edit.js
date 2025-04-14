import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const courseImage =
		lpCourseData?.image || '<div className="course-img"></div>';
	return (
		<>
			<a>
				<div
					{ ...blockProps }
					dangerouslySetInnerHTML={ {
						__html: courseImage,
					} }
				></div>
			</a>
		</>
	);
};
