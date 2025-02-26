import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<div { ...blockProps }>
				<div className="course-progress">
					<span>{ 'Course passing progress: 0%' }</span>
					<div className="line"></div>
					<span>{ 'Start date: 2025' }</span>
				</div>
			</div>
		</>
	);
};
