import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseLevel = lpCourseData?.level ||
		'<div class="course-count-level"><span class="course-level">All levels</span></div>';

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<ToggleControl
						label="Show Label"
						checked={ attributes.showLabel }
						onChange={ ( value ) => {
							setAttributes( {
								showLabel: value,
							} );
						} }
					/>
					<ToggleControl
						label="Show Icon"
						checked={ attributes.showIcon }
						onChange={ ( value ) => {
							setAttributes( {
								showIcon: value,
							} );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="info-meta-item">
					<span className="info-meta-left">
						{ attributes.showIcon && (
							<span dangerouslySetInnerHTML={ { __html: '<i class="lp-icon-signal"></i>' } } />
						) }
						{ attributes.showLabel ? 'Level:' : '' }
					</span>
					<span className="info-meta-right" dangerouslySetInnerHTML={ {
						__html: courseLevel,
					} }></span>
				</div>
			</div>
		</>
	);
};

export default Edit;
