import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseLesson =
		lpCourseData?.lesson ||
		'<div class="info-meta-item"><div class="info-meta-left"><i class="lp-icon-file-o"></i><span>Lesson:</span></div><span class="info-meta-right"><div class="course-count-lesson"><div class="course-count-item lp_lesson">5 Lessons</div></div></span></div>';
	return (
		<>
			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseLesson,
				} }
			></div>
		</>
	);
};
