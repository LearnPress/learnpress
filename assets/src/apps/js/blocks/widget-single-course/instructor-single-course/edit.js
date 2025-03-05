import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<ToggleGroupControl
						label="Layout"
						isBlock
						value={ props.attributes.layout ?? 'classic' }
						onChange={ ( value ) =>
							props.setAttributes( { layout: value } )
						}
					>
						<ToggleGroupControlOption
							value="classic"
							label="Classic"
						/>
						<ToggleGroupControlOption
							value="modern"
							label="Modern"
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ props.attributes.layout === 'modern' ? (
					<>
						<span>{ 'by ' }</span>
						<strong>{ 'Instructor' }</strong>
					</>
				) : (
					<>
						<div className="avatar"></div>
						<strong>{ 'Instructor' }</strong>
					</>
				) }
			</div>
		</>
	);
};
