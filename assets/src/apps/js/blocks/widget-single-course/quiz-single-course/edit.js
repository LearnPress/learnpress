import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<ToggleControl
						label="Show Only Number"
						checked={ props.attributes.showOnlyNumber ? true : false }
						onChange={ ( value ) => props.setAttributes( { showOnlyNumber: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<span>
					{ '0 Quiz' }
				</span>
			</div>
		</>
	);
};
