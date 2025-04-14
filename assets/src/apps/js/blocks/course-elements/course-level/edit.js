import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseLevel =
		lpCourseData?.level ||
		'<span>Level: All levels</span><div class="info-meta-item"><div class="info-meta-left"><i class="lp-icon-signal"></i><span>Level:</span></div><span class="info-meta-right"><div class="course-count-level"><span class="course-level">All levels</span></div></span></div>';
	return (
		<>
			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseLevel,
				} }
			></div>
		</>
	);
};
