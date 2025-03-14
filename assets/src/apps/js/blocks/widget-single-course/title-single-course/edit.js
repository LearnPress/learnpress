import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';

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
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<h3>{ 'Title' }</h3>
			</div>
		</>
	);
};
