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
						type="number"
						onChange={ ( value ) => props.setAttributes( { courseId: value ? value : '' } ) }
					/>
					<TextControl
						label="Title"
						value={ props.attributes.title }
						onChange={ ( value ) => props.setAttributes( { title: value ? value : '' } ) }
					/>
					<TextControl
						label="Meta Key"
						value={ props.attributes.metaKey }
						onChange={ ( value ) => props.setAttributes( { metaKey: value ? value : '' } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<strong>
					{ 'Box Extra Single Course' }
				</strong>
			</div>
		</>
	);
};
