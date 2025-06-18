import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const courseImage =
		lpCourseData?.image ||
		'<div className="course-img"><img src="https://placehold.co/500x300?text=Course+Image"/></div>';
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleControl
						label={ __( 'Make the image a link', 'learnpress' ) }
						checked={ props.attributes.isLink ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								isLink: value ? true : false,
							} );
						} }
					/>
					{ props.attributes.isLink ? (
						<ToggleControl
							label={ __( 'Open is new tab', 'learnpress' ) }
							checked={ props.attributes.target ? true : false }
							onChange={ ( value ) => {
								props.setAttributes( {
									target: value ? true : false,
								} );
							} }
						/>
					) : (
						''
					) }
				</PanelBody>
			</InspectorControls>
			<a>
				<div
					{ ...blockProps }
					dangerouslySetInnerHTML={ {
						__html: courseImage,
					} }
				></div>
			</a>
		</>
	);
};
