import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const { attributes, setAttributes, context } = props;
	const { lpCourseData } = context;
	const courseCategory = lpCourseData?.category || 'Category';
	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<ToggleControl
						label="Show text 'by'"
						checked={ props.attributes.showText ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								showText: value ? true : false,
							} );
						} }
					/>
					<ToggleControl
						label="Is link"
						checked={ props.attributes.isLink ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								isLink: value ? true : false,
							} );
						} }
					/>
					{ props.attributes.isLink ? (
						<ToggleControl
							label="Open is new tab"
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
				<div className="category">
					{ props.attributes.showText ? 'in ' : '' }
					{ props.attributes.isLink ? (
						<a
							dangerouslySetInnerHTML={ {
								__html: courseCategory,
							} }
						></a>
					) : (
						<div
							dangerouslySetInnerHTML={ {
								__html: courseCategory,
							} }
						></div>
					) }
				</div>
			</div>
		</>
	);
};
