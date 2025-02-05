import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-results-progress" style={ { marginBottom: '10px' } }>
					<div className="items-progress" style={ { display: 'flex', justifyContent: 'space-between' } }>
						<strong>{ 'Lessons completed: ' }</strong>
						<span className="number">{ '0/1' }</span>
					</div>

					<div className="items-progress" style={ { display: 'flex', justifyContent: 'space-between' } }>
						<strong>{ 'Quizzes finished: ' }</strong>
						<span>{ '0/1' }</span>
					</div>

					<div className="items-progress" style={ { display: 'flex', justifyContent: 'space-between' } }>
						<strong>{ 'Course progress: ' }</strong>
						<span>{ '0%' }</span>
					</div>
				</div>
			</div>
		</>
	);
};
