import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { RawHTML } from '@wordpress/element';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

const Edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const { tag, isLink, target } = attributes;
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
						onChange={ ( value ) => setAttributes( { tag: value } ) }
					/>
					<ToggleControl
						label="Is Link"
						checked={ !! isLink }
						onChange={ ( value ) => {
							props.setAttributes( {
								isLink: value,
							} );
						} }
					/>
					{ props.attributes.isLink ? (
						<ToggleControl
							label="Open is new tab"
							checked={ !! target }
							onChange={ ( value ) => {
								props.setAttributes( {
									target: value,
								} );
							} }
						/>
					) : (
						''
					) }
				</PanelBody>
			</InspectorControls>
			<div>
				{ isLink ? (
					<a className="course-permalink">
						<TagName
							className="course-title"
							{ ...blockProps }
							dangerouslySetInnerHTML={ {
								__html: courseTitle,
							} }
						/>
					</a>
				) : (
					<TagName
						{ ...blockProps }
						dangerouslySetInnerHTML={ {
							__html: courseTitle,
						} }
					/>
				) }
			</div>
		</>
	);
};

export default Edit;
