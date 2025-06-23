import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseInstructor = lpCourseData?.instructor || '<strong>Instructor</strong>';
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleControl
						label={ __( "Show text 'by'", 'learnpress' ) }
						checked={ props.attributes.showText ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								showText: value ? true : false,
							} );
						} }
					/>
					<ToggleControl
						label={ __( 'Make the instructor a link', 'learnpress' ) }
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
			<div { ...blockProps }>
				<div className="is-layout-flex c-gap-4">
					<label>{ props.attributes.showText ? 'by ' : '' }</label>
					<div className="course-instructor">
						{ props.attributes.isLink ? (
							<a
								dangerouslySetInnerHTML={ {
									__html: courseInstructor,
								} }
							></a>
						) : (
							<div
								dangerouslySetInnerHTML={ {
									__html: courseInstructor,
								} }
							></div>
						) }
					</div>
				</div>
			</div>
		</>
	);
};
