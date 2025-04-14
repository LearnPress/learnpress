import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;

	const courseStudent =
		lpCourseData?.student || '<div class="info-meta-item"><div class="info-meta-left"><i class="lp-icon-user-graduate"></i><span>Student:</span></div><span class="info-meta-right"><div class="course-count-student"><div class="course-count-student">1 Student</div></div></span></div>';
	return (
		<>
			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseStudent,
				} }
			></div>
		</>
	);
};
