import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

const Edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseCategory = lpCourseData?.category || 'Category';
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleControl
						label={ __( "Show text 'by'", 'learnpress' ) }
						checked={ attributes.showText }
						onChange={ ( value ) => {
							setAttributes( {
								showText: value,
							} );
						} }
					/>
					<ToggleControl
						label={ __( 'Make the category a link', 'learnpress' ) }
						checked={ attributes.isLink }
						onChange={ ( value ) => {
							setAttributes( {
								isLink: value,
							} );
						} }
					/>
					{ attributes.isLink ? (
						<ToggleControl
							label={ __( 'Open is new tab', 'learnpress' ) }
							checked={ attributes.target }
							onChange={ ( value ) => {
								setAttributes( {
									target: value,
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
					{ attributes.showText ? 'in ' : '' }
					<div className="course-categories">
						<div
							dangerouslySetInnerHTML={ {
								__html: courseCategory,
							} }
						></div>
					</div>
				</div>
			</div>
		</>
	);
};

export default Edit;
