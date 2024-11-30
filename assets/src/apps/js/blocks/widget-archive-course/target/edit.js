import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<ToggleControl
						label="Custom Layout"
						help={ 'You need to turn off ajax loading and turn on the custom layout if you want to customize the course layout.' }
						checked={ props.attributes.custom ? true : false }
						onChange={ ( value ) => props.setAttributes( { custom: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
		</>
	);
};
