import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const courseDuration =
		lpCourseData?.duration ||
		'<div class="info-meta-item"><div class="info-meta-left"><i class="lp-icon-clock-o"></i><span>Duration:</span></div><span class="info-meta-right"><div class="course-count-duration"><span class="course-duration">10 Weeks</span></div></span></div>';
	return (
		<>
			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseDuration,
				} }
			></div>
		</>
	);
};
