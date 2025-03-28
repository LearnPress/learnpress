import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

const Edit = ( props ) => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps();
	const { lpCourseData } = context;
	const tag = [
		{ label: 'h1', value: 'h1' },
		{ label: 'h2', value: 'h2' },
		{ label: 'h3', value: 'h3' },
		{ label: 'h4', value: 'h4' },
		{ label: 'h5', value: 'h5' },
		{ label: 'h6', value: 'h6' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title="Settings">
					<SelectControl
						label="Tag"
						value={ props.attributes.tag }
						options={ tag }
						onChange={ ( value ) =>
							props.setAttributes( { tag: value ? value : '' } )
						}
					/>
					<ToggleControl
						label="Is Link"
						checked={ props.attributes.isLink ? true : false }
						onChange={ ( value ) => {
							props.setAttributes( {
								isLink: value ? true : false,
							} );
						} }
					/>
					{ props.attributes.isLink ? (
						<ToggleControl
							label="Open is new tab"
							checked={ props.attributes.target ? true : false }
							onChange={ ( value ) => {
								props.setAttributes( {
									target: value ? true : false,
								} );
							} }
						/>
					) : (
						''
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div>
					{ lpCourseData?.post_title ??
						__( 'Course Title', 'learnpress' ) }
				</div>
			</div>
		</>
	);
};

export default Edit;
