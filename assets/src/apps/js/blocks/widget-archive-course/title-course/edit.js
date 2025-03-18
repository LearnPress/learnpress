import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const tag = [
		{ label: 'h1', value: 'h1' },
		{ label: 'h2', value: 'h2' },
		{ label: 'h3', value: 'h3' },
		{ label: 'h4', value: 'h4' },
		{ label: 'h5', value: 'h5' },
		{ label: 'h6', value: 'h6' },
	];
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<SelectControl
						label="Tag"
						value={ props.attributes.tag }
						options={ tag }
						onChange={ ( value ) =>
							props.setAttributes( { tag: value ? value : '' } )
						}
					/>
					<ToggleControl
						label="Is Link"
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
				<span style={ { lineHeight: '1.3', fontSize: '1.5em' } }>
					{ props.attributes.isLink ? <a>{ 'Title' }</a> : 'Title' }
				</span>
			</div>
		</>
	);
};
