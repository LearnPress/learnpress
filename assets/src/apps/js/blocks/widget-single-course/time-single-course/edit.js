import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<TextControl
						label="Course ID"
						value={ props.attributes.courseId }
						help="The default value is the current course id"
						type="number"
						onChange={ ( value ) => props.setAttributes( { courseId: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
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
