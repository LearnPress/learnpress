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
						<h3> { 'Features' } </h3>
						<div className="line"></div>
						<div className="line"></div>
						<div className="line"></div>
					</>
				) : (
					<div className="features-single-course__classic">
						<span> { 'Features' } </span>
					</div>
				) }
			</div>
		</>
	);
};
