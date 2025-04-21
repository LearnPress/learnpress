import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseStudent =
		lpCourseData?.student ||
		'<div class="course-count-student"><div class="course-count-student">3 Student</div></div>';

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleControl
						label={ __( 'Show Label', 'learnpress' ) }
						checked={ attributes.showLabel }
						onChange={ ( value ) => {
							setAttributes( {
								showLabel: value,
							} );
						} }
					/>
					<ToggleControl
						label={ __( 'Show Icon', 'learnpress' ) }
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
							<span
								dangerouslySetInnerHTML={ { __html: '<i class="lp-icon-user-graduate"></i>' } }
							/>
						) }
						{ attributes.showLabel ? 'Student:' : '' }
					</span>
					<span
						className="info-meta-right"
						dangerouslySetInnerHTML={ {
							__html: courseStudent,
						} }
					></span>
				</div>
			</div>
		</>
	);
};

export default Edit;
