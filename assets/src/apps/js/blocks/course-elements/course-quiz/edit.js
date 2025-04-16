import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseQuiz =
		lpCourseData?.quiz ||
		'<div class="course-count-quiz"><div class="course-count-item lp_quiz">9 Quizzes</div></div>';

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<ToggleControl
						label="Show Label"
						checked={props.attributes.showLabel ? true : false}
						onChange={(value) => {
							props.setAttributes({
								showLabel: value ? true : false,
							});
						}}
					/>
					<ToggleControl
						label="Show Icon"
						checked={props.attributes.showIcon ? true : false}
						onChange={(value) => {
							props.setAttributes({
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
							<span dangerouslySetInnerHTML={{__html: '<i class="lp-icon-puzzle-piece"></i>'}}/>
						)}
						{props.attributes.showLabel ? 'Quiz:' : ''}
					</span>
					<span className="info-meta-right" dangerouslySetInnerHTML={{
						__html: courseQuiz,
					}}></span>
				</div>
			</div>
		</>
	);
};
