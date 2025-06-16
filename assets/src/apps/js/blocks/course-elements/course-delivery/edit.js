import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseDelivery = '<span class="course-deliver-type">Private 1-1</span>';

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
							<span dangerouslySetInnerHTML={ { __html: '<i class="lp-icon-bookmark-o"></i>' } } />
						) }
						{ attributes.showLabel ? __( 'Delivery type:', 'learnpress' ) : '' }
					</span>
					<span
						className="info-meta-right"
						dangerouslySetInnerHTML={ {
							__html: courseDelivery,
						} }
					></span>
				</div>
			</div>
		</>
	);
};

export default Edit;
