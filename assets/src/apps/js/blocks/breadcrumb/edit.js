import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleControl
						label={ __( 'Show Home', 'learnpress' ) }
						checked={ props.attributes.showHome ? true : false }
						onChange={ ( value ) =>
							props.setAttributes( {
								showHome: value ? true : false,
							} )
						}
					/>
					{ props.attributes.showHome ? (
						<TextControl
							label={ __( 'Home Label', 'learnpress' ) }
							onChange={ ( value ) => {
								props.setAttributes( {
									homeLabel: value ?? 'Home',
								} );
							} }
							value={ props.attributes.homeLabel ?? 'Home' }
						/>
					) : (
						''
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<ul className="learn-press-breadcrumb">
					{ props.attributes.showHome ? (
						<>
							<li>
								<a>{ props.attributes.homeLabel }</a>
							</li>
							<li className="breadcrumb-delimiter">
								<i className="lp-icon-angle-right"></i>
							</li>
						</>
					) : (
						''
					) }
					<li>
						<a>{ __( 'Navigation', 'learnpress' ) }</a>
					</li>
					<li className="breadcrumb-delimiter">
						<i className="lp-icon-angle-right"></i>
					</li>
					<li>
						<span>{ __( 'Path', 'learnpress' ) }</span>
					</li>
				</ul>
			</div>
		</>
	);
};
