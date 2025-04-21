import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseDuration =
		lpCourseData?.duration ||
		'<div class="course-count-duration"><span class="course-duration">10 Weeks</span></div>';

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
						{ props.attributes.showIcon && (
							<span dangerouslySetInnerHTML={ { __html: '<i class="lp-icon-clock-o"></i>' } } />
						) }
						{ props.attributes.showLabel ? 'Duration:' : '' }
					</span>
					<span
						className="info-meta-right"
						dangerouslySetInnerHTML={ {
							__html: courseDuration,
						} }
					></span>
				</div>
			</div>
		</>
	);
};

export default Edit;
