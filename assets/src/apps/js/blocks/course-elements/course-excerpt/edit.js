import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const courseExcerpt =
		lpCourseData?.excerpt ||
		'<div className="course-short-description"><p>Comprehenditur asotorum fratre deterreantur lyco quaeret oritur dicitur concedere nova…</p></div>';
	return (
		<>
			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseExcerpt,
				} }
			></div>
		</>
	);
};
