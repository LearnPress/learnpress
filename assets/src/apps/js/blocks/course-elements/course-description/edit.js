import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const courseDescription =
		lpCourseData?.description ||
		'<h3>Description</h3> <div className="lp-course-description"> <p>Ullo fecit epicurus necesse manilium plebiscito intrandum facto sequamur habemus nostrane adipiscing vocatur poterit caeleste</p> <p>Beatus neget maximarum superiores dacere veriusque isto anquam congressu reprehendi</p></div>';
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
