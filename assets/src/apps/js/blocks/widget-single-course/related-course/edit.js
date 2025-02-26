import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="related-course">
					<h3>{ 'Related Course' }</h3>
					<div className="list-course">
						<div className="course-item"></div>
						<div className="course-item"></div>
						<div className="course-item"></div>
						<div className="course-item"></div>
					</div>
				</div>
			</div>
		</>
	);
};
