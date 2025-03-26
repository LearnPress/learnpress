import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<ToggleControl
						label="Show Home"
						checked={ props.attributes.showHome ? true : false }
						onChange={ ( value ) =>
							props.setAttributes( {
								showHome: value ? true : false,
							} )
						}
					/>
					{ props.attributes.showHome ? (
						<TextControl
							label="Home Label"
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
				<span>
					{ props.attributes.showHome ? (
						<>
							<a>{ props.attributes.homeLabel }</a>
							{ ' / ' }
						</>
					) : (
						''
					) }
					<a>{ 'Navigation' }</a>
					{ ' / Path' }
				</span>
			</div>
		</>
	);
};
