import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-progress">
					<span>{ 'Course passing progress: 90%' }</span>
					<div className="course-progress__line">
						<div className="course-progress__line__active" style={ { width: '90%' } }></div>
						<div className="course-progress__line__point" style={ { left: '80%' } }></div>
					</div>
					<span>{ 'Start date: 2025' }</span>
				</div>
			</div>
		</>
	);
};
