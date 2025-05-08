import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	Icon,
	Dropdown,
	Button,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';

import {
	useBlockProps,
	BlockControls,
	InspectorControls,
	AlignmentToolbar,
	JustifyToolbar,
	BlockVerticalAlignmentToolbar,
} from '@wordpress/block-editor';

const Edit = ( props ) => {
	const { attributes = {}, setAttributes } = props;
	const blockProps = useBlockProps( {
		style: {
			textAlign: attributes.textAlign,
			width: attributes.width ? `${ attributes.width }%` : undefined,
		},
	} );

	// classOfDiv to fix align.
	let classOfDiv = blockProps.className || '';
	classOfDiv = classOfDiv.replaceAll( 'wp-block-learnpress-course-button', '' );

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ attributes.textAlign }
					onChange={ ( newAlign ) => setAttributes( { textAlign: newAlign } ) }
				/>
				<JustifyToolbar
					value={ attributes.justifyContent }
					onChange={ ( newJustify ) => setAttributes( { justifyContent: newJustify } ) }
				/>
				<BlockVerticalAlignmentToolbar
					value={attributes.verticalAlign}
					onChange={(newAlign) => setAttributes({ verticalAlign: newAlign })}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress' ) }>
					<ToggleGroupControl
						label={ __( 'Width', 'learnpress' ) }
						value={ attributes.width || '' }
						onChange={ ( value ) => {
							setAttributes( {
								width: value || '',
							} );
						} }
						isBlock={ true }
					>
						<ToggleGroupControlOption value="25" label="25%" />
						<ToggleGroupControlOption value="50" label="50%" />
						<ToggleGroupControlOption value="75" label="75%" />
						<ToggleGroupControlOption value="100" label="100%" />
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div
				className={ classOfDiv }
				style={ {
					display: 'flex',
					textAlign: attributes.textAlign,
					verticalAlign: attributes.verticalAlign,
					justifyContent: attributes.justifyContent,
				} }
			>
				<button { ...blockProps }>{ __( 'Buy Now', 'learnpress' ) }</button>
			</div>
		</>
	);
};

export default Edit;
