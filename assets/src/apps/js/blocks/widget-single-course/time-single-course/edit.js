import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-time">
					<p className="course-time-row">
						<strong>{ 'You started on: ' }</strong>
						<time className="entry-date enrolled">{ 'Start time' }</time>
					</p>
					<p className="course-time-row">
						<strong>{ 'Course will end: ' }</strong>
						<time className="entry-date expire">{ 'End time' }</time>
					</p>
				</div>
			</div>
		</>
	);
};
