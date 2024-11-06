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
					<TextControl
						label="Course ID"
						value={ props.attributes.courseId }
						type="number"
						onChange={ ( value ) => props.setAttributes( { courseId: value ? value : '' } ) }
					/>
					<TextControl
						label="Item Type"
						value={ props.attributes.itemType }
						onChange={ ( value ) => props.setAttributes( { itemType: value ? value : '' } ) }
					/>
					<ToggleControl
						label="Show Only Number"
						checked={ props.attributes.showOnlyNumber ? true : false }
						onChange={ ( value ) => props.setAttributes( { showOnlyNumber: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<strong>
					{ 'Count Item Single Course' }
				</strong>
			</div>
		</>
	);
};
