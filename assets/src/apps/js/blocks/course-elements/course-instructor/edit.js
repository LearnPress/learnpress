import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
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
				<div className="instructor">
					{ props.attributes.showText ? 'by ' : '' }
					{ props.attributes.isLink ? (
						<a>
							{ ' ' }
							<strong>{ 'Instructor' }</strong>{ ' ' }
						</a>
					) : (
						<strong>{ 'Instructor' }</strong>
					) }
				</div>
			</div>
		</>
	);
};
