import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseQuiz =
		lpCourseData?.quiz ||
		'<div class="info-meta-item"><div class="info-meta-left"><i class="lp-icon-puzzle-piece"></i><span>Quiz:</span></div><span class="info-meta-right"><div class="course-count-quiz"><div class="course-count-item lp_quiz">9 Quizzes</div></div></span></div>';
	return (
		<>
			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseQuiz,
				} }
			></div>
		</>
	);
};
