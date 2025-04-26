import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	const mode = [
		{ label: 'Icon + Number + Text', value: '' },
		{ label: 'Icon + Number', value: 'text' },
		{ label: 'Number + Text', value: 'icon' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<SelectControl
						label={ __( 'Display Modes', 'learnpress' ) }
						value={ props.attributes.hidden }
						options={ mode }
						onChange={ ( value ) =>
							props.setAttributes( {
								hidden: value ? value : '',
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="wrapper-instructor-total-students">
					{ props.attributes.hidden && props.attributes.hidden === 'icon' ? (
						''
					) : (
						<span className="lp-ico lp-icon-students"></span>
					) }

					<span className="instructor-total-students">{ '99' }</span>
					{ props.attributes.hidden && props.attributes.hidden === 'text' ? (
						''
					) : (
						<span>{ ' Students' }</span>
					) }
				</div>
			</div>
		</>
	);
};
