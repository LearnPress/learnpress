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
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<SelectControl
						label={ __( 'Tag', 'learnpress' ) }
						value={ tag }
						options={ tagOptions }
						onChange={ ( value ) => setAttributes( { tag: value } ) }
					/>
					<ToggleControl
						// Default text of WP so not need text-domain
						label={ __( 'Make the title a link' ) }
						checked={ !! isLink }
						onChange={ ( value ) => {
							props.setAttributes( {
								isLink: value,
							} );
						} }
					/>
					{ props.attributes.isLink ? (
						<ToggleControl
							label={ __( 'Open is new tab', 'learnpress' ) }
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

			{ props.attributes.isLink ? (
				<TagName { ...blockProps }>
					<a
						dangerouslySetInnerHTML={ {
							__html: courseTitle,
						} }
					></a>
				</TagName>
			) : (
				<TagName
					{ ...blockProps }
					dangerouslySetInnerHTML={ {
						__html: courseTitle,
					} }
				/>
			) }
		</>
	);
};

export default Edit;
