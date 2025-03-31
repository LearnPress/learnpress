import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const courseDescription =
		lpCourseData?.description ||
		'<h3>Description</h3> <div className="line"></div> <div className="line"></div> <div className="line"></div>';
	return (
		<>
			<div
				{ ...blockProps }
				dangerouslySetInnerHTML={ {
					__html: courseDescription,
				} }
			></div>
		</>
	);
};
