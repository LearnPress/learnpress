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
						help="The default value is the current course id"
						onChange={ ( value ) => props.setAttributes( { courseId: value ? value : '' } ) }
					/>
					<ToggleControl
						label="Avatar"
						checked={ props.attributes.avatar ? true : false }
						onChange={ ( value ) => props.setAttributes( { avatar: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<span>
					{ 'Instructor' }
				</span>
			</div>
		</>
	);
};
