import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { RawHTML } from '@wordpress/element';
import { PanelBody, SelectControl } from '@wordpress/components';

const Edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const { tag } = attributes;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const tagOptions = [
		{ label: 'span', value: 'span' },
		{ label: 'div', value: 'div' },
		{ label: 'h1', value: 'h1' },
		{ label: 'h2', value: 'h2' },
		{ label: 'h3', value: 'h3' },
		{ label: 'h4', value: 'h4' },
		{ label: 'h5', value: 'h5' },
		{ label: 'h6', value: 'h6' },
	];
	const courseTitle = lpCourseData?.title || __( 'Course Title', 'learnpress' );
	const TagName = tag;

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<SelectControl
						label="Tag"
						value={ tag }
						options={ tagOptions }
						onChange={ ( value ) =>
							setAttributes( { tag: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<TagName
					dangerouslySetInnerHTML={ {
						__html: courseTitle,
					} }
				/>
			</div>
		</>
	);
};

export default Edit;
