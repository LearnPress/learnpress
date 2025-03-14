import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const position = [
		{ label: 'Center', value: 'center' },
		{ label: 'Left', value: 'left' },
		{ label: 'Right', value: 'right' },
		{ label: 'Top', value: 'top' },
		{ label: 'Bottom', value: 'bottom' },
	];

	const size = [
		{ label: 'Auto', value: 'auto' },
		{ label: 'Cover', value: 'cover' },
		{ label: 'Contain', value: 'contain' },
		{ label: 'Unset', value: 'Unset' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<SelectControl
						label="Background Position"
						value={ props.attributes.position }
						options={ position }
						onChange={ ( value ) =>
							props.setAttributes( {
								position: value ? value : '',
							} )
						}
					/>

					<SelectControl
						label="Background Size"
						value={ props.attributes.size }
						options={ size }
						onChange={ ( value ) =>
							props.setAttributes( {
								size: value ? value : '',
							} )
						}
					/>

					<ToggleControl
						label="Background Repeat"
						checked={ props.attributes.repeat ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								repeat: value ? true : false,
							} );
						} }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="instructor-background"></div>
			</div>
		</>
	);
};
