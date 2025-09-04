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
		{ label: 'Unset', value: 'unset' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<SelectControl
						label={ __( 'Background Position', 'learnpress' ) }
						value={ props.attributes.position }
						options={ position }
						onChange={ ( value ) =>
							props.setAttributes( {
								position: value ? value : '',
							} )
						}
					/>

					<SelectControl
						label={ __( 'Background Size', 'learnpress' ) }
						value={ props.attributes.size }
						options={ size }
						onChange={ ( value ) =>
							props.setAttributes( {
								size: value ? value : '',
							} )
						}
					/>

					<ToggleControl
						label={ __( 'Background Repeat', 'learnpress' ) }
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
				<div className="lp-user-cover-image_background">
					<img
						src="/wp-content/plugins/learnpress/assets/images/no-image.png"
						alt="Instructor background placeholder"
						height={ 285 }
						width={ 1280 }
						style={ { aspectRatio: 4.5, objectFit: 'cover' } }
					></img>
				</div>
			</div>
		</>
	);
};
