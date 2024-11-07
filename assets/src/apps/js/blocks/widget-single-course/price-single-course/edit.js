import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
export const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title="Custom Settings">
					<TextControl
						label="Course ID"
						value={ props.attributes.courseId }
						help="The default value is the current course id"
						type="number"
						onChange={ ( value ) => props.setAttributes( { courseId: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<strong>
					{ 'Price Single Course' }
				</strong>
			</div>
		</>
	);
};
