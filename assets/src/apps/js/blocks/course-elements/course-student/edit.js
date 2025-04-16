import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseStudent =
		lpCourseData?.student ||
		'<div class="course-count-student"><div class="course-count-student">3 Student</div></div>';

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<ToggleControl
						label="Show Label"
						checked={attributes.showLabel ? true : false}
						onChange={(value) => {
							setAttributes({
								showLabel: value ? true : false,
							});
						}}
					/>
					<ToggleControl
						label="Show Icon"
						checked={attributes.showIcon ? true : false}
						onChange={(value) => {
							setAttributes({
								showIcon: value ? true : false,
							});
						}}
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{...blockProps}
			>
				<div className="info-meta-item">
					<span className="info-meta-left">
						{props.attributes.showIcon && (
							<span dangerouslySetInnerHTML={{__html: '<i class="lp-icon-user-graduate"></i>'}}/>
						)}
						{props.attributes.showLabel ? 'Student:' : ''}
					</span>
					<span className="info-meta-right" dangerouslySetInnerHTML={{
						__html: courseStudent,
					}}></span>
				</div>
			</div>
		</>
	);
};
